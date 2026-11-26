<?php
/**
 * ============================================================================
 * Project Name : SIAM Online Training (SOT LMS)
 * File Name    : MyPortalController.php
 * Module       : Auth Portal (My Orders, My Courses & Documents)
 * Description  : จัดการหน้า "คำสั่งซื้อของฉัน" (ดูได้ทุกสถานะแม้ยังไม่อนุมัติ)
 *                หน้า "คอร์สของฉัน" (เฉพาะ Order/รุ่น ที่อนุมัติแล้ว)
 *                ดาวน์โหลดใบเสนอราคา (QUO) / ใบแจ้งค่าบริการ (IVE)
 *                และหน้าแก้ไขข้อมูลส่วนตัว / ข้อมูลบริษัท / Popup รายชื่อผู้เรียน
 *                (แก้ชื่อและตำแหน่งได้เฉพาะผู้เข้าอบรมคนอื่นที่ยังไม่มี user เข้าระบบ
 *                พร้อมตรวจสอบชื่อ-นามสกุลซ้ำ)
 * ============================================================================
 */

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use App\Core\Controller;
use PDO;
use ReflectionObject;
use RuntimeException;
use Throwable;

class MyPortalController extends Controller
{
    private ?string $tablePrefix = null;

    private function resolvePdo($db): PDO
    {
        if ($db instanceof PDO) {
            return $db;
        }

        if (is_object($db)) {
            if (method_exists($db, 'pdo')) {
                $pdo = $db->pdo();
                if ($pdo instanceof PDO) {
                    return $pdo;
                }
            }
            if (method_exists($db, 'getPdo')) {
                $pdo = $db->getPdo();
                if ($pdo instanceof PDO) {
                    return $pdo;
                }
            }
            if (isset($db->pdo) && $db->pdo instanceof PDO) {
                return $db->pdo;
            }

            $ref = new ReflectionObject($db);
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

    /**
     * ค้นหา Database Prefix ของระบบ SOT แบบไดนามิก
     */
    private function resolveTablePrefix(PDO $pdo): string
    {
        if ($this->tablePrefix !== null) {
            return $this->tablePrefix;
        }

        foreach (['DB_PREFIX', 'TABLE_PREFIX', 'SOT_DB_PREFIX'] as $constName) {
            if (defined($constName)) {
                $val = (string)constant($constName);
                if ($val !== '') {
                    return $this->tablePrefix = $val;
                }
            }
        }

        if (!empty($GLOBALS['db_prefix']) && is_string($GLOBALS['db_prefix'])) {
            return $this->tablePrefix = $GLOBALS['db_prefix'];
        }
        if (!empty($GLOBALS['config']['db']['prefix']) && is_string($GLOBALS['config']['db']['prefix'])) {
            return $this->tablePrefix = $GLOBALS['config']['db']['prefix'];
        }
        if (!empty($GLOBALS['config']['prefix']) && is_string($GLOBALS['config']['prefix'])) {
            return $this->tablePrefix = $GLOBALS['config']['prefix'];
        }

        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '%\\_orders'");
            if ($stmt) {
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
                foreach ($tables as $tbl) {
                    $tblName = (string)$tbl;
                    if (preg_match('/^(.*_)orders$/', $tblName, $m)) {
                        return $this->tablePrefix = $m[1];
                    }
                }
            }
        } catch (Throwable $e) {
            // ข้ามหากไม่สามารถคิวรี SHOW TABLES ได้
        }

        return $this->tablePrefix = 'sot_';
    }

    /**
     * คืนชื่อตารางเต็มพร้อม prefix เช่น 'orders' -> 'sot_orders'
     */
    private function table(PDO $pdo, string $baseName): string
    {
        $cleanBase = ltrim($baseName, '_');
        $prefix = $this->resolveTablePrefix($pdo);

        if ($prefix !== '' && str_starts_with($cleanBase, $prefix)) {
            return $cleanBase;
        }

        return $prefix . $cleanBase;
    }

    /**
     * ตรวจสอบคอลัมน์ที่มีอยู่จริงในตาราง เพื่อสร้าง SQL ที่ไม่พังแม้ชื่อคอลัมน์ต่างกัน
     *
     * @return array<string, bool>
     */
    private function getTableColumns(PDO $pdo, string $baseOrFullTableName): array
    {
        static $cache = [];
        $tableName = $this->table($pdo, $baseOrFullTableName);

        if (isset($cache[$tableName])) {
            return $cache[$tableName];
        }

        $columns = [];
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM `{$tableName}`");
            if ($stmt) {
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    if (!empty($row['Field'])) {
                        $columns[(string)$row['Field']] = true;
                    }
                }
            }
        } catch (Throwable $e) {
            $columns = [];
        }

        $cache[$tableName] = $columns;
        return $columns;
    }

    /**
     * โหลดรายการสินค้า/หลักสูตรของคำสั่งซื้อ พร้อมชื่อหลักสูตรเต็มและข้อมูลวันอบรม/ราคาต่อหน่วย
     *
     * @param int[] $orderIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function loadOrderItemsWithCourseInfo(PDO $pdo, array $orderIds): array
    {
        if (empty($orderIds)) {
            return [];
        }

        $tOrderItems     = $this->table($pdo, 'order_items');
        $tCourses        = $this->table($pdo, 'courses');
        $tCourseBatches  = $this->table($pdo, 'course_batches');
        $tOrderAttendees = $this->table($pdo, 'order_attendees');
        $tParticipants   = $this->table($pdo, 'participants');

        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $itemCols     = $this->getTableColumns($pdo, 'order_items');
        $courseCols   = $this->getTableColumns($pdo, 'courses');
        $batchCols    = $this->getTableColumns($pdo, 'course_batches');

        $joins = [];
        $selectExtra = [];

        if (isset($itemCols['batch_approval_status'])) {
            $selectExtra[] = "oi.`batch_approval_status`";
        } else {
            $selectExtra[] = "NULL AS `batch_approval_status`";
        }

        if (isset($itemCols['batch_approved_at'])) {
            $selectExtra[] = "oi.`batch_approved_at`";
        }

        if (!empty($courseCols)) {
            $joins[] = "LEFT JOIN `{$tCourses}` c ON c.`id` = oi.`course_id`";
            $titleCandidates = array_values(array_filter(
                ['title', 'course_title', 'name', 'course_name'],
                static fn(string $col): bool => isset($courseCols[$col])
            ));
            if (!empty($titleCandidates)) {
                $coalesceArgs = array_map(static fn(string $col): string => "NULLIF(TRIM(c.`{$col}`), '')", $titleCandidates);
                $selectExtra[] = "COALESCE(" . implode(', ', $coalesceArgs) . ") AS `course_title`";
            } else {
                $selectExtra[] = "NULL AS `course_title`";
            }

            if (isset($courseCols['date_text'])) {
                $selectExtra[] = "c.`date_text` AS `course_date_text`";
            }
        } else {
            $selectExtra[] = "NULL AS `course_title`";
        }

        if (!empty($batchCols)) {
            $joins[] = "LEFT JOIN `{$tCourseBatches}` b ON b.`id` = oi.`batch_id`";

            $batchCodeCols = array_values(array_filter(
                ['batch_code', 'code', 'batch_no'],
                static fn(string $col): bool => isset($batchCols[$col])
            ));
            $selectExtra[] = !empty($batchCodeCols)
                ? "COALESCE(" . implode(', ', array_map(static fn(string $col): string => "b.`{$col}`", $batchCodeCols)) . ") AS `batch_code`"
                : "NULL AS `batch_code`";

            $batchNameCols = array_values(array_filter(
                ['batch_name', 'name', 'title'],
                static fn(string $col): bool => isset($batchCols[$col])
            ));
            $selectExtra[] = !empty($batchNameCols)
                ? "COALESCE(" . implode(', ', array_map(static fn(string $col): string => "b.`{$col}`", $batchNameCols)) . ") AS `batch_name`"
                : "NULL AS `batch_name`";

            foreach (['date_text', 'start_date', 'end_date', 'training_date', 'session_date', 'event_date'] as $dateCol) {
                if (isset($batchCols[$dateCol])) {
                    $selectExtra[] = "b.`{$dateCol}` AS `{$dateCol}`";
                }
            }
        } else {
            $selectExtra[] = "NULL AS `batch_code`";
            $selectExtra[] = "NULL AS `batch_name`";
        }

        $extraSql = !empty($selectExtra) ? ",\n                    " . implode(",\n                    ", $selectExtra) : '';
        $joinSql  = !empty($joins) ? "\n                " . implode("\n                ", $joins) : '';

        $stmtItems = $pdo->prepare("
            SELECT
                oi.`id`,
                oi.`order_id`,
                oi.`course_id`,
                oi.`batch_id`,
                oi.`rate_tier`,
                oi.`unit_price`,
                oi.`quantity`,
                oi.`subtotal`{$extraSql}
            FROM `{$tOrderItems}` oi{$joinSql}
            WHERE oi.`order_id` IN ($placeholders)
            ORDER BY oi.`id` ASC
        ");
        $stmtItems->execute($orderIds);
        $rawItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $itemIds = [];
        $itemsByOrder = [];
        foreach ($rawItems as $it) {
            $iid = (int)($it['id'] ?? 0);
            if ($iid > 0) {
                $itemIds[] = $iid;
            }
            if (empty($it['date_text']) && !empty($it['course_date_text'])) {
                $it['date_text'] = $it['course_date_text'];
            }
            $it['attendees'] = [];
            $itemsByOrder[(int)$it['order_id']][$iid] = $it;
        }

        if (!empty($itemIds)) {
            $attPlaceholders = implode(',', array_fill(0, count($itemIds), '?'));
            $stmtAtt = $pdo->prepare("
                SELECT
                    oa.`id`,
                    oa.`order_item_id`,
                    oa.`participant_id`,
                    oa.`user_id`,
                    oa.`prefix`,
                    oa.`first_name`,
                    oa.`last_name`,
                    oa.`email`,
                    oa.`phone`,
                    oa.`is_self_enrollment`,
                    oa.`attendance_status`,
                    oa.`certificate_status`,
                    p.`pin_code`
                FROM `{$tOrderAttendees}` oa
                LEFT JOIN `{$tParticipants}` p ON p.`id` = oa.`participant_id`
                WHERE oa.`order_item_id` IN ($attPlaceholders)
                ORDER BY oa.`id` ASC
            ");
            $stmtAtt->execute($itemIds);
            $rawAtts = $stmtAtt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($rawAtts as $att) {
                $iid = (int)($att['order_item_id'] ?? 0);
                foreach ($itemsByOrder as &$orderItemsMap) {
                    if (isset($orderItemsMap[$iid])) {
                        $orderItemsMap[$iid]['attendees'][] = $att;
                    }
                }
                unset($orderItemsMap);
            }
        }

        $result = [];
        foreach ($itemsByOrder as $oid => $map) {
            $result[(int)$oid] = array_values($map);
        }

        return $result;
    }

    public function myOrders(): string
    {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            $_SESSION['flash_error'] = 'กรุณาเข้าสู่ระบบก่อนดูรายการคำสั่งซื้อค่ะ';
            redirect('/login');
        }

        $pdo = $this->resolvePdo($this->db());

        $tOrders         = $this->table($pdo, 'orders');
        $tOrderInvoices  = $this->table($pdo, 'order_invoices');
        $tOrderItems     = $this->table($pdo, 'order_items');
        $tOrderAttendees = $this->table($pdo, 'order_attendees');

        $stmt = $pdo->prepare("
            SELECT
                o.`id`,
                o.`order_no`,
                o.`invoice_no`,
                o.`quotation_no`,
                o.`customer_name`,
                o.`customer_email`,
                o.`customer_phone`,
                o.`send_attendee_direct_email`,
                o.`total_subtotal`,
                o.`total_vat`,
                o.`total_wht`,
                o.`grand_total`,
                o.`special_requirements`,
                o.`payment_status`,
                o.`created_at`,
                o.`paid_at`,
                o.`approved_at`,
                inv.`company_name` AS invoice_company_name,
                inv.`tax_type` AS invoice_tax_type,
                (
                    SELECT COUNT(oa.`id`)
                    FROM `{$tOrderItems}` oi
                    LEFT JOIN `{$tOrderAttendees}` oa ON oa.`order_item_id` = oi.`id`
                    WHERE oi.`order_id` = o.`id`
                ) AS attendee_count
            FROM `{$tOrders}` o
            LEFT JOIN `{$tOrderInvoices}` inv ON inv.`order_id` = o.`id`
            WHERE o.`user_id` = :user_id
            ORDER BY o.`id` DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $orderIds = [];
        foreach ($orders as &$ord) {
            $ord['quotation_no'] = trim((string)($ord['quotation_no'] ?? ''));
            $ord['items'] = [];
            $ord['_is_fully_approved'] = false;
            $oid = (int)($ord['id'] ?? 0);
            if ($oid > 0) {
                $orderIds[] = $oid;
            }
        }
        unset($ord);

        if (!empty($orderIds)) {
            $itemsByOrder = $this->loadOrderItemsWithCourseInfo($pdo, $orderIds);
            foreach ($orders as &$ord) {
                $oid = (int)($ord['id'] ?? 0);
                $ordItems = $itemsByOrder[$oid] ?? [];
                $ord['items'] = $ordItems;

                $rawSt = strtolower(trim((string)($ord['payment_status'] ?? 'pending')));
                $allApproved = ($rawSt === 'paid') && !empty($ordItems);
                if ($allApproved) {
                    foreach ($ordItems as $itRow) {
                        if (strtolower(trim((string)($itRow['batch_approval_status'] ?? ''))) !== 'approved') {
                            $allApproved = false;
                            break;
                        }
                    }
                }
                $ord['_is_fully_approved'] = $allApproved;
            }
            unset($ord);
        }

        $statusLabels = [
            'pending'               => ['label' => 'รอการชำระ', 'bg' => '#fef9c3', 'color' => '#854d0e'],
            'awaiting_verification'  => ['label' => 'รอตรวจสอบ', 'bg' => '#e0f2fe', 'color' => '#075985'],
            'pending_approval'      => ['label' => 'รอการอนุมัติ', 'bg' => '#ffedd5', 'color' => '#9a3412'],
            'paid'                  => ['label' => 'ชำระเงินแล้ว', 'bg' => '#dcfce7', 'color' => '#166534'],
            'cancelled'             => ['label' => 'ยกเลิก', 'bg' => '#fee2e2', 'color' => '#991b1b'],
            'refunded'              => ['label' => 'คืนเงินแล้ว', 'bg' => '#f3e8ff', 'color' => '#6b21a8'],
        ];

        return view('Auth::my-orders', [
            'orders'       => $orders,
            'statusLabels' => $statusLabels,
            'breadcrumbs'  => ['หน้าแรก' => '/', 'แดชบอร์ด' => '/dashboard', 'คำสั่งซื้อของฉัน' => ''],
        ]);
    }

    public function downloadDocument(): void
    {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            $_SESSION['flash_error'] = 'กรุณาเข้าสู่ระบบก่อนดาวน์โหลดเอกสารค่ะ';
            redirect('/login');
        }

        $orderId = (int)($_GET['order_id'] ?? 0);
        $docType = strtolower(trim((string)($_GET['type'] ?? 'quo'))); // 'quo' | 'ive'
        $format  = strtolower(trim((string)($_GET['format'] ?? 'preview'))); // 'preview' | 'download'

        if ($orderId <= 0 || !in_array($docType, ['quo', 'ive'], true)) {
            $_SESSION['flash_error'] = 'ไม่พบข้อมูลเอกสารที่ต้องการดาวน์โหลด';
            redirect('/my-orders');
        }

        $pdo = $this->resolvePdo($this->db());
        $tOrders        = $this->table($pdo, 'orders');
        $tOrderInvoices = $this->table($pdo, 'order_invoices');

        $stmt = $pdo->prepare("
            SELECT
                o.*,
                inv.`tax_type`,
                inv.`tax_id`,
                inv.`company_name` AS inv_company_name,
                inv.`branch` AS inv_branch,
                inv.`address` AS inv_address
            FROM `{$tOrders}` o
            LEFT JOIN `{$tOrderInvoices}` inv ON inv.`order_id` = o.`id`
            WHERE o.`id` = :order_id AND o.`user_id` = :user_id
            LIMIT 1
        ");
        $stmt->execute([
            ':order_id' => $orderId,
            ':user_id'  => $userId,
        ]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $_SESSION['flash_error'] = 'ไม่มีสิทธิ์เข้าถึงคำสั่งซื้อนี้';
            redirect('/my-orders');
        }

        $quoNo = trim((string)($order['quotation_no'] ?? ''));
        $iveNo = trim((string)($order['invoice_no'] ?? ''));

        if ($docType === 'quo' && $quoNo === '') {
            $_SESSION['flash_error'] = 'ยังไม่มีเลขที่ใบเสนอราคาในระบบสำหรับคำสั่งซื้อนี้';
            redirect('/my-orders');
        }

        $itemsByOrder = $this->loadOrderItemsWithCourseInfo($pdo, [$orderId]);
        $items = $itemsByOrder[$orderId] ?? [];

        if (!class_exists(\Modules\Cart\Services\DocumentService::class)) {
            $serviceFile = dirname(__DIR__, 2) . '/Cart/Services/DocumentService.php';
            if (is_file($serviceFile)) {
                require_once $serviceFile;
            }
        }

        $docService = new \Modules\Cart\Services\DocumentService($pdo);
        $dest = ($format === 'download') ? 'D' : 'I';

        if (ob_get_length()) {
            ob_end_clean();
        }

        $docService->renderPdf($order, $items, $docType, $dest);
        exit;
    }

    public function myCourses(): string
    {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            $_SESSION['flash_error'] = 'กรุณาเข้าสู่ระบบก่อนดูคอร์สเรียนของฉันค่ะ';
            redirect('/login');
        }

        $tab = strtolower(trim((string)($_GET['role'] ?? 'learner')));
        if (!in_array($tab, ['learner', 'coordinator'], true)) {
            $tab = 'learner';
        }

        $pdo = $this->resolvePdo($this->db());
        $tEnrollments  = $this->table($pdo, 'enrollments');
        $tOrders       = $this->table($pdo, 'orders');
        $tOrderItems   = $this->table($pdo, 'order_items');
        $tParticipants = $this->table($pdo, 'participants');
        $tCourses      = $this->table($pdo, 'courses');
        $tBatches      = $this->table($pdo, 'course_batches');

        $enrCols    = $this->getTableColumns($pdo, 'enrollments');
        $itemCols   = $this->getTableColumns($pdo, 'order_items');
        $courseCols = $this->getTableColumns($pdo, 'courses');
        $batchCols  = $this->getTableColumns($pdo, 'course_batches');

        $joins = [
            "INNER JOIN `{$tOrders}` o ON o.`id` = e.`order_id`",
            "LEFT JOIN `{$tParticipants}` p ON p.`id` = e.`participant_id`",
        ];

        $selectExtra = [];

        if (!empty($itemCols) && isset($itemCols['order_id'], $itemCols['course_id'])) {
            $itemJoinConds = ["oi.`order_id` = e.`order_id`", "oi.`course_id` = e.`course_id`"];
            if (isset($itemCols['batch_id']) && isset($enrCols['batch_id'])) {
                $itemJoinConds[] = "oi.`batch_id` = e.`batch_id`";
            }
            $joins[] = "LEFT JOIN `{$tOrderItems}` oi ON " . implode(' AND ', $itemJoinConds);
            $selectExtra[] = isset($itemCols['batch_approval_status']) ? "oi.`batch_approval_status`" : "NULL AS `batch_approval_status`";
            $selectExtra[] = isset($itemCols['batch_approved_at']) ? "oi.`batch_approved_at`" : "NULL AS `batch_approved_at`";
        } else {
            $selectExtra[] = "NULL AS `batch_approval_status`";
            $selectExtra[] = "NULL AS `batch_approved_at`";
        }

        if (!empty($courseCols)) {
            $joins[] = "LEFT JOIN `{$tCourses}` c ON c.`id` = e.`course_id`";
            $titleCandidates = array_values(array_filter(
                ['title', 'course_title', 'name', 'course_name'],
                static fn(string $col): bool => isset($courseCols[$col])
            ));
            $selectExtra[] = !empty($titleCandidates)
                ? "COALESCE(" . implode(', ', array_map(static fn(string $col): string => "NULLIF(TRIM(c.`{$col}`), '')", $titleCandidates)) . ") AS `course_title`"
                : "NULL AS `course_title`";
        } else {
            $selectExtra[] = "NULL AS `course_title`";
        }

        if (!empty($batchCols) && isset($enrCols['batch_id'])) {
            $joins[] = "LEFT JOIN `{$tBatches}` b ON b.`id` = e.`batch_id`";
            $codeCandidates = array_values(array_filter(
                ['batch_code', 'code', 'batch_no'],
                static fn(string $col): bool => isset($batchCols[$col])
            ));
            $selectExtra[] = !empty($codeCandidates)
                ? "COALESCE(" . implode(', ', array_map(static fn(string $col): string => "b.`{$col}`", $codeCandidates)) . ") AS `batch_code`"
                : "NULL AS `batch_code`";

            $dateCandidates = array_values(array_filter(
                ['date_text', 'start_date', 'training_date', 'session_date'],
                static fn(string $col): bool => isset($batchCols[$col])
            ));
            $selectExtra[] = !empty($dateCandidates)
                ? "COALESCE(" . implode(', ', array_map(static fn(string $col): string => "b.`{$col}`", $dateCandidates)) . ") AS `batch_date_text`"
                : "NULL AS `batch_date_text`";
        } else {
            $selectExtra[] = "NULL AS `batch_code`";
            $selectExtra[] = "NULL AS `batch_date_text`";
        }

        $strictApprovalConds = [
            "o.`payment_status` = 'paid'",
            "o.`payment_status` NOT IN ('cancelled', 'refunded')",
        ];
        if (isset($enrCols['payment_status'])) {
            $strictApprovalConds[] = "e.`payment_status` = 'paid'";
        }
        if (isset($enrCols['status'])) {
            $strictApprovalConds[] = "COALESCE(e.`status`, 'active') NOT IN ('cancelled', 'refunded', 'pending')";
        }
        if (isset($itemCols['batch_approval_status'])) {
            $strictApprovalConds[] = "oi.`batch_approval_status` = 'approved'";
        }
        $approvalSql = implode(' AND ', $strictApprovalConds);

        $baseSelect = "
            SELECT
                e.`id` AS enrollment_id,
                e.`order_id`,
                e.`course_id`,
                e.`batch_id`,
                e.`learner_user_id`,
                e.`payer_user_id`,
                e.`attendance_status`,
                e.`participation_status`,
                e.`created_at` AS enrolled_at,
                o.`order_no`,
                o.`invoice_no`,
                o.`quotation_no`,
                o.`payment_status`,
                o.`approved_at`,
                p.`first_name` AS participant_first_name,
                p.`last_name` AS participant_last_name,
                p.`pin_code` AS participant_pin_code,
                " . implode(",\n                ", $selectExtra) . "
            FROM `{$tEnrollments}` e
            " . implode("\n            ", $joins) . "
        ";

        $learnerConds = ["p.`learner_user_id` = :uid_part_learner"];
        $learnerParams = [':uid_part_learner' => $userId];
        if (isset($enrCols['learner_user_id'])) {
            $learnerConds[] = "e.`learner_user_id` = :uid_enr_learner";
            $learnerParams[':uid_enr_learner'] = $userId;
        }
        $stmtLearner = $pdo->prepare(
            $baseSelect . " WHERE ({$approvalSql}) AND (" . implode(' OR ', $learnerConds) . ") ORDER BY e.`id` DESC"
        );
        $stmtLearner->execute($learnerParams);
        $learnerCourses = $stmtLearner->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $coordConds = ["o.`user_id` = :uid_order_owner"];
        $coordParams = [':uid_order_owner' => $userId];
        if (isset($enrCols['payer_user_id'])) {
            $coordConds[] = "e.`payer_user_id` = :uid_payer_owner";
            $coordParams[':uid_payer_owner'] = $userId;
        }
        $stmtCoord = $pdo->prepare(
            $baseSelect . " WHERE ({$approvalSql}) AND (" . implode(' OR ', $coordConds) . ") ORDER BY e.`id` DESC"
        );
        $stmtCoord->execute($coordParams);
        $coordinatorCourses = $stmtCoord->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $courses = ($tab === 'coordinator') ? $coordinatorCourses : $learnerCourses;

        return view('Auth::my-courses', [
            'courses'            => $courses,
            'learnerCourses'     => $learnerCourses,
            'coordinatorCourses' => $coordinatorCourses,
            'activeRoleTab'      => $tab,
            'breadcrumbs'        => ['หน้าแรก' => '/', 'แดชบอร์ด' => '/dashboard', 'คอร์สของฉัน' => ''],
        ]);
    }

    private function loadCompanyParticipants(PDO $pdo, int $companyId, int $userId, string $userEmail = ''): array
    {
        if ($companyId <= 0 || $userId <= 0) {
            return [];
        }

        $tParticipants = $this->table($pdo, 'participants');
        $tCompanyUsers = $this->table($pdo, 'company_users');

        $stmt = $pdo->prepare("
            SELECT
                p.`id`,
                p.`company_id`,
                p.`user_id`,
                p.`learner_user_id`,
                p.`prefix_name`,
                p.`first_name`,
                p.`last_name`,
                p.`email`,
                p.`phone`,
                p.`position`,
                p.`department`,
                p.`pin_code`
            FROM `{$tParticipants}` p
            INNER JOIN `{$tCompanyUsers}` cu
                ON cu.`company_id` = p.`company_id`
                AND cu.`user_id` = :user_id
            WHERE p.`company_id` = :company_id
            ORDER BY p.`id` ASC
        ");
        $stmt->execute([
            ':user_id'    => $userId,
            ':company_id' => $companyId,
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $normUserEmail = strtolower(trim($userEmail));

        foreach ($rows as &$r) {
            $learnerUid = (int)($r['learner_user_id'] ?? 0);
            $partEmail  = strtolower(trim((string)($r['email'] ?? '')));

            $isSelf = ($learnerUid === $userId)
                || ($learnerUid === 0 && $normUserEmail !== '' && $partEmail !== '' && $partEmail === $normUserEmail);

            $hasLearnerAccount = ($learnerUid > 0);
            $canEditName = (!$isSelf && !$hasLearnerAccount);

            $r['is_self'] = $isSelf;
            $r['has_learner_account'] = $hasLearnerAccount;
            $r['can_edit_name'] = $canEditName;
        }
        unset($r);

        return $rows;
    }

    private function syncOwnParticipantProfile(
        PDO $pdo,
        int $userId,
        string $oldEmail,
        string $prefix,
        string $firstname,
        string $lastname,
        string $email,
        string $phone,
        string $position
    ): void {
        $tParticipants = $this->table($pdo, 'participants');
        $partCols = $this->getTableColumns($pdo, 'participants');
        if (empty($partCols)) {
            return;
        }

        $conditions = [];
        $params = [];

        if (isset($partCols['learner_user_id'])) {
            $conditions[] = "`learner_user_id` = :uid_learner";
            $params[':uid_learner'] = $userId;
        }

        $matchEmails = array_values(array_unique(array_filter([
            strtolower(trim($oldEmail)),
            strtolower(trim($email)),
        ])));

        if (!empty($matchEmails) && isset($partCols['email'])) {
            $emailPlaceholders = [];
            foreach ($matchEmails as $idx => $em) {
                $key = ':em_' . $idx;
                $emailPlaceholders[] = $key;
                $params[$key] = $em;
            }
            $conditions[] = "LOWER(TRIM(`email`)) IN (" . implode(',', $emailPlaceholders) . ")";
        }

        if (empty($conditions)) {
            return;
        }

        $setClauses = [];
        if (isset($partCols['learner_user_id'])) {
            $setClauses[] = "`learner_user_id` = COALESCE(`learner_user_id`, :set_uid)";
            $params[':set_uid'] = $userId;
        }
        if (isset($partCols['prefix_name'])) {
            $setClauses[] = "`prefix_name` = :prefix_name";
            $params[':prefix_name'] = $prefix !== '' ? $prefix : null;
        }
        if (isset($partCols['first_name'])) {
            $setClauses[] = "`first_name` = :first_name";
            $params[':first_name'] = $firstname;
        }
        if (isset($partCols['last_name'])) {
            $setClauses[] = "`last_name` = :last_name";
            $params[':last_name'] = $lastname;
        }
        if (isset($partCols['email'])) {
            $setClauses[] = "`email` = :new_email";
            $params[':new_email'] = $email;
        }
        if (isset($partCols['phone'])) {
            $setClauses[] = "`phone` = :phone";
            $params[':phone'] = $phone !== '' ? $phone : null;
        }
        if (isset($partCols['position'])) {
            $setClauses[] = "`position` = :position";
            $params[':position'] = $position !== '' ? $position : null;
        }
        if (isset($partCols['updated_at'])) {
            $setClauses[] = "`updated_at` = NOW()";
        }

        if (empty($setClauses)) {
            return;
        }

        $sql = "UPDATE `{$tParticipants}` SET " . implode(', ', $setClauses) . " WHERE (" . implode(' OR ', $conditions) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    public function editProfile(): string
    {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            $_SESSION['flash_error'] = 'กรุณาเข้าสู่ระบบก่อนแก้ไขข้อมูลส่วนตัวค่ะ';
            redirect('/login');
        }

        $activeTab = strtolower(trim((string)($_GET['tab'] ?? 'personal')));
        if (!in_array($activeTab, ['personal', 'company'], true)) {
            $activeTab = 'personal';
        }

        $selectedCompanyId = (int)($_GET['company_id'] ?? 0);
        $isNewCompany      = ((string)($_GET['new_company'] ?? '') === '1');

        $userModel = new \Modules\Auth\Models\UserModel($this->db());
        $user = $userModel->findById($userId) ?? [];
        unset($user['password'], $user['password_hash']);
        $userEmail = (string)($user['email'] ?? '');

        $pdo = $this->resolvePdo($this->db());
        $tCompanies    = $this->table($pdo, 'companies');
        $tCompanyUsers = $this->table($pdo, 'company_users');

        $stmtComps = $pdo->prepare("
            SELECT
                c.*,
                cu.`is_default`
            FROM `{$tCompanyUsers}` cu
            INNER JOIN `{$tCompanies}` c ON c.`id` = cu.`company_id`
            WHERE cu.`user_id` = :user_id
            ORDER BY cu.`is_default` DESC, c.`id` ASC
        ");
        $stmtComps->execute([':user_id' => $userId]);
        $companies = $stmtComps->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $company = [];
        if (!$isNewCompany && !empty($companies)) {
            if ($selectedCompanyId > 0) {
                foreach ($companies as $compRow) {
                    if ((int)($compRow['id'] ?? 0) === $selectedCompanyId) {
                        $company = $compRow;
                        break;
                    }
                }
            }
            if (empty($company)) {
                $company = $companies[0];
            }
        }

        $companyParticipantsById = [];
        foreach ($companies as $compRow) {
            $cid = (int)($compRow['id'] ?? 0);
            if ($cid > 0) {
                $companyParticipantsById[$cid] = $this->loadCompanyParticipants($pdo, $cid, $userId, $userEmail);
            }
        }

        $currentCompanyId = (int)($company['id'] ?? 0);
        $companyParticipants = (!$isNewCompany && $currentCompanyId > 0)
            ? ($companyParticipantsById[$currentCompanyId] ?? [])
            : [];

        $isCoordinator = !empty($companies);
        if (!$isCoordinator) {
            $tOrders = $this->table($pdo, 'orders');
            $stmtCoord = $pdo->prepare("SELECT 1 FROM `{$tOrders}` WHERE `user_id` = :user_id LIMIT 1");
            $stmtCoord->execute([':user_id' => $userId]);
            $isCoordinator = (bool)$stmtCoord->fetchColumn();
        }

        return view('Auth::profile', [
            'user'                    => $user,
            'company'                 => $company,
            'companies'               => $companies,
            'companyParticipantsById' => $companyParticipantsById,
            'companyParticipants'     => $companyParticipants,
            'isNewCompany'            => $isNewCompany,
            'isCoordinator'           => $isCoordinator,
            'activeTab'               => $activeTab,
            'breadcrumbs'             => ['หน้าแรก' => '/', 'แดชบอร์ด' => '/dashboard', 'แก้ไขข้อมูลส่วนตัว' => ''],
        ]);
    }

    public function updateProfile(): void
    {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            $_SESSION['flash_error'] = 'กรุณาเข้าสู่ระบบก่อนบันทึกข้อมูลค่ะ';
            redirect('/login');
            return;
        }

        $section = strtolower(trim((string)($_POST['section'] ?? 'personal')));
        if (!in_array($section, ['personal', 'company', 'set_default_company', 'unlink_company', 'update_participant_name'], true)) {
            $section = 'personal';
        }

        $pdo = $this->resolvePdo($this->db());

        if ($section === 'update_participant_name') {
            $participantId = (int)($_POST['participant_id'] ?? 0);
            $companyId     = (int)($_POST['company_id'] ?? 0);
            $prefixName    = trim((string)($_POST['prefix_name'] ?? ''));
            $firstName     = trim((string)($_POST['first_name'] ?? ''));
            $lastName      = trim((string)($_POST['last_name'] ?? ''));
            $position      = trim((string)($_POST['position'] ?? ''));

            $redirectBack = url('profile?tab=company' . ($companyId > 0 ? '&company_id=' . $companyId . '&modal_company_id=' . $companyId : ''));

            if ($participantId <= 0 || $firstName === '' || $lastName === '') {
                $_SESSION['flash_error'] = 'กรุณาระบุชื่อและนามสกุลของผู้เข้าอบรมให้ครบถ้วน';
                redirect($redirectBack);
                return;
            }

            $tParticipants = $this->table($pdo, 'participants');
            $tCompanyUsers = $this->table($pdo, 'company_users');
            $partCols = $this->getTableColumns($pdo, 'participants');

            try {
                $stmtChk = $pdo->prepare("
                    SELECT
                        p.`id`,
                        p.`company_id`,
                        p.`learner_user_id`,
                        p.`email`
                    FROM `{$tParticipants}` p
                    INNER JOIN `{$tCompanyUsers}` cu
                        ON cu.`company_id` = p.`company_id`
                        AND cu.`user_id` = :uid
                    WHERE p.`id` = :pid
                    LIMIT 1
                ");
                $stmtChk->execute([
                    ':uid' => $userId,
                    ':pid' => $participantId,
                ]);
                $partRow = $stmtChk->fetch(PDO::FETCH_ASSOC);

                if (!$partRow) {
                    throw new \RuntimeException('ไม่พบข้อมูลผู้เข้าอบรมหรือคุณไม่มีสิทธิ์แก้ไขรายการนี้');
                }

                $learnerUid = (int)($partRow['learner_user_id'] ?? 0);
                $partEmail  = strtolower(trim((string)($partRow['email'] ?? '')));
                $sessEmail  = strtolower(trim((string)($_SESSION['user_email'] ?? '')));

                if ($learnerUid === $userId || ($learnerUid === 0 && $sessEmail !== '' && $partEmail === $sessEmail)) {
                    throw new \RuntimeException('ชื่อและตำแหน่งของตัวเองไม่สามารถแก้ไขในหน้านี้ได้ กรุณาแก้ไขที่เมนู "แก้ไขข้อมูลส่วนตัว"');
                }

                if ($learnerUid > 0) {
                    throw new \RuntimeException('ผู้เข้าอบรมรายนี้มีบัญชีผู้ใช้เข้าระบบแล้ว จึงไม่สามารถแก้ไขแทนในหน้านี้ได้');
                }

                $targetCid = (int)($partRow['company_id'] ?? $companyId);

                $normFirst = mb_strtolower($firstName, 'UTF-8');
                $normLast  = mb_strtolower($lastName, 'UTF-8');

                $stmtDup = $pdo->prepare("
                    SELECT `id`, `prefix_name`, `first_name`, `last_name`
                    FROM `{$tParticipants}`
                    WHERE `company_id` = :cid
                      AND `id` <> :pid
                      AND LOWER(TRIM(`first_name`)) = :fn
                      AND LOWER(TRIM(`last_name`)) = :ln
                    LIMIT 1
                ");
                $stmtDup->execute([
                    ':cid' => $targetCid,
                    ':pid' => $participantId,
                    ':fn'  => $normFirst,
                    ':ln'  => $normLast,
                ]);
                $dupRow = $stmtDup->fetch(PDO::FETCH_ASSOC);
                if ($dupRow) {
                    $dupFull = trim(
                        trim((string)($dupRow['prefix_name'] ?? '')) . ' ' .
                        trim((string)($dupRow['first_name'] ?? '')) . ' ' .
                        trim((string)($dupRow['last_name'] ?? ''))
                    );
                    throw new \RuntimeException('ชื่อ-นามสกุล "' . $firstName . ' ' . $lastName . '" ซ้ำกับผู้เข้าอบรมรายอื่นในบริษัท (' . $dupFull . ')');
                }

                $setClauses = [];
                $params = [':pid' => $participantId];

                if (isset($partCols['prefix_name'])) {
                    $setClauses[] = "`prefix_name` = :prefix_name";
                    $params[':prefix_name'] = $prefixName !== '' ? $prefixName : null;
                }
                if (isset($partCols['first_name'])) {
                    $setClauses[] = "`first_name` = :first_name";
                    $params[':first_name'] = $firstName;
                }
                if (isset($partCols['last_name'])) {
                    $setClauses[] = "`last_name` = :last_name";
                    $params[':last_name'] = $lastName;
                }
                if (isset($partCols['position'])) {
                    $setClauses[] = "`position` = :position";
                    $params[':position'] = $position !== '' ? $position : null;
                }
                if (isset($partCols['updated_at'])) {
                    $setClauses[] = "`updated_at` = NOW()";
                }

                if (!empty($setClauses)) {
                    $stmtUpd = $pdo->prepare("
                        UPDATE `{$tParticipants}`
                        SET " . implode(', ', $setClauses) . "
                        WHERE `id` = :pid
                        LIMIT 1
                    ");
                    $stmtUpd->execute($params);
                }

                $_SESSION['flash_success'] = 'บันทึกชื่อ-นามสกุลและตำแหน่งผู้เข้าอบรมเรียบร้อยแล้วค่ะ';
                redirect(url('profile?tab=company' . ($targetCid > 0 ? '&company_id=' . $targetCid . '&modal_company_id=' . $targetCid : '')));
            } catch (Throwable $e) {
                $_SESSION['flash_error'] = 'ไม่สามารถบันทึกข้อมูลผู้เข้าอบรมได้: ' . $e->getMessage();
                redirect($redirectBack);
            }
            return;
        }

        if ($section === 'unlink_company') {
            $targetCompanyId = (int)($_POST['company_id'] ?? 0);
            if ($targetCompanyId <= 0) {
                $_SESSION['flash_error'] = 'ไม่พบข้อมูลบริษัทที่ต้องการนำออกจากการผูก';
                redirect(url('profile?tab=company'));
                return;
            }

            $tCompanies    = $this->table($pdo, 'companies');
            $tCompanyUsers = $this->table($pdo, 'company_users');
            $tParticipants = $this->table($pdo, 'participants');

            try {
                $pdo->beginTransaction();

                $stmtLink = $pdo->prepare("
                    SELECT `id`, `is_default`
                    FROM `{$tCompanyUsers}`
                    WHERE `company_id` = :cid AND `user_id` = :uid
                    LIMIT 1
                ");
                $stmtLink->execute([':cid' => $targetCompanyId, ':uid' => $userId]);
                $linkRow = $stmtLink->fetch(PDO::FETCH_ASSOC);

                if (!$linkRow) {
                    throw new \RuntimeException('ไม่พบความสัมพันธ์ผูกบริษัทนี้ในบัญชีของคุณ');
                }

                $wasDefault = ((int)($linkRow['is_default'] ?? 0) === 1);

                $pdo->prepare("
                    DELETE FROM `{$tCompanyUsers}`
                    WHERE `company_id` = :cid AND `user_id` = :uid
                    LIMIT 1
                ")->execute([':cid' => $targetCompanyId, ':uid' => $userId]);

                if ($wasDefault) {
                    $stmtNext = $pdo->prepare("
                        SELECT `id`
                        FROM `{$tCompanyUsers}`
                        WHERE `user_id` = :uid
                        ORDER BY `id` ASC
                        LIMIT 1
                    ");
                    $stmtNext->execute([':uid' => $userId]);
                    $nextId = $stmtNext->fetchColumn();
                    if ($nextId !== false) {
                        $pdo->prepare("UPDATE `{$tCompanyUsers}` SET `is_default` = 1 WHERE `id` = :id")
                            ->execute([':id' => (int)$nextId]);
                    }
                }

                $stmtRemUsers = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM `{$tCompanyUsers}`
                    WHERE `company_id` = :cid
                ");
                $stmtRemUsers->execute([':cid' => $targetCompanyId]);
                $remainingLinks = (int)$stmtRemUsers->fetchColumn();

                $stmtRemParts = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM `{$tParticipants}`
                    WHERE `company_id` = :cid
                ");
                $stmtRemParts->execute([':cid' => $targetCompanyId]);
                $remainingParticipants = (int)$stmtRemParts->fetchColumn();

                $deletedCompany = false;
                if ($remainingLinks === 0 && $remainingParticipants === 0) {
                    $stmtDelComp = $pdo->prepare("
                        DELETE FROM `{$tCompanies}`
                        WHERE `id` = :cid
                        LIMIT 1
                    ");
                    $stmtDelComp->execute([':cid' => $targetCompanyId]);
                    $deletedCompany = ($stmtDelComp->rowCount() > 0);
                }

                $pdo->commit();
                if ($deletedCompany) {
                    $_SESSION['flash_success'] = 'นำบริษัทออกจากการผูกและลบข้อมูลบริษัทออกจากระบบเรียบร้อยแล้วค่ะ (ไม่มีผู้ประสานงานและผู้เรียนผูกอยู่)';
                } elseif ($remainingLinks === 0 && $remainingParticipants > 0) {
                    $_SESSION['flash_success'] = 'นำบริษัทออกจากการผูกเรียบร้อยแล้วค่ะ (คงข้อมูลบริษัทไว้เนื่องจากยังมีผู้เรียนผูกอยู่ ' . $remainingParticipants . ' คน)';
                } else {
                    $_SESSION['flash_success'] = 'นำบริษัทออกจากการผูกเรียบร้อยแล้วค่ะ';
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $_SESSION['flash_error'] = 'ไม่สามารถนำบริษัทออกจากการผูกได้: ' . $e->getMessage();
            }

            redirect(url('profile?tab=company'));
            return;
        }

        if ($section === 'set_default_company') {
            $targetCompanyId = (int)($_POST['company_id'] ?? 0);
            if ($targetCompanyId <= 0) {
                $_SESSION['flash_error'] = 'ไม่พบข้อมูลบริษัทที่ต้องการตั้งเป็นค่าเริ่มต้น';
                redirect(url('profile?tab=company'));
                return;
            }

            $tCompanyUsers = $this->table($pdo, 'company_users');

            try {
                $pdo->beginTransaction();

                $stmtOwn = $pdo->prepare("
                    SELECT `id`
                    FROM `{$tCompanyUsers}`
                    WHERE `company_id` = :cid AND `user_id` = :uid
                    LIMIT 1
                ");
                $stmtOwn->execute([':cid' => $targetCompanyId, ':uid' => $userId]);
                if ($stmtOwn->fetchColumn() === false) {
                    throw new \RuntimeException('คุณไม่มีสิทธิ์จัดการบริษัทนี้');
                }

                $pdo->prepare("UPDATE `{$tCompanyUsers}` SET `is_default` = 0 WHERE `user_id` = :uid")
                    ->execute([':uid' => $userId]);

                $pdo->prepare("UPDATE `{$tCompanyUsers}` SET `is_default` = 1 WHERE `user_id` = :uid AND `company_id` = :cid")
                    ->execute([':uid' => $userId, ':cid' => $targetCompanyId]);

                $pdo->commit();
                $_SESSION['flash_success'] = 'ตั้งเป็นบริษัทเริ่มต้นเรียบร้อยแล้วค่ะ';
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $_SESSION['flash_error'] = 'ไม่สามารถตั้งเป็นบริษัทเริ่มต้นได้: ' . $e->getMessage();
            }

            redirect(url('profile?tab=company&company_id=' . $targetCompanyId));
            return;
        }

        if ($section === 'company') {
            $companyId    = (int)($_POST['company_id'] ?? 0);
            $setAsDefault = !empty($_POST['set_as_default']);
            $taxType      = trim((string)($_POST['tax_type'] ?? 'corporate'));
            if (!in_array($taxType, ['personal', 'corporate'], true)) {
                $taxType = 'corporate';
            }
            $companyName  = trim((string)($_POST['company_name'] ?? ''));
            $branch       = trim((string)($_POST['branch'] ?? 'สำนักงานใหญ่'));
            $taxId        = trim((string)($_POST['tax_id'] ?? ''));
            $address      = trim((string)($_POST['address'] ?? ''));
            $province     = trim((string)($_POST['province'] ?? ''));
            $amphoe       = trim((string)($_POST['amphoe'] ?? ''));
            $district     = trim((string)($_POST['district'] ?? ''));
            $zipcode      = trim((string)($_POST['zipcode'] ?? ''));
            $phone        = trim((string)($_POST['phone'] ?? ''));
            $billingEmail = trim((string)($_POST['billing_email'] ?? ''));

            if ($companyName === '') {
                $_SESSION['flash_error'] = 'กรุณากรอกชื่อบริษัท/องค์กร';
                redirect(url('profile?tab=company' . ($companyId > 0 ? '&company_id=' . $companyId : '&new_company=1')));
                return;
            }

            if ($billingEmail !== '' && !filter_var($billingEmail, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['flash_error'] = 'รูปแบบอีเมลสำหรับออกบิลไม่ถูกต้อง';
                redirect(url('profile?tab=company' . ($companyId > 0 ? '&company_id=' . $companyId : '&new_company=1')));
                return;
            }

            $tCompanies    = $this->table($pdo, 'companies');
            $tCompanyUsers = $this->table($pdo, 'company_users');

            try {
                $pdo->beginTransaction();

                $targetCompanyId = 0;
                if ($companyId > 0) {
                    $stmtOwn = $pdo->prepare("
                        SELECT `company_id`
                        FROM `{$tCompanyUsers}`
                        WHERE `company_id` = :cid AND `user_id` = :uid
                        LIMIT 1
                    ");
                    $stmtOwn->execute([':cid' => $companyId, ':uid' => $userId]);
                    if ($stmtOwn->fetchColumn() !== false) {
                        $targetCompanyId = $companyId;
                    }
                }

                if ($targetCompanyId > 0) {
                    $stmtUpd = $pdo->prepare("
                        UPDATE `{$tCompanies}`
                        SET
                            `tax_type` = :tax_type,
                            `company_name` = :company_name,
                            `branch` = :branch,
                            `tax_id` = :tax_id,
                            `address` = :address,
                            `province` = :province,
                            `amphoe` = :amphoe,
                            `district` = :district,
                            `zipcode` = :zipcode,
                            `phone` = :phone,
                            `billing_email` = :billing_email,
                            `updated_at` = NOW()
                        WHERE `id` = :cid
                        LIMIT 1
                    ");
                    $stmtUpd->execute([
                        ':tax_type'      => $taxType,
                        ':company_name'  => $companyName,
                        ':branch'        => $branch !== '' ? $branch : 'สำนักงานใหญ่',
                        ':tax_id'        => $taxId !== '' ? $taxId : null,
                        ':address'       => $address !== '' ? $address : null,
                        ':province'      => $province !== '' ? $province : null,
                        ':amphoe'        => $amphoe !== '' ? $amphoe : null,
                        ':district'      => $district !== '' ? $district : null,
                        ':zipcode'       => $zipcode !== '' ? $zipcode : null,
                        ':phone'         => $phone !== '' ? $phone : null,
                        ':billing_email' => $billingEmail !== '' ? $billingEmail : null,
                        ':cid'           => $targetCompanyId,
                    ]);

                    if ($setAsDefault) {
                        $pdo->prepare("UPDATE `{$tCompanyUsers}` SET `is_default` = 0 WHERE `user_id` = :uid")
                            ->execute([':uid' => $userId]);
                        $pdo->prepare("UPDATE `{$tCompanyUsers}` SET `is_default` = 1 WHERE `user_id` = :uid AND `company_id` = :cid")
                            ->execute([':uid' => $userId, ':cid' => $targetCompanyId]);
                    }
                } else {
                    $stmtIns = $pdo->prepare("
                        INSERT INTO `{$tCompanies}` (
                            `tax_type`, `company_name`, `branch`, `tax_id`, `address`,
                            `province`, `amphoe`, `district`, `zipcode`, `phone`, `billing_email`,
                            `created_at`, `updated_at`
                        ) VALUES (
                            :tax_type, :company_name, :branch, :tax_id, :address,
                            :province, :amphoe, :district, :zipcode, :phone, :billing_email,
                            NOW(), NOW()
                        )
                    ");
                    $stmtIns->execute([
                        ':tax_type'      => $taxType,
                        ':company_name'  => $companyName,
                        ':branch'        => $branch !== '' ? $branch : 'สำนักงานใหญ่',
                        ':tax_id'        => $taxId !== '' ? $taxId : null,
                        ':address'       => $address !== '' ? $address : null,
                        ':province'      => $province !== '' ? $province : null,
                        ':amphoe'        => $amphoe !== '' ? $amphoe : null,
                        ':district'      => $district !== '' ? $district : null,
                        ':zipcode'       => $zipcode !== '' ? $zipcode : null,
                        ':phone'         => $phone !== '' ? $phone : null,
                        ':billing_email' => $billingEmail !== '' ? $billingEmail : null,
                    ]);
                    $targetCompanyId = (int)$pdo->lastInsertId();

                    $stmtHasDefault = $pdo->prepare("
                        SELECT COUNT(*) FROM `{$tCompanyUsers}` WHERE `user_id` = :uid AND `is_default` = 1
                    ");
                    $stmtHasDefault->execute([':uid' => $userId]);
                    $hasDefault = ((int)$stmtHasDefault->fetchColumn()) > 0;

                    $shouldBeDefault = $setAsDefault || !$hasDefault;
                    if ($shouldBeDefault) {
                        $pdo->prepare("UPDATE `{$tCompanyUsers}` SET `is_default` = 0 WHERE `user_id` = :uid")
                            ->execute([':uid' => $userId]);
                    }

                    $stmtLink = $pdo->prepare("
                        INSERT INTO `{$tCompanyUsers}` (`company_id`, `user_id`, `is_default`, `created_at`)
                        VALUES (:cid, :uid, :is_def, NOW())
                        ON DUPLICATE KEY UPDATE `is_default` = VALUES(`is_default`)
                    ");
                    $stmtLink->execute([
                        ':cid'    => $targetCompanyId,
                        ':uid'    => $userId,
                        ':is_def' => $shouldBeDefault ? 1 : 0,
                    ]);
                }

                $pdo->commit();
                $_SESSION['flash_success'] = 'บันทึกข้อมูลบริษัทเรียบร้อยแล้วค่ะ';
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $_SESSION['flash_error'] = 'เกิดข้อผิดพลาดในการบันทึกข้อมูลบริษัท: ' . $e->getMessage();
            }

            redirect(url('profile?tab=company&company_id=' . $targetCompanyId));
            return;
        }

        $prefixRaw         = trim((string)($_POST['prefix'] ?? ''));
        $prefixCustom      = trim((string)($_POST['prefix_custom'] ?? ''));
        $prefix            = ($prefixRaw === '__other__') ? $prefixCustom : $prefixRaw;
        $firstname         = trim((string)($_POST['firstname'] ?? ''));
        $lastname          = trim((string)($_POST['lastname'] ?? ''));
        $email             = trim((string)($_POST['email'] ?? ''));
        $phone             = trim((string)($_POST['phone'] ?? ''));
        $idCard            = trim((string)($_POST['id_card'] ?? ''));
        $position          = trim((string)($_POST['position'] ?? ''));
        $otherRequirements = trim((string)($_POST['other_requirements'] ?? ''));
        $newPassword       = (string)($_POST['password'] ?? '');
        $confirmPassword   = (string)($_POST['password_confirmation'] ?? '');

        if ($prefixRaw === '__other__' && $prefix === '') {
            $_SESSION['flash_error'] = 'กรุณาระบุคำนำหน้าเพิ่มเติม';
            redirect(url('profile?tab=personal'));
            return;
        }

        if ($firstname === '' || $lastname === '' || $email === '') {
            $_SESSION['flash_error'] = 'กรุณากรอก ชื่อ นามสกุล และอีเมล ให้ครบถ้วน';
            redirect(url('profile?tab=personal'));
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'รูปแบบอีเมลไม่ถูกต้อง';
            redirect(url('profile?tab=personal'));
            return;
        }

        if ($newPassword !== '' && $newPassword !== $confirmPassword) {
            $_SESSION['flash_error'] = 'รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน';
            redirect(url('profile?tab=personal'));
            return;
        }

        try {
            $userModel = new \Modules\Auth\Models\UserModel($this->db());
            $currentUser = $userModel->findById($userId);
            if (!$currentUser) {
                $_SESSION['flash_error'] = 'ไม่พบข้อมูลบัญชีผู้ใช้งาน';
                redirect(url('profile?tab=personal'));
                return;
            }

            $oldEmail = (string)($currentUser['email'] ?? '');

            $dupUser = $userModel->findByEmailExcludingId($email, $userId);
            if ($dupUser) {
                $_SESSION['flash_error'] = 'อีเมลนี้ถูกใช้งานโดยบัญชีอื่นแล้ว';
                redirect(url('profile?tab=personal'));
                return;
            }

            $userCols = $this->getTableColumns($pdo, 'users');

            $updateData = [
                'prefix'             => $prefix,
                'firstname'          => $firstname,
                'lastname'           => $lastname,
                'email'              => $email,
                'phone'              => $phone,
                'id_card'            => $idCard,
                'position'           => $position,
                'other_requirements' => $otherRequirements,
            ];

            if ($newPassword !== '') {
                $updateData['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }
            if (isset($userCols['updated_at'])) {
                $updateData['updated_at'] = date('Y-m-d H:i:s');
            }

            $filteredData = [];
            foreach ($updateData as $col => $val) {
                if (isset($userCols[$col])) {
                    $filteredData[$col] = $val;
                }
            }

            $pdo->beginTransaction();

            $userModel->updateProfile($userId, $filteredData);

            $this->syncOwnParticipantProfile(
                $pdo,
                $userId,
                $oldEmail,
                $prefix,
                $firstname,
                $lastname,
                $email,
                $phone,
                $position
            );

            $pdo->commit();

            $_SESSION['user_name'] = trim($firstname . ' ' . $lastname);
            $_SESSION['user_email'] = $email;
            $_SESSION['flash_success'] = 'บันทึกการแก้ไขข้อมูลส่วนตัวและอัปเดตข้อมูลผู้เข้าเรียนเรียบร้อยแล้วค่ะ';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['flash_error'] = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage();
        }

        redirect(url('profile?tab=personal'));
    }
}