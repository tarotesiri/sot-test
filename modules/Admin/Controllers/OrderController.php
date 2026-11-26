<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name    : OrderController.php
 * Description  : Admin Orders Management, Batch-level Approval with Email
 *                Attachment & Clear Status Reporting, Same-No Invoice Print,
 *                Order Invoices Sync (Never mutating sot_companies),
 *                Clean Address Sanitization, Enriched Attendees from
 *                Participants & 6-Value Financial Summary
 * ====================================================================
 */
declare(strict_types=1);

namespace Modules\Admin\Controllers;

use App\Core\Env;
use InvalidArgumentException;
use Modules\Admin\Support\AdminHelper as H;
use Modules\Payment\Services\PaymentApprovalService;
use PDO;
use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;
use Throwable;

final class OrderController extends AdminBaseController
{
    private const PER_PAGE = 20;

    private ?PaymentApprovalService $paymentService = null;
    private ?string $tablePrefix = null;

    private function pdo(): PDO
    {
        $db = $this->db();
        if ($db instanceof PDO) {
            return $db;
        }
        if (is_object($db)) {
            foreach (['getPdo', 'pdo', 'getDb', 'db'] as $method) {
                if (method_exists($db, $method)) {
                    $res = $db->$method();
                    if ($res instanceof PDO) {
                        return $res;
                    }
                }
            }
            $ref = new \ReflectionObject($db);
            foreach ($ref->getProperties() as $prop) {
                $prop->setAccessible(true);
                $val = $prop->getValue($db);
                if ($val instanceof PDO) {
                    return $val;
                }
            }
        }
        throw new RuntimeException('ไม่สามารถดึง PDO instance จากฐานข้อมูลได้');
    }

    private function paymentService(): PaymentApprovalService
    {
        if ($this->paymentService === null) {
            $this->paymentService = new PaymentApprovalService($this->pdo());
        }
        return $this->paymentService;
    }

    private function resolvePrefix(): string
    {
        if ($this->tablePrefix !== null) {
            return $this->tablePrefix;
        }
        $db = $this->db();
        if (is_object($db) && method_exists($db, 'prefix')) {
            $p = (string)$db->prefix();
            if ($p !== '') {
                return $this->tablePrefix = $p;
            }
        }
        return $this->tablePrefix = 'sot_';
    }

    private function table(string $baseName): string
    {
        $clean = ltrim($baseName, '_');
        $prefix = $this->resolvePrefix();
        if ($prefix !== '' && str_starts_with($clean, $prefix)) {
            return $clean;
        }
        return $prefix . $clean;
    }

    private function resolveAdminUserId(): int
    {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            @session_start();
        }
        return (int)($_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 1);
    }

    private function getTableColumns(string $tableName): array
    {
        static $cache = [];
        if (isset($cache[$tableName])) {
            return $cache[$tableName];
        }
        $cols = [];
        try {
            $stmt = $this->pdo()->query("SHOW COLUMNS FROM `{$tableName}`");
            if ($stmt) {
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
                    if (!empty($col['Field'])) {
                        $cols[(string)$col['Field']] = true;
                    }
                }
            }
        } catch (Throwable $e) {
            // ข้าม
        }
        return $cache[$tableName] = $cols;
    }

    private function pickNonEmptyString(array $row, array $keys, string $default = ''): string
    {
        foreach ($keys as $k) {
            if (array_key_exists($k, $row) && $row[$k] !== null && is_scalar($row[$k])) {
                $val = trim((string)$row[$k]);
                if ($val !== '') {
                    return $val;
                }
            }
        }
        return $default;
    }

    private function sanitizeStreetAddress(
        string $street,
        string $district = '',
        string $amphoe = '',
        string $province = '',
        string $zipcode = ''
    ): string {
        $clean = preg_replace('/\s{2,}/u', ' ', trim($street)) ?? trim($street);
        if ($clean === '') {
            return '';
        }

        $clean = preg_replace(
            '/(?:\s*(?:แขวง|ต\.|ตำบล)\s*[^\s]+\s*(?:เขต|อ\.|อำเภอ)\s*[^\s]+(?:\s*(?:จ\.|จังหวัด)?\s*[^\s]+)?(?:\s*\d{5})?)+$/u',
            '',
            $clean
        ) ?? $clean;

        $tokens = [];
        foreach ([$district, $amphoe, $province, $zipcode] as $part) {
            $p = trim($part);
            if ($p === '') {
                continue;
            }
            foreach (['', 'แขวง', 'แขวง ', 'ต.', 'ต. ', 'ตำบล', 'ตำบล ', 'เขต', 'เขต ', 'อ.', 'อ. ', 'อำเภอ', 'อำเภอ ', 'จ.', 'จ. ', 'จังหวัด', 'จังหวัด '] as $prefix) {
                $tokens[] = $prefix . $p;
            }
        }

        $tokens = array_values(array_unique(array_filter($tokens, static fn(string $s): bool => $s !== '')));
        usort($tokens, static fn(string $a, string $b): int => mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8'));

        foreach ($tokens as $tok) {
            $clean = str_replace($tok, ' ', $clean);
        }

        $clean = preg_replace('/(?:แขวง|ต\.|ตำบล|เขต|อ\.|อำเภอ|จ\.|จังหวัด)(?=\s|$)/u', ' ', $clean) ?? $clean;
        $clean = preg_replace('/\s{2,}/u', ' ', $clean) ?? $clean;

        return trim($clean, " \t\n\r\0\x0B,");
    }

    /**
     * โหลดข้อมูลผู้เข้าอบรมสำหรับรายการ order_item_ids โดยผสานข้อมูลจาก
     * order_attendees + participants + users ให้ครบ (เลขบัตร, ตำแหน่ง, ความต้องการอื่นๆ)
     *
     * @param int[] $itemIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function loadAttendeesForItems(array $itemIds): array
    {
        $cleanIds = array_values(array_unique(array_filter(
            array_map('intval', $itemIds),
            static fn(int $v): bool => $v > 0
        )));
        if ($cleanIds === []) {
            return [];
        }

        $pdo               = $this->pdo();
        $orderAttTable     = $this->table('order_attendees');
        $participantsTable = $this->table('participants');
        $usersTable        = $this->table('users');

        $attCols  = $this->getTableColumns($orderAttTable);
        $partCols = $this->getTableColumns($participantsTable);
        $userCols = $this->getTableColumns($usersTable);

        if ($attCols === []) {
            return [];
        }

        $selectParts = ['oa.*'];
        $joins = [];

        if ($partCols !== []) {
            $hasPartIdJoin    = isset($attCols['participant_id']) && isset($partCols['id']);
            $hasPartUserJoin  = isset($attCols['user_id']) && isset($partCols['learner_user_id']);
            $hasPartEmailJoin = isset($attCols['email']) && isset($partCols['email']);

            $conds = [];
            if ($hasPartIdJoin) {
                $conds[] = 'p.`id` = oa.`participant_id`';
            }
            if ($hasPartUserJoin) {
                $conds[] = '(oa.`user_id` IS NOT NULL AND p.`learner_user_id` = oa.`user_id`)';
            }
            if ($hasPartEmailJoin) {
                $conds[] = "(oa.`email` IS NOT NULL AND oa.`email` <> '' AND LOWER(TRIM(p.`email`)) = LOWER(TRIM(oa.`email`)))";
            }

            if ($conds !== []) {
                $joins[] = "LEFT JOIN `{$participantsTable}` p ON (" . implode(' OR ', $conds) . ")";
                foreach (['id_card', 'citizen_id', 'national_id', 'position', 'job_position', 'role_title', 'prefix_name', 'first_name', 'last_name', 'email', 'phone', 'pin_code', 'other_requirements', 'special_requirements'] as $pCol) {
                    if (isset($partCols[$pCol])) {
                        $selectParts[] = "p.`{$pCol}` AS `part_{$pCol}`";
                    }
                }
            }
        }

        if ($userCols !== [] && isset($attCols['user_id']) && isset($userCols['id'])) {
            $joins[] = "LEFT JOIN `{$usersTable}` u ON u.`id` = oa.`user_id`";
            foreach (['id_card', 'citizen_id', 'position', 'job_position', 'prefix', 'firstname', 'lastname', 'email', 'phone'] as $uCol) {
                if (isset($userCols[$uCol])) {
                    $selectParts[] = "u.`{$uCol}` AS `usr_{$uCol}`";
                }
            }
        }

        $inPh = implode(',', array_fill(0, count($cleanIds), '?'));
        $sql = "SELECT " . implode(', ', $selectParts)
            . " FROM `{$orderAttTable}` oa "
            . implode(' ', $joins)
            . " WHERE oa.`order_item_id` IN ({$inPh}) ORDER BY oa.`id` ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($cleanIds);
        $rawRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $grouped = [];
        foreach ($rawRows as $row) {
            $oiid = (int)($row['order_item_id'] ?? 0);
            if ($oiid <= 0) {
                continue;
            }

            $prefix = $this->pickNonEmptyString($row, [
                'prefix', 'prefix_name', 'title', 'part_prefix_name', 'usr_prefix',
            ]);
            $firstName = $this->pickNonEmptyString($row, [
                'first_name', 'fname', 'part_first_name', 'usr_firstname',
            ]);
            $lastName = $this->pickNonEmptyString($row, [
                'last_name', 'lname', 'part_last_name', 'usr_lastname',
            ]);
            if ($firstName === '' && $lastName === '') {
                $full = $this->pickNonEmptyString($row, ['full_name', 'attendee_name']);
                if ($full !== '') {
                    $parts = preg_split('/\s+/u', $full, 2) ?: [];
                    $firstName = trim((string)($parts[0] ?? ''));
                    $lastName  = trim((string)($parts[1] ?? ''));
                }
            }

            $email = $this->pickNonEmptyString($row, [
                'email', 'attendee_email', 'part_email', 'usr_email',
            ]);
            $phone = $this->pickNonEmptyString($row, [
                'phone', 'tel', 'mobile', 'part_phone', 'usr_phone',
            ]);
            $idCard = $this->pickNonEmptyString($row, [
                'id_card', 'citizen_id', 'national_id', 'id_number',
                'part_id_card', 'part_citizen_id', 'part_national_id',
                'usr_id_card', 'usr_citizen_id',
            ]);
            $position = $this->pickNonEmptyString($row, [
                'position', 'job_title', 'job_position', 'role', 'role_title',
                'part_position', 'part_job_position', 'part_role_title',
                'usr_position', 'usr_job_position',
            ]);
            $otherReq = $this->pickNonEmptyString($row, [
                'other_requirements', 'other_requirement', 'other_req',
                'special_requirements', 'special_requirement',
                'special_request', 'other_request', 'dietary', 'remark', 'note',
                'part_other_requirements', 'part_special_requirements',
            ]);
            $pinCode = $this->pickNonEmptyString($row, ['pin_code', 'pin', 'part_pin_code']);

            $row['prefix']             = $prefix;
            $row['first_name']         = $firstName;
            $row['last_name']          = $lastName;
            $row['email']              = $email;
            $row['phone']              = $phone;
            $row['id_card']            = $idCard;
            $row['position']           = $position;
            $row['other_requirements'] = $otherReq;
            $row['pin_code']           = $pinCode;

            $grouped[$oiid][] = $row;
        }

        return $grouped;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadCompaniesOptions(): array
    {
        $pdo = $this->pdo();
        $companiesTable = $this->table('companies');
        $rows = [];
        try {
            $stmt = $pdo->query("SELECT * FROM `{$companiesTable}` ORDER BY `id` DESC LIMIT 500");
            if ($stmt) {
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $c) {
                    $cId = (int)($c['id'] ?? 0);
                    $cName = trim((string)($c['company_name'] ?? $c['name'] ?? ''));
                    if ($cId > 0 || $cName !== '') {
                        $rows[] = [
                            'id'            => $cId,
                            'tax_type'      => (string)($c['tax_type'] ?? 'corporate'),
                            'company_name'  => $cName,
                            'branch'        => trim((string)($c['branch'] ?? '')),
                            'tax_id'        => trim((string)($c['tax_id'] ?? '')),
                            'address'       => trim((string)($c['address'] ?? '')),
                            'province'      => trim((string)($c['province'] ?? '')),
                            'amphoe'        => trim((string)($c['amphoe'] ?? '')),
                            'district'      => trim((string)($c['district'] ?? '')),
                            'zipcode'       => trim((string)($c['zipcode'] ?? '')),
                            'phone'         => trim((string)($c['phone'] ?? '')),
                            'billing_email' => trim((string)($c['billing_email'] ?? '')),
                        ];
                    }
                }
            }
        } catch (Throwable $e) {
            // ข้าม
        }
        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadOrderInvoiceRow(int $orderId): array
    {
        $invTable = $this->table('order_invoices');
        try {
            $stmt = $this->pdo()->prepare("SELECT * FROM `{$invTable}` WHERE `order_id` = :oid ORDER BY `id` DESC LIMIT 1");
            $stmt->execute([':oid' => $orderId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                return [];
            }

            $addrText = trim((string)($row['address'] ?? ''));
            $poNumber = trim((string)($row['po_number'] ?? ''));

            if ($poNumber === '' && preg_match('/\bPO\s*Number\s*:\s*([^\r\n]+)/iu', $addrText, $mPo)) {
                $poNumber = trim($mPo[1]);
                $addrText = trim((string)preg_replace('/\r?\n?\s*\bPO\s*Number\s*:\s*[^\r\n]+/iu', '', $addrText));
            }

            $district = trim((string)($row['district'] ?? ''));
            $amphoe   = trim((string)($row['amphoe'] ?? ''));
            $province = trim((string)($row['province'] ?? ''));
            $zipcode  = trim((string)($row['zipcode'] ?? ''));

            if ($district === '' && preg_match('/(?:ต\.\/แขวง|แขวง|ต\.)\s*([^\s]+)/u', $addrText, $mD)) {
                $district = trim($mD[1]);
            }
            if ($amphoe === '' && preg_match('/(?:อ\.\/เขต|เขต|อ\.)\s*([^\s]+)/u', $addrText, $mA)) {
                $amphoe = trim($mA[1]);
            }
            if ($province === '' && preg_match('/จ\.([^\s]+)/u', $addrText, $mP)) {
                $province = trim($mP[1]);
            }
            if ($zipcode === '' && preg_match('/\b(\d{5})\b$/', $addrText, $mZ)) {
                $zipcode = trim($mZ[1]);
            }

            $street = $this->sanitizeStreetAddress($addrText, $district, $amphoe, $province, $zipcode);

            $row['po_number']      = $poNumber;
            $row['street_address'] = $street;
            $row['district']       = $district;
            $row['amphoe']         = $amphoe;
            $row['province']       = $province;
            $row['zipcode']        = $zipcode;

            return $row;
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function enrichOrderData(array $order, ?array $invoiceRow = null): array
    {
        $orderId = (int)($order['id'] ?? 0);
        if ($invoiceRow === null && $orderId > 0) {
            $invoiceRow = $this->loadOrderInvoiceRow($orderId);
        }
        $inv = is_array($invoiceRow) ? $invoiceRow : [];

        $totalSubtotal = round((float)($order['total_subtotal'] ?? 0.0), 2);
        $vatRate       = round((float)($order['vat_rate'] ?? 0.0), 2);
        $totalVat      = round((float)($order['total_vat'] ?? 0.0), 2);
        $whtRate       = round((float)($order['wht_rate'] ?? 0.0), 2);
        $totalWht      = round((float)($order['total_wht'] ?? 0.0), 2);
        $grandTotal    = round((float)($order['grand_total'] ?? 0.0), 2);

        $coordName  = trim((string)($order['customer_name'] ?? ''));
        $coordPhone = trim((string)($order['customer_phone'] ?? ''));
        $coordEmail = trim((string)($order['customer_email'] ?? ''));
        $otherNote  = trim((string)($order['special_requirements'] ?? ''));

        $taxType    = trim((string)($inv['tax_type'] ?? 'corporate'));
        $compName   = trim((string)($inv['company_name'] ?? ''));
        $branch     = trim((string)($inv['branch'] ?? ''));
        $taxId      = trim((string)($inv['tax_id'] ?? ''));
        $poNumber   = trim((string)($inv['po_number'] ?? ''));
        $province   = trim((string)($inv['province'] ?? ''));
        $amphoe     = trim((string)($inv['amphoe'] ?? ''));
        $district   = trim((string)($inv['district'] ?? ''));
        $zipcode    = trim((string)($inv['zipcode'] ?? ''));
        $streetAddr = $this->sanitizeStreetAddress(
            (string)($inv['street_address'] ?? $inv['address'] ?? ''),
            $district,
            $amphoe,
            $province,
            $zipcode
        );

        $isBkk = $province !== '' && (str_contains($province, 'กรุงเทพ') || mb_strtolower($province, 'UTF-8') === 'bangkok');
        $addrDisplayParts = [];
        if ($streetAddr !== '') {
            $addrDisplayParts[] = $streetAddr;
        }
        if ($district !== '') {
            $addrDisplayParts[] = $isBkk ? ('แขวง' . $district) : ('ต.' . $district);
        }
        if ($amphoe !== '') {
            $addrDisplayParts[] = $isBkk ? ('เขต' . $amphoe) : ('อ.' . $amphoe);
        }
        if ($province !== '') {
            $addrDisplayParts[] = $isBkk ? $province : ('จ.' . $province);
        }
        if ($zipcode !== '') {
            $addrDisplayParts[] = $zipcode;
        }
        $cleanFullAddr = implode(' ', $addrDisplayParts);

        $order['_invoice_row']       = $inv;
        $order['_total_subtotal']    = $totalSubtotal;
        $order['_vat_rate']          = $vatRate;
        $order['_total_vat']         = $totalVat;
        $order['_wht_rate']          = $whtRate;
        $order['_total_wht']         = $totalWht;
        $order['_grand_total']       = $grandTotal;
        $order['_coordinator_name']  = $coordName;
        $order['_coordinator_phone'] = $coordPhone;
        $order['_coordinator_email'] = $coordEmail;
        $order['_other_note']        = $otherNote;
        $order['_tax_type']          = $taxType;
        $order['_company_name']      = $compName;
        $order['_branch']            = $branch;
        $order['_branch_label']      = $branch;
        $order['_tax_id']            = $taxId;
        $order['_po_number']         = $poNumber;
        $order['_street_address']    = $streetAddr;
        $order['_province']          = $province;
        $order['_amphoe']            = $amphoe;
        $order['_district']          = $district;
        $order['_zipcode']           = $zipcode;
        $order['_full_address']      = $cleanFullAddr !== '' ? $cleanFullAddr : $streetAddr;

        return $order;
    }

    /**
     * GET /admin/orders
     */
    public function index(): string
    {
        $this->admGuard();

        $pdo              = $this->pdo();
        $ordersTable      = $this->table('orders');
        $orderItemsTable  = $this->table('order_items');
        $orderAttTable    = $this->table('order_attendees');
        $enrollmentsTable = $this->table('enrollments');
        $paymentsTable    = $this->table('order_payments');
        $coursesTable     = $this->table('courses');
        $batchesTable     = $this->table('course_batches');

        $q      = H::query('q');
        $status = H::query('status');
        $page   = max(1, H::inInt('page', 1));
        $offset = ($page - 1) * self::PER_PAGE;

        $orderCols = $this->getTableColumns($ordersTable);
        $where     = [];
        $params    = [];

        if ($status !== '' && PaymentApprovalService::isValidStatus($status)) {
            $where[]           = 'o.`payment_status` = :status';
            $params[':status'] = $status;
        }

        if ($q !== '') {
            $searchCols = ['order_no', 'invoice_no', 'quotation_no', 'customer_name', 'company_name', 'tax_id'];
            $likes = [];
            foreach ($searchCols as $idx => $sc) {
                if (isset($orderCols[$sc])) {
                    $ph = ':q' . $idx;
                    $likes[] = "o.`{$sc}` LIKE {$ph}";
                    $params[$ph] = '%' . $q . '%';
                }
            }
            if ($likes !== []) {
                $where[] = '(' . implode(' OR ', $likes) . ')';
            }
        }

        $whereSql = $where !== [] ? ('WHERE ' . implode(' AND ', $where)) : '';

        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM `{$ordersTable}` o {$whereSql}");
        $stmtCount->execute($params);
        $totalItems = (int)$stmtCount->fetchColumn();
        $totalPages = max(1, (int)ceil($totalItems / self::PER_PAGE));

        $sql = "
            SELECT
                o.*,
                (SELECT COUNT(*) FROM `{$orderItemsTable}` oi WHERE oi.`order_id` = o.`id`) AS course_count,
                (
                    SELECT COUNT(*)
                    FROM `{$orderAttTable}` oa
                    INNER JOIN `{$orderItemsTable}` oi_att ON oi_att.`id` = oa.`order_item_id`
                    WHERE oi_att.`order_id` = o.`id`
                ) AS attendee_count,
                (SELECT COUNT(*) FROM `{$paymentsTable}` p WHERE p.`order_id` = o.`id`) AS slip_count
            FROM `{$ordersTable}` o
            {$whereSql}
            ORDER BY o.`id` DESC
            LIMIT " . self::PER_PAGE . " OFFSET {$offset}
        ";
        $stmtList = $pdo->prepare($sql);
        $stmtList->execute($params);
        $rawOrders = $stmtList->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $orders = [];
        foreach ($rawOrders as $ordRow) {
            $oidVal = (int)($ordRow['id'] ?? 0);
            if ((int)($ordRow['attendee_count'] ?? 0) === 0 && $oidVal > 0) {
                try {
                    $stmtEnrCnt = $pdo->prepare("SELECT COUNT(*) FROM `{$enrollmentsTable}` WHERE `order_id` = ?");
                    $stmtEnrCnt->execute([$oidVal]);
                    $ordRow['attendee_count'] = (int)$stmtEnrCnt->fetchColumn();
                } catch (Throwable $e) {
                    // ข้าม
                }
            }
            $orders[] = $this->enrichOrderData($ordRow);
        }

        $orderBatchesMap         = [];
        $orderSlipsMap           = [];
        $orderAttendeesByItemMap = [];

        $orderIds = array_values(array_filter(
            array_map(static fn(array $r): int => (int)($r['id'] ?? 0), $orders),
            static fn(int $v): bool => $v > 0
        ));

        if ($orderIds !== []) {
            $inPh = implode(',', array_fill(0, count($orderIds), '?'));

            $stmtBatches = $pdo->prepare("
                SELECT
                    oi.*,
                    c.`title` AS `course_title`,
                    b.`batch_no`,
                    b.`date_text` AS `batch_date_text`,
                    b.`time_text` AS `batch_time_text`,
                    b.`document_file`,
                    b.`document_title`,
                    (SELECT COUNT(*) FROM `{$orderAttTable}` oa WHERE oa.`order_item_id` = oi.`id`) AS batch_attendees
                FROM `{$orderItemsTable}` oi
                LEFT JOIN `{$coursesTable}` c ON c.`id` = oi.`course_id`
                LEFT JOIN `{$batchesTable}` b ON b.`id` = oi.`batch_id`
                WHERE oi.`order_id` IN ({$inPh})
                ORDER BY oi.`id` ASC
            ");
            $stmtBatches->execute($orderIds);
            $itemIds = [];
            foreach ($stmtBatches->fetchAll(PDO::FETCH_ASSOC) ?: [] as $bRow) {
                $oidKey  = (int)($bRow['order_id'] ?? 0);
                $oiidKey = (int)($bRow['id'] ?? 0);
                $orderBatchesMap[$oidKey][] = $bRow;
                if ($oiidKey > 0) {
                    $itemIds[] = $oiidKey;
                }
            }

            if ($itemIds !== []) {
                $orderAttendeesByItemMap = $this->loadAttendeesForItems($itemIds);
            }

            $stmtSlips = $pdo->prepare("
                SELECT `id`, `order_id`, `slip_path`, `slip_original_name`, `transfer_amount`, `transfer_datetime_text`, `transfer_date`, `bank_name`, `created_at`
                FROM `{$paymentsTable}`
                WHERE `order_id` IN ({$inPh})
                ORDER BY `id` ASC
            ");
            $stmtSlips->execute($orderIds);
            foreach ($stmtSlips->fetchAll(PDO::FETCH_ASSOC) ?: [] as $sRow) {
                $oidKey = (int)($sRow['order_id'] ?? 0);
                $orderSlipsMap[$oidKey][] = $sRow;
            }
        }

        foreach ($orders as &$ordItem) {
            $oidItem = (int)($ordItem['id'] ?? 0);
            $rawSt   = strtolower(trim((string)($ordItem['payment_status'] ?? 'pending')));
            $bList   = $orderBatchesMap[$oidItem] ?? [];
            $allApproved = ($rawSt === 'paid') && ($bList !== []);
            if ($allApproved) {
                foreach ($bList as $bItem) {
                    if (strtolower(trim((string)($bItem['batch_approval_status'] ?? ''))) !== 'approved') {
                        $allApproved = false;
                        break;
                    }
                }
            }
            $ordItem['_is_fully_approved'] = $allApproved;
        }
        unset($ordItem);

        $statusCounts = ['all' => 0];
        foreach (PaymentApprovalService::ALL_STATUSES as $st) {
            $statusCounts[$st] = 0;
        }
        $stmtGrp = $pdo->query("SELECT `payment_status`, COUNT(*) AS cnt FROM `{$ordersTable}` GROUP BY `payment_status`");
        if ($stmtGrp) {
            foreach ($stmtGrp->fetchAll(PDO::FETCH_ASSOC) as $g) {
                $stKey = trim((string)($g['payment_status'] ?? ''));
                $cnt   = (int)($g['cnt'] ?? 0);
                $statusCounts['all'] += $cnt;
                if (isset($statusCounts[$stKey])) {
                    $statusCounts[$stKey] = $cnt;
                }
            }
        }

        return $this->admPage('orders/index', [
            'orders'                  => $orders,
            'orderBatchesMap'         => $orderBatchesMap,
            'orderSlipsMap'           => $orderSlipsMap,
            'orderAttendeesByItemMap' => $orderAttendeesByItemMap,
            'filters'                 => ['q' => $q, 'status' => $status],
            'pagination'              => [
                'page'        => $page,
                'per_page'    => self::PER_PAGE,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
            ],
            'statusCounts'            => $statusCounts,
            'paymentStatusLabels'     => PaymentApprovalService::PAYMENT_STATUS_LABELS,
            'statusLabels'            => PaymentApprovalService::PAYMENT_STATUS_LABELS,
        ], 'จัดการคำสั่งซื้อ', 'orders');
    }

    /**
     * POST /admin/orders/{id}/payment-status
     */
    public function updatePaymentStatus(string $id): void
    {
        $this->admGuard();

        $orderId = ctype_digit($id) ? (int)$id : 0;
        if ($orderId <= 0) {
            H::flash('error', 'รหัสคำสั่งซื้อไม่ถูกต้อง');
            H::redirect('admin/orders');
        }

        $redirectTo = H::in('redirect_to', 100);
        $targetUrl  = $redirectTo === 'detail' ? ('admin/orders/' . $orderId) : 'admin/orders';

        try {
            $newStatus   = H::in('payment_status', 50);
            $reason      = H::in('reason', 500);
            $adminUserId = $this->resolveAdminUserId();

            $res = $this->paymentService()->updateOrderPaymentStatus(
                $orderId,
                $newStatus,
                max(1, $adminUserId),
                $reason,
                null
            );

            $label = $res['status_label'] ?? PaymentApprovalService::paymentStatusLabel($newStatus);
            H::flash('success', "อัปเดตสถานะการชำระเงินเป็น \"{$label}\" เรียบร้อยแล้ว");
        } catch (Throwable $e) {
            H::flash('error', $e->getMessage());
        }

        H::redirect($targetUrl);
    }

    /**
     * POST /admin/orders/{id}/batches/{orderItemId}/approve
     */
    public function approveBatch(string $id, string $orderItemId): void
    {
        $this->admGuard();

        $oid  = ctype_digit($id) ? (int)$id : 0;
        $oiid = ctype_digit($orderItemId) ? (int)$orderItemId : 0;
        if ($oid <= 0 || $oiid <= 0) {
            H::flash('error', 'รหัสคำสั่งซื้อหรือรุ่นไม่ถูกต้อง');
            H::redirect('admin/orders');
        }

        $redirectTo = H::in('redirect_to', 100);
        $targetUrl  = $redirectTo === 'detail' ? ('admin/orders/' . $oid) : 'admin/orders';

        try {
            $note        = H::in('note', 500);
            $adminUserId = $this->resolveAdminUserId();

            $res        = $this->paymentService()->approveBatchInOrder($oid, $oiid, max(1, $adminUserId), $note, false);
            $mailStatus = $this->sendBatchApprovalEmail($res);

            $attCount = (int)($res['attachment_count'] ?? 0);
            $enrCount = (int)($res['synced_enrollments'] ?? 0);
            $verb     = !empty($res['was_already_approved']) ? 'อนุมัติซ้ำรุ่น' : 'อนุมัติรุ่น';
            $mailNote = match ($mailStatus) {
                'sent'             => 'และส่งอีเมลแล้ว',
                'logged_localhost' => 'และบันทึกรายการอีเมลลง log (โหมด localhost) แล้ว',
                default            => 'แต่ยังไม่มีผู้รับอีเมลที่ถูกต้องหรือส่งอีเมลไม่สำเร็จ',
            };

            $msg = "{$verb} \"" . ($res['batch_label'] ?? '') . "\" เรียบร้อยแล้ว"
                . " (ซิงก์สิทธิ์เรียน {$enrCount} รายการ · แนบเอกสาร {$attCount} ไฟล์) {$mailNote}";

            H::flash('success', $msg);
        } catch (Throwable $e) {
            H::flash('error', $e->getMessage());
        }

        H::redirect($targetUrl);
    }

    /**
     * คืนค่า 'sent' | 'logged_localhost' | 'failed'
     */
    private function sendBatchApprovalEmail(array $approvalData): string
    {
        $order       = is_array($approvalData['order'] ?? null) ? $approvalData['order'] : [];
        $item        = is_array($approvalData['item'] ?? null) ? $approvalData['item'] : [];
        $attendees   = is_array($approvalData['attendees'] ?? null) ? $approvalData['attendees'] : [];
        $attachments = is_array($approvalData['attachments'] ?? null) ? $approvalData['attachments'] : [];
        $batchLabel  = (string)($approvalData['batch_label'] ?? 'รุ่นอบรม');

        $recipients = [];
        $buyerEmail = strtolower(trim((string)($order['customer_email'] ?? '')));
        if ($buyerEmail !== '' && filter_var($buyerEmail, FILTER_VALIDATE_EMAIL)) {
            $recipients[$buyerEmail] = trim((string)($order['customer_name'] ?? 'ผู้ประสานงาน'));
        }
        foreach ($attendees as $att) {
            $em = strtolower(trim((string)($att['email'] ?? '')));
            if ($em !== '' && filter_var($em, FILTER_VALIDATE_EMAIL)) {
                $full = trim((string)($att['first_name'] ?? '') . ' ' . (string)($att['last_name'] ?? ''));
                $recipients[$em] = $full !== '' ? $full : 'ผู้เข้าอบรม';
            }
        }

        if ($recipients === []) {
            return 'failed';
        }

        $orderNo  = trim((string)($order['order_no'] ?? '')) ?: ('#' . ($order['id'] ?? ''));
        $dateText = trim((string)($item['batch_date_text'] ?? ''));
        $timeText = trim((string)($item['batch_time_text'] ?? ''));

        $subject = "แจ้งอนุมัติการเข้าอบรม: {$batchLabel} (คำสั่งซื้อ {$orderNo})";
        $body    = "เรียน ผู้เกี่ยวข้อง\n"
                 . "คำสั่งซื้อเลขที่: {$orderNo}\n"
                 . "รุ่นที่ได้รับการอนุมัติ: {$batchLabel}\n"
                 . ($dateText !== '' ? "วันที่อบรม: {$dateText}\n" : '')
                 . ($timeText !== '' ? "เวลาอบรม: {$timeText}\n" : '')
                 . "จำนวนผู้เข้าอบรมในรุ่นนี้: " . count($attendees) . " คน\n"
                 . (count($attachments) > 0
                     ? "เอกสารแนบ: " . implode(', ', array_column($attachments, 'name')) . "\n"
                     : "หมายเหตุ: ขณะนี้ยังไม่มีไฟล์เอกสารแนบในระบบสำหรับรุ่นนี้\n");

        $rawLocalFlag = strtolower(trim((string)(class_exists(Env::class) ? Env::get('MAIL_IS_LOCALHOST', '1') : '1')));
        $isLocalhost  = in_array($rawLocalFlag, ['1', 'true', 'yes', 'on'], true);

        if ($isLocalhost) {
            $logDir = (defined('BASE_PATH') ? rtrim((string)BASE_PATH, "/\\") : dirname(__DIR__, 3)) . '/storage/logs/mails';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0775, true);
            }
            $entry = "[" . date('Y-m-d H:i:s') . "] TO: " . implode(', ', array_keys($recipients))
                . " | ATTACHMENTS: " . count($attachments)
                . " | SUBJECT: {$subject}\n{$body}\n----------------------------------------\n";
            @file_put_contents($logDir . '/phpmailer_localhost.log', $entry, FILE_APPEND);
            return 'logged_localhost';
        }

        if (!class_exists(PHPMailer::class)) {
            return 'failed';
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host     = (string)(class_exists(Env::class) ? Env::get('MAIL_HOST', '127.0.0.1') : '127.0.0.1');
            $mail->Port     = (int)(class_exists(Env::class) ? Env::get('MAIL_PORT', '587') : '587');
            $mail->CharSet  = 'UTF-8';
            $mail->SMTPAuth = (string)(class_exists(Env::class) ? Env::get('MAIL_USERNAME', '') : '') !== '';
            $mail->Username = (string)(class_exists(Env::class) ? Env::get('MAIL_USERNAME', '') : '');
            $mail->Password = (string)(class_exists(Env::class) ? Env::get('MAIL_PASSWORD', '') : '');

            $fromEmail = (string)(class_exists(Env::class) ? Env::get('MAIL_FROM_EMAIL', 'noreply@sot.local') : 'noreply@sot.local');
            $fromName  = (string)(class_exists(Env::class) ? Env::get('MAIL_FROM_NAME', 'SIAM Online Training (SOT)') : 'SIAM Online Training (SOT)');
            $mail->setFrom($fromEmail, $fromName);

            foreach ($recipients as $addr => $rName) {
                $mail->addAddress($addr, $rName);
            }
            foreach ($attachments as $attFile) {
                $mail->addAttachment((string)$attFile['path'], (string)$attFile['name']);
            }

            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->send();
            return 'sent';
        } catch (MailException $e) {
            return 'failed';
        }
    }

    /**
     * GET /admin/orders/{id}
     */
    public function show(string $id): string
    {
        $this->admGuard();

        $orderId = ctype_digit($id) ? (int)$id : 0;
        if ($orderId <= 0) {
            H::flash('error', 'รหัสคำสั่งซื้อไม่ถูกต้อง');
            H::redirect('admin/orders');
        }

        $pdo              = $this->pdo();
        $ordersTable      = $this->table('orders');
        $orderItemsTable  = $this->table('order_items');
        $enrollmentsTable = $this->table('enrollments');
        $paymentsTable    = $this->table('order_payments');
        $coursesTable     = $this->table('courses');
        $batchesTable     = $this->table('course_batches');

        $stmtOrder = $pdo->prepare("SELECT * FROM `{$ordersTable}` WHERE `id` = :id LIMIT 1");
        $stmtOrder->execute([':id' => $orderId]);
        $rawOrder = $stmtOrder->fetch(PDO::FETCH_ASSOC);
        if (!$rawOrder) {
            H::flash('error', 'ไม่พบข้อมูลคำสั่งซื้อที่ระบุ');
            H::redirect('admin/orders');
        }

        $invoiceRow = $this->loadOrderInvoiceRow($orderId);
        $order      = $this->enrichOrderData($rawOrder, $invoiceRow);
        $companies  = $this->loadCompaniesOptions();

        $stmtItems = $pdo->prepare("
            SELECT
                oi.*,
                c.`title` AS `course_title`,
                b.`batch_no`,
                b.`date_text` AS `batch_date_text`,
                b.`time_text` AS `batch_time_text`,
                b.`document_file`,
                b.`document_title`
            FROM `{$orderItemsTable}` oi
            LEFT JOIN `{$coursesTable}` c ON c.`id` = oi.`course_id`
            LEFT JOIN `{$batchesTable}` b ON b.`id` = oi.`batch_id`
            WHERE oi.`order_id` = :oid
            ORDER BY oi.`id` ASC
        ");
        $stmtItems->execute([':oid' => $orderId]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $attendeesByItem = [];
        $itemIds = array_values(array_filter(
            array_map(static fn(array $r): int => (int)($r['id'] ?? 0), $items),
            static fn(int $v): bool => $v > 0
        ));
        if ($itemIds !== []) {
            $attendeesByItem = $this->loadAttendeesForItems($itemIds);
        }

        $enrollments = $pdo->prepare("SELECT * FROM `{$enrollmentsTable}` WHERE `order_id` = :oid ORDER BY `id` ASC");
        $enrollments->execute([':oid' => $orderId]);
        $enrollmentRows = $enrollments->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $payments = $pdo->prepare("SELECT * FROM `{$paymentsTable}` WHERE `order_id` = :oid ORDER BY `id` DESC");
        $payments->execute([':oid' => $orderId]);
        $paymentRows = $payments->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $logs = $this->paymentService()->getOrderStatusLogs($orderId, 50);

        $hasCreatedLog = false;
        foreach ($logs as $lg) {
            $act = strtolower((string)($lg['action_type'] ?? ''));
            if (str_contains($act, 'created') || str_contains($act, 'order_placed')) {
                $hasCreatedLog = true;
                break;
            }
        }
        if (!$hasCreatedLog) {
            $logs[] = [
                'created_at'  => (string)($order['created_at'] ?? date('Y-m-d H:i:s')),
                'action_type' => 'order_created',
                'reason'      => 'ผู้ใช้งานกดยืนยันสั่งซื้อเลขที่ ' . ($order['_coordinator_name'] !== '' ? ('(ผู้ประสานงาน: ' . $order['_coordinator_name'] . ')') : ('#' . ($order['order_no'] ?? $orderId))),
            ];
        }
        usort($logs, static fn(array $a, array $b): int => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));

        return $this->admPage('orders/detail', [
            'order'               => $order,
            'companies'           => $companies,
            'items'               => $items,
            'attendeesByItem'     => $attendeesByItem,
            'enrollments'         => $enrollmentRows,
            'payments'            => $paymentRows,
            'logs'                => $logs,
            'paymentStatusLabels' => PaymentApprovalService::PAYMENT_STATUS_LABELS,
            'statusLabels'        => PaymentApprovalService::PAYMENT_STATUS_LABELS,
        ], 'รายละเอียดคำสั่งซื้อ ' . ($order['order_no'] ?? ('#' . $orderId)), 'orders');
    }

    /**
     * POST /admin/orders/{id}/update
     */
    public function update(string $id): void
    {
        $this->admGuard();

        $orderId = ctype_digit($id) ? (int)$id : 0;
        if ($orderId <= 0) {
            H::flash('error', 'รหัสคำสั่งซื้อไม่ถูกต้อง');
            H::redirect('admin/orders');
        }

        try {
            $pdo                = $this->pdo();
            $ordersTable        = $this->table('orders');
            $orderInvoicesTable = $this->table('order_invoices');

            $orderCols = $this->getTableColumns($ordersTable);
            $invCols   = $this->getTableColumns($orderInvoicesTable);

            $stmtOld = $pdo->prepare("SELECT * FROM `{$ordersTable}` WHERE `id` = :id LIMIT 1");
            $stmtOld->execute([':id' => $orderId]);
            $oldOrder = $stmtOld->fetch(PDO::FETCH_ASSOC);
            if (!$oldOrder) {
                throw new RuntimeException('ไม่พบคำสั่งซื้อที่ต้องการแก้ไข');
            }

            $oldInv = $this->loadOrderInvoiceRow($orderId);

            $coordName  = H::in('customer_name', 200);
            $coordPhone = H::in('customer_phone', 100);
            $coordEmail = H::in('customer_email', 200);
            $otherNote  = H::in('other_note', 1000);

            $taxType    = H::in('tax_type', 30) === 'personal' ? 'personal' : 'corporate';
            $compName   = H::in('company_name', 255);
            $branch     = H::in('branch', 100);
            $taxId      = H::in('tax_id', 50);
            $poNumber   = H::in('po_number', 100);
            $province   = H::in('province', 100);
            $amphoe     = H::in('amphoe', 100);
            $district   = H::in('district', 100);
            $zipcode    = H::in('zipcode', 10);
            $streetAddr = $this->sanitizeStreetAddress(H::in('address', 1000), $district, $amphoe, $province, $zipcode);

            if ($coordName === '' && $compName === '') {
                throw new InvalidArgumentException('กรุณาระบุชื่อผู้ประสานงานหรือชื่อบริษัทอย่างน้อย 1 รายการ');
            }

            $totalSubtotal = round((float)($oldOrder['total_subtotal'] ?? 0.0), 2);
            $totalVat      = round((float)($oldOrder['total_vat'] ?? 0.0), 2);

            $whtMode = H::in('wht_mode', 20);
            $rawWhtRate = (float)str_replace(',', '', H::in('wht_rate', 30));
            $rawWhtAmt  = (float)str_replace(',', '', H::in('wht_amount', 30));

            if ($whtMode === '0') {
                $newWhtRate = 0.0;
                $newWhtAmt  = 0.0;
            } elseif ($whtMode === '3') {
                $newWhtRate = 3.0;
                $newWhtAmt  = round($totalSubtotal * 0.03, 2);
            } else {
                $newWhtAmt  = max(0.0, round($rawWhtAmt, 2));
                $newWhtRate = $totalSubtotal > 0.0
                    ? round(($newWhtAmt / $totalSubtotal) * 100, 2)
                    : max(0.0, round($rawWhtRate, 2));
            }

            $newGrandTotal = round($totalSubtotal + $totalVat - $newWhtAmt, 2);

            $isBkk = $province !== '' && (str_contains($province, 'กรุงเทพ') || mb_strtolower($province, 'UTF-8') === 'bangkok');
            $addrParts = array_filter([
                $streetAddr,
                $district !== '' ? ($isBkk ? 'แขวง' . $district : 'ต.' . $district) : '',
                $amphoe !== '' ? ($isBkk ? 'เขต' . $amphoe : 'อ.' . $amphoe) : '',
                $province !== '' ? ($isBkk ? $province : 'จ.' . $province) : '',
                $zipcode,
            ]);
            $fullAddress = implode(' ', $addrParts);
            if ($poNumber !== '') {
                $fullAddress .= "\nPO Number: " . $poNumber;
            }

            $keepInvoiceNo = trim((string)($oldOrder['invoice_no'] ?? ''));
            if ($keepInvoiceNo === '') {
                $keepInvoiceNo = 'IVE-' . trim((string)($oldOrder['order_no'] ?? $orderId));
            }

            $pdo->beginTransaction();

            $ordSet = [];
            $ordParams = [':id' => $orderId];
            $ordMap = [
                'customer_name'        => $coordName !== '' ? $coordName : null,
                'customer_email'       => $coordEmail !== '' ? $coordEmail : null,
                'customer_phone'       => $coordPhone !== '' ? $coordPhone : null,
                'special_requirements' => $otherNote !== '' ? $otherNote : null,
                'wht_rate'             => $newWhtRate,
                'total_wht'            => $newWhtAmt,
                'grand_total'          => $newGrandTotal,
                'invoice_no'           => $keepInvoiceNo,
            ];
            foreach ($ordMap as $col => $val) {
                if (isset($orderCols[$col])) {
                    $ordSet[] = "`{$col}` = :{$col}";
                    $ordParams[":{$col}"] = $val;
                }
            }
            if (isset($orderCols['updated_at'])) {
                $ordSet[] = "`updated_at` = NOW()";
            }
            if ($ordSet !== []) {
                $pdo->prepare("UPDATE `{$ordersTable}` SET " . implode(', ', $ordSet) . " WHERE `id` = :id")
                    ->execute($ordParams);
            }

            $invData = [
                'tax_type'      => $taxType,
                'tax_id'        => $taxId !== '' ? $taxId : '',
                'company_name'  => $compName !== '' ? $compName : null,
                'branch'        => $branch !== '' ? $branch : null,
                'po_number'     => $poNumber !== '' ? $poNumber : null,
                'address'       => $fullAddress,
                'province'      => $province !== '' ? $province : null,
                'amphoe'        => $amphoe !== '' ? $amphoe : null,
                'district'      => $district !== '' ? $district : null,
                'zipcode'       => $zipcode !== '' ? $zipcode : null,
                'phone'         => $coordPhone !== '' ? $coordPhone : null,
                'billing_email' => $coordEmail !== '' ? $coordEmail : null,
            ];

            if (!empty($oldInv)) {
                $invSet = [];
                $invParams = [':order_id' => $orderId];
                foreach ($invData as $col => $val) {
                    if (isset($invCols[$col])) {
                        $invSet[] = "`{$col}` = :{$col}";
                        $invParams[":{$col}"] = $val;
                    }
                }
                if (isset($invCols['updated_at'])) {
                    $invSet[] = "`updated_at` = NOW()";
                }
                if ($invSet !== []) {
                    $pdo->prepare("UPDATE `{$orderInvoicesTable}` SET " . implode(', ', $invSet) . " WHERE `order_id` = :order_id")
                        ->execute($invParams);
                }
            } else {
                $iCols = ['`order_id`'];
                $iVals = [':order_id'];
                $iParams = [':order_id' => $orderId];
                foreach ($invData as $col => $val) {
                    if (isset($invCols[$col])) {
                        $iCols[] = "`{$col}`";
                        $iVals[] = ":{$col}";
                        $iParams[":{$col}"] = $val;
                    }
                }
                $iCols[] = '`created_at`';
                $iVals[] = 'NOW()';
                $pdo->prepare(
                    "INSERT INTO `{$orderInvoicesTable}` (" . implode(', ', $iCols) . ") VALUES (" . implode(', ', $iVals) . ")"
                )->execute($iParams);
            }

            $pdo->commit();

            $this->paymentService()->insertStatusLog([
                'order_id'    => $orderId,
                'action_type' => 'order_updated_and_invoice_reissued',
                'from_status' => (string)($oldOrder['payment_status'] ?? ''),
                'to_status'   => (string)($oldOrder['payment_status'] ?? ''),
                'old_value'   => json_encode([
                    'wht_rate'    => (float)($oldOrder['wht_rate'] ?? 0),
                    'total_wht'   => (float)($oldOrder['total_wht'] ?? 0),
                    'grand_total' => (float)($oldOrder['grand_total'] ?? 0),
                    'invoice_no'  => $keepInvoiceNo,
                ], JSON_UNESCAPED_UNICODE),
                'new_value'   => json_encode([
                    'wht_rate'    => $newWhtRate,
                    'total_wht'   => $newWhtAmt,
                    'grand_total' => $newGrandTotal,
                    'invoice_no'  => $keepInvoiceNo,
                ], JSON_UNESCAPED_UNICODE),
                'reason'      => "อัปเดตข้อมูลใบกำกับภาษี (sot_order_invoices) และ WHT ({$newWhtRate}% = "
                    . number_format($newWhtAmt, 2) . " บาท, ยอดสุทธิใหม่ " . number_format($newGrandTotal, 2)
                    . " บาท) — ปริ้น/ใช้เลขที่ใบแจ้งหนี้เดิม {$keepInvoiceNo}",
                'actor_id'    => $this->resolveAdminUserId(),
                'actor_role'  => 'admin',
            ]);

            H::flash('success', "บันทึกข้อมูลเรียบร้อยแล้ว (ยอดสุทธิใหม่ " . number_format($newGrandTotal, 2) . " บาท · เลขที่ใบแจ้งหนี้เดิม {$keepInvoiceNo})");
        } catch (Throwable $e) {
            if ($this->pdo()->inTransaction()) {
                $this->pdo()->rollBack();
            }
            H::flash('error', $e->getMessage());
        }

        H::redirect('admin/orders/' . $orderId);
    }

    public function updateAttendees(string $id): void
    {
        $this->admGuard();
        $orderId = ctype_digit($id) ? (int)$id : 0;
        H::flash('info', 'ส่วนจัดการรายชื่อผู้เข้าอบรมในหน้านี้ถูกปิดการแก้ไขเพื่อคงความถูกต้องของยอดเงินและสิทธิ์ผู้เรียน');
        H::redirect($orderId > 0 ? ('admin/orders/' . $orderId) : 'admin/orders');
    }

    public function reissueInvoice(string $id): void
    {
        $this->admGuard();

        $orderId = ctype_digit($id) ? (int)$id : 0;
        if ($orderId <= 0) {
            H::flash('error', 'รหัสคำสั่งซื้อไม่ถูกต้อง');
            H::redirect('admin/orders');
        }

        try {
            $pdo         = $this->pdo();
            $ordersTable = $this->table('orders');
            $cols        = $this->getTableColumns($ordersTable);

            $stmtOld = $pdo->prepare("SELECT * FROM `{$ordersTable}` WHERE `id` = :id LIMIT 1");
            $stmtOld->execute([':id' => $orderId]);
            $order = $stmtOld->fetch(PDO::FETCH_ASSOC);
            if (!$order) {
                throw new RuntimeException('ไม่พบคำสั่งซื้อ');
            }

            $keepInvoiceNo = trim((string)($order['invoice_no'] ?? ''));
            if ($keepInvoiceNo === '') {
                $keepInvoiceNo = 'IVE-' . trim((string)($order['order_no'] ?? $orderId));
                if (isset($cols['invoice_no'])) {
                    $pdo->prepare("UPDATE `{$ordersTable}` SET `invoice_no` = :inv WHERE `id` = :id")
                        ->execute([':inv' => $keepInvoiceNo, ':id' => $orderId]);
                }
            }

            $note = H::in('reissue_reason', 300);
            $reasonText = "ปริ้น/ออกใบแจ้งหนี้ใหม่เลขเดิม: {$keepInvoiceNo}"
                . ($note !== '' ? " — {$note}" : '');

            $this->paymentService()->insertStatusLog([
                'order_id'    => $orderId,
                'action_type' => 'invoice_reissued_same_no',
                'from_status' => (string)($order['payment_status'] ?? ''),
                'to_status'   => (string)($order['payment_status'] ?? ''),
                'old_value'   => json_encode(['invoice_no' => $keepInvoiceNo], JSON_UNESCAPED_UNICODE),
                'new_value'   => json_encode(['invoice_no' => $keepInvoiceNo], JSON_UNESCAPED_UNICODE),
                'reason'      => $reasonText,
                'actor_id'    => $this->resolveAdminUserId(),
                'actor_role'  => 'admin',
            ]);

            H::flash('success', "ยืนยันปริ้น/ออกใบแจ้งหนี้เลขเดิม {$keepInvoiceNo} และบันทึกประวัติแล้ว");
        } catch (Throwable $e) {
            H::flash('error', $e->getMessage());
        }

        H::redirect('admin/orders/' . $orderId);
    }

    /**
     * POST /admin/orders/{id}/batches/{orderItemId}/unapprove
     * ยกเลิกการอนุมัติรายรุ่น (ไม่ส่งอีเมลแจ้งเตือน)
     */
    public function unapproveBatch(string $id, string $orderItemId): void
    {
        $this->admGuard();

        $oid  = ctype_digit($id) ? (int)$id : 0;
        $oiid = ctype_digit($orderItemId) ? (int)$orderItemId : 0;
        if ($oid <= 0 || $oiid <= 0) {
            H::flash('error', 'รหัสคำสั่งซื้อหรือรุ่นไม่ถูกต้อง');
            H::redirect('admin/orders');
        }

        $redirectTo = H::in('redirect_to', 100);
        $targetUrl  = $redirectTo === 'detail' ? ('admin/orders/' . $oid) : 'admin/orders';

        try {
            $note        = H::in('note', 500);
            $adminUserId = $this->resolveAdminUserId();

            $res = $this->paymentService()->unapproveBatchInOrder($oid, $oiid, max(1, $adminUserId), $note);
            $revokedCount = (int)($res['revoked_enrollments'] ?? 0);

            H::flash(
                'success',
                'ยกเลิกอนุมัติรุ่น "' . ($res['batch_label'] ?? '') . '" เรียบร้อยแล้ว'
                . " (ระงับสิทธิ์เรียน {$revokedCount} รายการ · ไม่ส่งอีเมล)"
            );
        } catch (Throwable $e) {
            H::flash('error', $e->getMessage());
        }

        H::redirect($targetUrl);
    }

    /**
     * GET /admin/order_batches
     * แสดงรายการคำสั่งซื้อแยกตามหลักสูตร - รุ่น - วันที่อบรม (ใหม่ล่าสุดอยู่บนสุด)
     */
    public function byBatch(): string
    {
        $this->admGuard();

        $pdo          = $this->pdo();
        $ordersTable  = $this->table('orders');
        $itemsTable   = $this->table('order_items');
        $attTable     = $this->table('order_attendees');
        $invTable     = $this->table('order_invoices');
        $paymentsTbl  = $this->table('order_payments');
        $coursesTbl   = $this->table('courses');
        $batchesTbl   = $this->table('course_batches');

        $q        = H::query('q');
        $payStat  = H::query('payment_status');
        $batchApp = H::query('batch_approval');
        $courseId = (int)H::query('course_id');
        $batchId  = (int)H::query('batch_id');

        $where  = [];
        $params = [];

        if ($courseId > 0) {
            $where[]              = 'oi.`course_id` = :course_id';
            $params[':course_id'] = $courseId;
        }

        if ($batchId > 0) {
            $where[]             = 'oi.`batch_id` = :batch_id';
            $params[':batch_id'] = $batchId;
        }

        if ($payStat !== '' && PaymentApprovalService::isValidStatus($payStat)) {
            $where[]               = 'o.`payment_status` = :pay_status';
            $params[':pay_status'] = $payStat;
        }

        if ($batchApp !== '') {
            $where[]               = "COALESCE(oi.`batch_approval_status`, 'pending') = :batch_app";
            $params[':batch_app'] = $batchApp;
        }

        if ($q !== '') {
            $likeQ = '%' . $q . '%';
            $qCols = [
                'o.`order_no`',
                'o.`invoice_no`',
                'o.`quotation_no`',
                'o.`customer_name`',
                'inv.`company_name`',
                'inv.`tax_id`',
                'c.`title`',
                'b.`batch_no`',
            ];
            $qParts = [];
            foreach ($qCols as $idxCol => $colExpr) {
                $phName = ':q_' . $idxCol;
                $qParts[] = "{$colExpr} LIKE {$phName}";
                $params[$phName] = $likeQ;
            }
            $where[] = '(' . implode(' OR ', $qParts) . ')';
        }

        $whereSql = $where !== [] ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "
            SELECT
                o.`id`,
                o.`order_no`,
                o.`invoice_no`,
                o.`quotation_no`,
                o.`customer_name`,
                o.`customer_email`,
                o.`customer_phone`,
                o.`special_requirements`,
                o.`payment_status`,
                o.`grand_total`,
                o.`created_at`,
                inv.`tax_type` AS `inv_tax_type`,
                inv.`company_name` AS `inv_company_name`,
                inv.`branch` AS `inv_branch`,
                inv.`tax_id` AS `inv_tax_id`,
                inv.`po_number` AS `inv_po_number`,
                inv.`address` AS `inv_address`,
                inv.`province` AS `inv_province`,
                inv.`amphoe` AS `inv_amphoe`,
                inv.`district` AS `inv_district`,
                inv.`zipcode` AS `inv_zipcode`,
                oi.`id` AS `order_item_id`,
                oi.`course_id`,
                oi.`batch_id`,
                oi.`quantity`,
                oi.`unit_price`,
                oi.`subtotal` AS `line_total`,
                COALESCE(oi.`batch_approval_status`, 'pending') AS `batch_approval_status`,
                COALESCE(c.`title`, CONCAT('หลักสูตร #', oi.`course_id`)) AS `course_title`,
                COALESCE(b.`batch_no`, 'ไม่ระบุรุ่น') AS `batch_no`,
                b.`date_start`,
                b.`date_text`,
                b.`time_text`,
                b.`status` AS `batch_status`,
                (
                    SELECT COUNT(*)
                    FROM `{$attTable}` oa
                    WHERE oa.`order_item_id` = oi.`id`
                ) AS `real_attendee_count`
            FROM `{$itemsTable}` oi
            INNER JOIN `{$ordersTable}` o ON o.`id` = oi.`order_id`
            LEFT JOIN `{$invTable}` inv ON inv.`order_id` = o.`id`
            LEFT JOIN `{$coursesTbl}` c ON c.`id` = oi.`course_id`
            LEFT JOIN `{$batchesTbl}` b ON b.`id` = oi.`batch_id`
            {$whereSql}
            ORDER BY
                (CASE WHEN LOWER(TRIM(COALESCE(b.`status`, ''))) IN ('closed', 'cancelled', 'canceled', 'inactive', 'ended') THEN 1 ELSE 0 END) ASC,
                (CASE WHEN LOWER(TRIM(COALESCE(b.`status`, ''))) IN ('closed', 'cancelled', 'canceled', 'inactive', 'ended') THEN 0 ELSE 1 END) DESC,
                (b.`date_start` IS NULL OR b.`date_start` = '0000-00-00') ASC,
                CASE WHEN LOWER(TRIM(COALESCE(b.`status`, ''))) IN ('closed', 'cancelled', 'canceled', 'inactive', 'ended') THEN NULL ELSE b.`date_start` END ASC,
                CASE WHEN LOWER(TRIM(COALESCE(b.`status`, ''))) IN ('closed', 'cancelled', 'canceled', 'inactive', 'ended') THEN b.`date_start` ELSE NULL END DESC,
                b.`id` DESC,
                o.`created_at` DESC,
                o.`id` DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $orderSlipsMap = [];
        $orderIds = array_values(array_unique(array_filter(
            array_map(static fn(array $r): int => (int)($r['id'] ?? 0), $rows),
            static fn(int $v): bool => $v > 0
        )));

        if ($orderIds !== []) {
            $inPh = implode(',', array_fill(0, count($orderIds), '?'));
            $stmtSlips = $pdo->prepare("
                SELECT `id`, `order_id`, `slip_path`
                FROM `{$paymentsTbl}`
                WHERE `order_id` IN ({$inPh})
                ORDER BY `id` ASC
            ");
            $stmtSlips->execute($orderIds);
            foreach ($stmtSlips->fetchAll(PDO::FETCH_ASSOC) ?: [] as $sRow) {
                $oidKey = (int)($sRow['order_id'] ?? 0);
                $sPath  = trim((string)($sRow['slip_path'] ?? ''));
                if ($oidKey > 0 && $sPath !== '') {
                    $orderSlipsMap[$oidKey][] = $sPath;
                }
            }
        }

        $groupsMap = [];
        foreach ($rows as $r) {
            $cid = (int)($r['course_id'] ?? 0);
            $bid = (int)($r['batch_id'] ?? 0);
            $key = $cid . ':' . $bid;

            if (!isset($groupsMap[$key])) {
                $groupsMap[$key] = [
                    'course_id'    => $cid,
                    'batch_id'     => $bid,
                    'course_title' => (string)($r['course_title'] ?? 'ไม่ระบุหลักสูตร'),
                    'batch_no'     => (string)($r['batch_no'] ?? '-'),
                    'date_start'   => $r['date_start'] ?? null,
                    'date_text'    => (string)($r['date_text'] ?? ''),
                    'time_text'    => (string)($r['time_text'] ?? ''),
                    'batch_status' => (string)($r['batch_status'] ?? ''),
                    'orders'       => [],
                ];
            }

            $realAtt = (int)($r['real_attendee_count'] ?? 0);
            $qty     = max(1, (int)($r['quantity'] ?? 1));
            $attCnt  = $realAtt > 0 ? $realAtt : $qty;

            $lineTotal = (float)($r['line_total'] ?? 0);
            if ($lineTotal <= 0 && (float)($r['unit_price'] ?? 0) > 0) {
                $lineTotal = (float)$r['unit_price'] * $attCnt;
            }

            $province = trim((string)($r['inv_province'] ?? ''));
            $amphoe   = trim((string)($r['inv_amphoe'] ?? ''));
            $district = trim((string)($r['inv_district'] ?? ''));
            $zipcode  = trim((string)($r['inv_zipcode'] ?? ''));
            $street   = $this->sanitizeStreetAddress(
                (string)($r['inv_address'] ?? ''),
                $district,
                $amphoe,
                $province,
                $zipcode
            );

            $isBkk = $province !== '' && (str_contains($province, 'กรุงเทพ') || mb_strtolower($province, 'UTF-8') === 'bangkok');
            $addrParts = array_filter([
                $street,
                $district !== '' ? ($isBkk ? ('แขวง' . $district) : ('ต.' . $district)) : '',
                $amphoe !== '' ? ($isBkk ? ('เขต' . $amphoe) : ('อ.' . $amphoe)) : '',
                $province !== '' ? ($isBkk ? $province : ('จ.' . $province)) : '',
                $zipcode,
            ]);

            $oidCur = (int)($r['id'] ?? 0);
            $r['attendee_count']   = $attCnt;
            $r['line_total']       = $lineTotal;
            $r['inv_company_name'] = trim((string)($r['inv_company_name'] ?? ''));
            $r['inv_branch']       = trim((string)($r['inv_branch'] ?? ''));
            $r['inv_full_address'] = implode(' ', $addrParts);
            $r['inv_tax_id']       = trim((string)($r['inv_tax_id'] ?? ''));
            $r['other_note']       = trim((string)($r['special_requirements'] ?? ''));
            $r['slips']            = $orderSlipsMap[$oidCur] ?? [];

            $groupsMap[$key]['orders'][] = $r;
        }

        return $this->admPage('orders/by_batch', [
            'groups'              => array_values($groupsMap),
            'filters'             => [
                'q'              => $q,
                'payment_status' => $payStat,
                'batch_approval' => $batchApp,
                'course_id'      => $courseId,
                'batch_id'       => $batchId,
            ],
            'paymentStatusLabels' => PaymentApprovalService::PAYMENT_STATUS_LABELS,
        ], 'คำสั่งซื้อแยกตามหลักสูตร-รุ่นวันที่อบรม', 'order_batches');
    }

    /**
     * GET /admin/slips/view?path=...
     * ตรวจสอบสิทธิ์แอดมินและสตรีมไฟล์สลิปจากโฟลเดอร์ storage/uploads
     */
    public function viewSlip(): void
    {
        $this->admGuard();

        $rawPath = trim((string)H::query('path'));
        $clean   = str_replace('\\', '/', $rawPath);
        $clean   = ltrim($clean, '/');

        if ($clean === '') {
            http_response_code(400);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Missing slip path';
            exit;
        }

        // ตัด base folder ของแอป (เช่น SOT/) หากติดมาใน slip_path
        $clean = preg_replace('#^SOT/#i', '', $clean) ?? $clean;

        // หา Project Root จริง (โฟลเดอร์ที่มีทั้ง storage และ modules)
        $projectRoot = null;
        $probe = __DIR__;
        for ($i = 0; $i < 8; $i++) {
            $probe = dirname($probe);
            if (is_dir($probe . '/storage') && is_dir($probe . '/modules')) {
                $projectRoot = realpath($probe) ?: $probe;
                break;
            }
        }
        if ($projectRoot === null && defined('BASE_PATH')) {
            $projectRoot = realpath((string)BASE_PATH) ?: (string)BASE_PATH;
        }
        if ($projectRoot === null) {
            $projectRoot = realpath(dirname(__DIR__, 4)) ?: dirname(__DIR__, 4);
        }

        $baseDir = realpath($projectRoot . '/storage/uploads') ?: ($projectRoot . '/storage/uploads');

        // สร้างรายการ candidate path ที่เป็นไปได้
        $candidates = [];
        if (str_starts_with($clean, 'storage/')) {
            $candidates[] = $projectRoot . '/' . $clean;
        } elseif (str_starts_with($clean, 'uploads/')) {
            $candidates[] = $projectRoot . '/storage/' . $clean;
            $candidates[] = $projectRoot . '/' . $clean;
        } else {
            $candidates[] = $projectRoot . '/storage/uploads/' . $clean;
            $candidates[] = $projectRoot . '/storage/' . $clean;
            $candidates[] = $projectRoot . '/' . $clean;
        }

        $resolvedFile = null;
        $resolvedBase = realpath($baseDir);

        foreach ($candidates as $cand) {
            $realCand = realpath($cand);
            if ($realCand === false || !is_file($realCand) || !is_readable($realCand)) {
                continue;
            }
            // ป้องกันการออกนอกโฟลเดอร์ storage/uploads
            if ($resolvedBase !== false) {
                $normBase = rtrim(str_replace('\\', '/', $resolvedBase), '/') . '/';
                $normFile = str_replace('\\', '/', $realCand);
                if (!str_starts_with($normFile, $normBase)) {
                    continue;
                }
            }
            $resolvedFile = $realCand;
            break;
        }

        if ($resolvedFile === null) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Slip file not found: ' . $clean;
            exit;
        }

        $ext = strtolower(pathinfo($resolvedFile, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'pdf'         => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'gif'         => 'image/gif',
            'webp'        => 'image/webp',
            default       => (function_exists('mime_content_type')
                ? (string)(mime_content_type($resolvedFile) ?: 'application/octet-stream')
                : 'application/octet-stream'),
        };

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($resolvedFile)) ?: 'slip';

        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . $safeName . '"');
        header('Content-Length: ' . (string)filesize($resolvedFile));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=300');

        readfile($resolvedFile);
        exit;
    }
}