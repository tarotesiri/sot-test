<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training (SOT LMS)
 * File Name    : index.php
 * Description  : Admin Orders Index View (Complete Attendee & Order Info,
 *                Deduplicated Thai Address Formatting & Flash Messages)
 * ====================================================================
 */
declare(strict_types=1);

use Modules\Admin\Support\AdminHelper as H;
use Modules\Payment\Services\PaymentApprovalService;

$orders                  = is_array($orders ?? null) ? $orders : [];
$orderBatchesMap         = is_array($orderBatchesMap ?? null) ? $orderBatchesMap : [];
$orderSlipsMap           = is_array($orderSlipsMap ?? null) ? $orderSlipsMap : [];
$orderAttendeesByItemMap = is_array($orderAttendeesByItemMap ?? null) ? $orderAttendeesByItemMap : [];
$filters                 = is_array($filters ?? null) ? $filters : ['q' => '', 'status' => ''];
$pagination              = is_array($pagination ?? null) ? $pagination : ['page' => 1, 'total_pages' => 1, 'total_items' => 0];
$statusCounts            = is_array($statusCounts ?? null) ? $statusCounts : [];
$paymentStatusLabels     = is_array($paymentStatusLabels ?? null) ? $paymentStatusLabels : [
    'pending'               => 'รอการชำระ',
    'awaiting_verification' => 'กำลังตรวจสอบ',
    'pending_approval'      => 'รออนุมัติ',
    'paid'                  => 'ชำระเงินแล้ว',
    'cancelled'             => 'ยกเลิก',
    'refunded'              => 'คืนเงิน',
];

$pickField = static function (array $row, array $keys, string $default = ''): string {
    foreach ($keys as $k) {
        if (isset($row[$k]) && is_scalar($row[$k]) && trim((string)$row[$k]) !== '') {
            return trim((string)$row[$k]);
        }
    }
    return $default;
};

$sanitizeStreet = static function (
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

    // ตัดชุดแขวง/ตำบล + เขต/อำเภอ + จังหวัด + รหัสไปรษณีย์ ที่ต่อซ้ำท้ายสตริง
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
};

$formatOrderAddress = static function (array $row, string $fallbackAddr) use ($pickField, $sanitizeStreet): string {
    $district = $pickField($row, ['_district', 'inv_district', 'district']);
    $amphoe   = $pickField($row, ['_amphoe', 'inv_amphoe', 'amphoe']);
    $province = $pickField($row, ['_province', 'inv_province', 'province']);
    $zipcode  = $pickField($row, ['_zipcode', 'inv_zipcode', 'zipcode']);

    if ($district === '' && preg_match('/(?:แขวง|ต\.|ตำบล)\s*([^\s]+)/u', $fallbackAddr, $m)) {
        $district = trim($m[1]);
    }
    if ($amphoe === '' && preg_match('/(?:เขต|อ\.|อำเภอ)\s*([^\s]+)/u', $fallbackAddr, $m)) {
        $amphoe = trim($m[1]);
    }
    if ($province === '' && preg_match('/(?:กรุงเทพมหานคร|กรุงเทพ|(?:จ\.|จังหวัด)\s*[^\s]+)/u', $fallbackAddr, $m)) {
        $province = preg_replace('/^(?:จ\.|จังหวัด)\s*/u', '', trim($m[0])) ?? trim($m[0]);
    }
    if ($zipcode === '' && preg_match('/\b(\d{5})\b/u', $fallbackAddr, $m)) {
        $zipcode = trim($m[1]);
    }

    $street = $sanitizeStreet($fallbackAddr, $district, $amphoe, $province, $zipcode);
    $isBkk  = ($province !== '') && (str_contains($province, 'กรุงเทพ') || mb_strtolower($province, 'UTF-8') === 'bangkok');

    $parts = [];
    if ($street !== '') {
        $parts[] = $street;
    }
    if ($district !== '') {
        $parts[] = $isBkk ? ('แขวง' . $district) : ('ต.' . $district);
    }
    if ($amphoe !== '') {
        $parts[] = $isBkk ? ('เขต' . $amphoe) : ('อ.' . $amphoe);
    }
    if ($province !== '') {
        $parts[] = $isBkk ? $province : ('จ.' . $province);
    }
    if ($zipcode !== '') {
        $parts[] = $zipcode;
    }

    return implode(' ', $parts);
};

$statusBadgeStyle = static function (string $st): string {
    return match ($st) {
        'paid'                  => 'background:#dcfce7;color:#166534;border:1px solid #86efac;',
        'awaiting_verification' => 'background:#fef3c7;color:#92400e;border:1px solid #fcd34d;',
        'pending_approval'      => 'background:#e0f2fe;color:#075985;border:1px solid #7dd3fc;',
        'cancelled'             => 'background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;',
        'refunded'              => 'background:#f3e8ff;color:#6b21a8;border:1px solid #d8b4fe;',
        default                 => 'background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;',
    };
};

// รวบรวมข้อความแจ้งเตือน (msg) สีเขียว / สีแดง
$sotMsgs = [];
foreach (['success', 'flash_success', 'adm_success', 'msg_success', 'notice', 'message'] as $k) {
    if (!empty($$k) && is_scalar($$k)) {
        $sotMsgs[] = ['type' => 'success', 'text' => (string)$$k];
    }
}
foreach (['error', 'flash_error', 'adm_error', 'msg_error', 'danger'] as $k) {
    if (!empty($$k) && is_scalar($$k)) {
        $sotMsgs[] = ['type' => 'error', 'text' => (string)$$k];
    }
}

$sessionRoots = [
    &$_SESSION,
    &$_SESSION['_flash'],
    &$_SESSION['flash'],
    &$_SESSION['admin_flash'],
];

foreach ($sessionRoots as &$bag) {
    if (!is_array($bag)) {
        continue;
    }
    foreach (['success', 'flash_success', 'adm_success', '_flash_success', 'msg_success', 'ok'] as $k) {
        if (!empty($bag[$k]) && is_scalar($bag[$k])) {
            $sotMsgs[] = ['type' => 'success', 'text' => (string)$bag[$k]];
            unset($bag[$k]);
        }
    }
    foreach (['error', 'flash_error', 'adm_error', '_flash_error', 'msg_error', 'danger'] as $k) {
        if (!empty($bag[$k]) && is_scalar($bag[$k])) {
            $sotMsgs[] = ['type' => 'error', 'text' => (string)$bag[$k]];
            unset($bag[$k]);
        }
    }
    foreach (['message', 'flash_message', 'msg', 'notice'] as $k) {
        if (!empty($bag[$k]) && is_scalar($bag[$k])) {
            $rawType = strtolower((string)($bag['type'] ?? $bag['msg_type'] ?? $bag['status'] ?? ''));
            $mType = in_array($rawType, ['error', 'err', 'danger', 'fail', 'failed'], true) ? 'error' : 'success';
            $sotMsgs[] = ['type' => $mType, 'text' => (string)$bag[$k]];
            unset($bag[$k]);
        }
    }
}
unset($bag);

if (!empty($_GET['error']) && is_scalar($_GET['error'])) {
    $sotMsgs[] = ['type' => 'error', 'text' => trim((string)$_GET['error'])];
}
if (!empty($_GET['success']) && is_scalar($_GET['success'])) {
    $sotMsgs[] = ['type' => 'success', 'text' => trim((string)$_GET['success'])];
} elseif (isset($_GET['saved']) && (string)$_GET['saved'] === '1' && $sotMsgs === []) {
    $sotMsgs[] = ['type' => 'success', 'text' => 'บันทึกข้อมูลเรียบร้อยแล้ว'];
}

$pdfIconUrl = H::asset('img/icons/pdf.png');
?>

<div class="adm-page-head">
    <h1>จัดการคำสั่งซื้อ (Orders)</h1>
    <div style="display:flex;gap:8px">
        <a class="adm-btn is-light" href="<?= H::e(H::url('admin/payments')) ?>">ดูรายการแจ้งชำระ</a>
    </div>
</div>

<?php if ($sotMsgs !== []): ?>
    <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:14px">
        <?php foreach ($sotMsgs as $m): ?>
            <?php $isErr = ($m['type'] === 'error'); ?>
            <div style="padding:10px 14px;border-radius:8px;font-size:0.9rem;font-weight:700;border:1px solid <?= $isErr ? '#f87171' : '#4ade80' ?>;background:<?= $isErr ? '#fef2f2' : '#f0fdf4' ?>;color:<?= $isErr ? '#991b1b' : '#166534' ?>">
                <?= $isErr ? '✕ ไม่สำเร็จ: ' : '✓ สำเร็จ: ' ?><?= H::e($m['text']) ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- แถบตัวกรองตามสถานะชำระเงิน -->
<div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px">
    <?php
    $tabs = ['' => 'ทั้งหมด (' . (int)($statusCounts['all'] ?? 0) . ')'];
    foreach ($paymentStatusLabels as $stKey => $stLbl) {
        $tabs[$stKey] = $stLbl . ' (' . (int)($statusCounts[$stKey] ?? 0) . ')';
    }
    foreach ($tabs as $tabVal => $tabTitle):
        $isActive = ((string)($filters['status'] ?? '') === (string)$tabVal);
        $href = H::url('admin/orders') . ($tabVal !== '' ? ('?status=' . urlencode((string)$tabVal)) : '');
    ?>
        <a href="<?= H::e($href) ?>"
           class="adm-btn is-sm <?= $isActive ? 'is-primary' : 'is-light' ?>"
           style="text-decoration:none">
            <?= H::e($tabTitle) ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- ช่องค้นหา -->
<div class="adm-card" style="padding:14px;margin-bottom:16px">
    <form method="get" action="<?= H::e(H::url('admin/orders')) ?>" style="display:flex;flex-wrap:wrap;gap:10px">
        <?php if (!empty($filters['status'])): ?>
            <input type="hidden" name="status" value="<?= H::e((string)$filters['status']) ?>">
        <?php endif; ?>
        <input class="adm-input" type="text" name="q"
               value="<?= H::e((string)($filters['q'] ?? '')) ?>"
               placeholder="ค้นหาเลขที่คำสั่งซื้อ, เลขที่ใบแจ้งหนี้, ชื่อผู้ประสานงาน, ชื่อบริษัท หรือเลขผู้เสียภาษี..."
               style="flex:1;min-width:240px">
        <button class="adm-btn is-primary" type="submit">ค้นหา</button>
        <?php if (!empty($filters['q']) || !empty($filters['status'])): ?>
            <a class="adm-btn is-light" href="<?= H::e(H::url('admin/orders')) ?>">ล้างตัวกรอง</a>
        <?php endif; ?>
    </form>
</div>

<!-- ตารางคำสั่งซื้อ -->
<div class="adm-card">
    <div class="adm-table-wrap">
        <table class="adm-table" style="table-layout:fixed;width:100%">
            <thead>
                <tr>
                    <th style="width:160px">คำสั่งซื้อ / วันที่</th>
                    <th style="width:180px">ชื่อผู้ประสานงาน</th>
                    <th>ข้อมูลออกใบเสร็จ</th>
                    <th style="width:180px">อื่นๆ</th>
                    <th style="width:50px;text-align:center">หลัก<br/>สูตร</th>
                    <th style="width:50px;text-align:center">เข้า<br/>อบรม</th>
                    <th style="width:100px;text-align:right">ยอดสุทธิ</th>
                    <th style="width:125px">สถานะชำระเงิน</th>
                    <th style="width:80px">สลิป</th>
                    <th style="width:180px">จัดการ</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($orders === []): ?>
                <tr>
                    <td colspan="10" class="adm-empty">ไม่พบรายการคำสั่งซื้อ</td>
                </tr>
            <?php else: ?>
                <?php foreach ($orders as $o): ?>
                    <?php
                    $oid         = (int)($o['id'] ?? 0);
                    $orderNo     = $pickField($o, ['order_no'], '#' . $oid);
                    $invNo       = $pickField($o, ['invoice_no']);
                    $quoNo       = $pickField($o, ['quotation_no']);
                    $stKey       = $pickField($o, ['payment_status'], 'pending');
                    $batches     = $orderBatchesMap[$oid] ?? [];

                    $isOrderFullyApproved = !empty($o['_is_fully_approved']);
                    if (!$isOrderFullyApproved && $stKey === 'paid' && $batches !== []) {
                        $allApproved = true;
                        foreach ($batches as $chkB) {
                            if (strtolower(trim((string)($chkB['batch_approval_status'] ?? ''))) !== 'approved') {
                                $allApproved = false;
                                break;
                            }
                        }
                        $isOrderFullyApproved = $allApproved;
                    }

                    $stLbl       = ($stKey === 'paid' && $isOrderFullyApproved)
                        ? 'อนุมัติแล้ว'
                        : ($paymentStatusLabels[$stKey] ?? $stKey);
                    $coordName   = $pickField($o, ['_coordinator_name', 'customer_name'], '-');
                    $coordPhone  = $pickField($o, ['_coordinator_phone', 'customer_phone']);
                    $coordEmail  = $pickField($o, ['_coordinator_email', 'customer_email']);

                    $compName    = $pickField($o, ['_company_name', 'company_name']);
                    $branchLbl   = $pickField($o, ['_branch_label', 'branch_name', 'branch_code']);
                    $rawAddr     = $pickField($o, ['_full_address', 'billing_address', 'address']);
                    $fullAddr    = $formatOrderAddress($o, $rawAddr);
                    $taxId       = $pickField($o, ['_tax_id', 'tax_id']);
                    $poNumber    = $pickField($o, ['_po_number', 'po_number']);
                    $otherNote   = $pickField($o, ['_other_note', 'special_requirements', 'other_note', 'special_request', 'note']);

                    $courseCount = (int)($o['course_count'] ?? count($batches));
                    $attCount    = (int)($o['attendee_count'] ?? 0);
                    $grandTotal  = (float)($o['_grand_total'] ?? $o['grand_total'] ?? 0.0);
                    $canApprove  = PaymentApprovalService::canApproveBatchForOrder($stKey, $grandTotal);

                    $createdAt   = $pickField($o, ['created_at']);
                    $dateText    = $createdAt !== '' ? date('d/m/Y H:i', strtotime($createdAt)) : '-';
                    $slips       = $orderSlipsMap[$oid] ?? [];
                    $coursePanel = 'order-courses-' . $oid;

                    $quoPdfUrl   = H::url('cart/document?order_id=' . $oid . '&type=quo');
                    $ivePdfUrl   = H::url('cart/document?order_id=' . $oid . '&type=ive');
                    ?>
                    <tr>
                        <td style="word-break:break-word">
                            <a class="adm-strong" href="<?= H::e(H::url('admin/orders/' . $oid)) ?>" style="color:#1d4ed8">
                                <?= H::e($orderNo) ?>
                            </a>
                            <div class="adm-muted adm-small"><?= H::e($dateText) ?></div>
                            <?php if ($invNo !== ''): ?>
                                <div class="adm-small" style="color:#0f766e;display:flex;align-items:center;gap:4px;margin-top:2px">
                                    <a href="<?= H::e($ivePdfUrl) ?>" target="_blank" title="พิมพ์ใบแจ้งหนี้ / ใบกำกับภาษี (PDF)" style="display:inline-flex;align-items:center;flex-shrink:0">
                                        <img src="<?= H::e($pdfIconUrl) ?>" alt="PDF" style="width:15px;height:15px;object-fit:contain">
                                    </a>
                                    <span><?= H::e($invNo) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($quoNo !== ''): ?>
                                <div class="adm-muted adm-small" style="display:flex;align-items:center;gap:4px;margin-top:2px">
                                    <a href="<?= H::e($quoPdfUrl) ?>" target="_blank" title="พิมพ์ใบเสนอราคา (PDF)" style="display:inline-flex;align-items:center;flex-shrink:0">
                                        <img src="<?= H::e($pdfIconUrl) ?>" alt="PDF" style="width:15px;height:15px;object-fit:contain">
                                    </a>
                                    <span><?= H::e($quoNo) ?></span>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="word-break:break-word">
                            <div class="adm-strong"><?= H::e($coordName) ?></div>
                            <?php if ($coordPhone !== ''): ?>
                                <div class="adm-muted adm-small"><?= H::e($coordPhone) ?></div>
                            <?php endif; ?>
                            <?php if ($coordEmail !== ''): ?>
                                <div class="adm-muted adm-small"><?= H::e($coordEmail) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="white-space:normal;word-break:break-word;line-height:1.35">
                            <?php if ($compName === '' && $branchLbl === '' && $fullAddr === '' && $taxId === '' && $poNumber === ''): ?>
                                <span class="adm-muted">-</span>
                            <?php else: ?>
                                <?php if ($compName !== ''): ?>
                                    <div><span class="adm-muted adm-small">ชื่อบริษัท:</span> <strong class="adm-strong"><?= H::e($compName) ?></strong></div>
                                <?php endif; ?>
                                <?php if ($branchLbl !== ''): ?>
                                    <div class="adm-small"><span class="adm-muted">สาขา:</span> <?= H::e($branchLbl) ?></div>
                                <?php endif; ?>
                                <?php if ($fullAddr !== ''): ?>
                                    <div class="adm-small"><span class="adm-muted">ที่อยู่:</span> <?= nl2br(H::e($fullAddr)) ?></div>
                                <?php endif; ?>
                                <?php if ($taxId !== ''): ?>
                                    <div class="adm-muted adm-small">เลขผู้เสียภาษี: <?= H::e($taxId) ?></div>
                                <?php endif; ?>
                                <?php if ($poNumber !== ''): ?>
                                    <div class="adm-muted adm-small">PO Number: <?= H::e($poNumber) ?></div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td style="white-space:normal;word-break:break-word;line-height:1.35">
                            <?php if ($otherNote === ''): ?>
                                <span class="adm-muted">-</span>
                            <?php else: ?>
                                <div class="adm-small"><?= nl2br(H::e($otherNote)) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center">
                            <button type="button"
                                    onclick="toggleRowDisplay('<?= H::e($coursePanel) ?>')"
                                    title="คลิกเพื่อดูหลักสูตร"
                                    style="background:none;border:none;padding:2px 6px;cursor:pointer;font-weight:700;color:#1d4ed8;text-decoration:underline">
                                <?= $courseCount ?>
                            </button>
                        </td>
                        <td style="text-align:center">
                            <button type="button"
                                    onclick="toggleRowDisplay('<?= H::e($coursePanel) ?>')"
                                    title="คลิกเพื่อดูรายชื่อผู้เข้าอบรม"
                                    style="background:none;border:none;padding:2px 6px;cursor:pointer;font-weight:700;color:#1d4ed8;text-decoration:underline">
                                <?= $attCount ?>
                            </button>
                        </td>
                        <td style="text-align:right">
                            <strong><?= number_format($grandTotal, 2) ?></strong>
                        </td>
                        <td>
                            <span style="display:inline-block;padding:4px 9px;border-radius:999px;font-size:0.76rem;font-weight:700;<?= $statusBadgeStyle($stKey) ?>">
                                <?= H::e($stLbl) ?>
                            </span>
                        </td>
                        <td>
						    <?php if ($slips === []): ?>
						        <span class="adm-muted adm-small">—</span>
						    <?php else: ?>
						        <div style="display:flex;flex-direction:column;gap:4px">
						            <?php foreach ($slips as $idx => $sl): ?>
						                <?php $sRel = ltrim(str_replace('\\', '/', (string)($sl['slip_path'] ?? '')), '/'); ?>
						                <?php if ($sRel !== ''): ?>
						                    <?php
						                    $baseSlipUrl = H::url('admin/slips/view');
						                    $slipHref    = $baseSlipUrl . (str_contains($baseSlipUrl, '?') ? '&' : '?') . 'path=' . urlencode($sRel);
						                    ?>
						                    <a class="adm-btn is-light is-sm"
						                       href="<?= H::e($slipHref) ?>"
						                       target="_blank"
						                       style="padding:2px 8px;font-size:0.75rem">
						                        สลิป #<?= ($idx + 1) ?>
						                    </a>
						                <?php endif; ?>
						            <?php endforeach; ?>
						        </div>
						    <?php endif; ?>
						</td>
                        <td>
                            <div class="adm-actions" style="display:flex;flex-direction:column;gap:6px;align-items:flex-start">
                                <button type="button" class="adm-btn is-light is-sm"
                                        onclick="toggleRowDisplay('<?= H::e($coursePanel) ?>')">
                                    ▾ แสดงหลักสูตร/รายชื่อ
                                </button>
                                <a class="adm-btn is-light is-sm" href="<?= H::e(H::url('admin/orders/' . $oid)) ?>">
                                    แก้ไข / รายละเอียด
                                </a>
                            </div>
                        </td>
                    </tr>

                    <!-- แผงขยายแสดงหลักสูตร/รุ่น และปุ่มขยายดูรายชื่อผู้เข้าอบรมรายรุ่น -->
                    <tr id="<?= H::e($coursePanel) ?>" style="display:none;background:#f8fafc">
                        <td colspan="10" style="padding:12px 18px">
                            <?php if ($batches === []): ?>
                                <div class="adm-muted adm-small">ไม่พบข้อมูลหลักสูตร/รุ่นในคำสั่งซื้อนี้</div>
                            <?php else: ?>
                                <table class="adm-table" style="background:#fff">
                                    <thead>
                                        <tr>
                                            <th>หลักสูตร / รุ่น</th>
                                            <th>วันที่ / เวลาอบรม</th>
                                            <th style="text-align:center">ผู้เข้าอบรม</th>
                                            <th>เอกสารแนบ</th>
                                            <th>สถานะรุ่น</th>
                                            <th style="width:210px">ดำเนินการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($batches as $b): ?>
                                        <?php
                                        $oiid        = (int)($b['id'] ?? 0);
                                        $appStatus   = trim((string)($b['batch_approval_status'] ?? 'pending'));
                                        $isApproved  = ($appStatus === 'approved');
                                        $docTitle    = trim((string)($b['document_title'] ?? ''));
                                        $docFile     = trim((string)($b['document_file'] ?? ''));
                                        $hasDoc      = ($docFile !== '' || $docTitle !== '');
                                        $roster      = $orderAttendeesByItemMap[$oiid] ?? [];
                                        $rosterCount = count($roster) > 0 ? count($roster) : (int)($b['batch_attendees'] ?? 0);
                                        $rosterSubId = 'roster-sub-' . $oiid;
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="adm-strong"><?= H::e((string)($b['course_title'] ?? 'หลักสูตร')) ?></div>
                                                <div class="adm-small" style="color:#1d4ed8">รุ่น: <?= H::e((string)($b['batch_no'] ?? '-')) ?></div>
                                            </td>
                                            <td>
                                                <div><?= H::e((string)($b['batch_date_text'] ?? '-')) ?></div>
                                                <?php if (!empty($b['batch_time_text'])): ?>
                                                    <div class="adm-muted adm-small"><?= H::e((string)$b['batch_time_text']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align:center">
                                                <button type="button"
                                                        onclick="toggleRowDisplay('<?= H::e($rosterSubId) ?>')"
                                                        style="background:none;border:none;padding:2px 6px;cursor:pointer;font-weight:700;color:#1d4ed8;text-decoration:underline">
                                                    <?= $rosterCount ?> คน
                                                </button>
                                            </td>
                                            <td>
                                                <?php if ($hasDoc): ?>
                                                    <span class="adm-small" style="color:#15803d;font-weight:600">
                                                        ✓ <?= H::e($docTitle !== '' ? $docTitle : basename($docFile)) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="adm-muted adm-small">ไม่มีไฟล์แนบ</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($isApproved): ?>
                                                    <span style="display:inline-block;padding:3px 9px;border-radius:999px;font-size:0.76rem;font-weight:700;background:#dcfce7;color:#166534">
                                                        อนุมัติแล้ว
                                                    </span>
                                                <?php else: ?>
                                                    <span style="display:inline-block;padding:3px 9px;border-radius:999px;font-size:0.76rem;font-weight:700;background:#fef3c7;color:#92400e">
                                                        รออนุมัติ
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center">
                                                    <button type="button" class="adm-btn is-light is-sm"
                                                            onclick="toggleRowDisplay('<?= H::e($rosterSubId) ?>')">
                                                        ▾ รายชื่อ
                                                    </button>
                                                    <?php if ($isApproved): ?>
                                                        <form method="post" action="<?= H::e(H::url('admin/orders/' . $oid . '/batches/' . $oiid . '/unapprove')) ?>"
                                                              onsubmit="return confirm('ยืนยันยกเลิกอนุมัติรุ่นนี้? (ไม่ส่งอีเมล)')"
                                                              style="margin:0">
                                                            <?= H::csrf() ?>
                                                            <input type="hidden" name="redirect_to" value="index">
                                                            <button class="adm-btn is-light is-sm" type="submit">
                                                                ยกเลิกอนุมัติ
                                                            </button>
                                                        </form>
                                                    <?php elseif ($canApprove): ?>
                                                        <form method="post" action="<?= H::e(H::url('admin/orders/' . $oid . '/batches/' . $oiid . '/approve')) ?>"
                                                              onsubmit="return confirm('ยืนยันอนุมัติรุ่นนี้และส่งอีเมล?')"
                                                              style="margin:0">
                                                            <?= H::csrf() ?>
                                                            <input type="hidden" name="redirect_to" value="index">
                                                            <button class="adm-btn is-primary is-sm" type="submit">
                                                                อนุมัติรุ่นนี้
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- แถวขยายดูรายชื่อผู้เข้าอบรมของรุ่นนี้ -->
                                        <tr id="<?= H::e($rosterSubId) ?>" style="display:none;background:#f1f5f9">
                                            <td colspan="6" style="padding:10px 14px">
                                                <?php if ($roster === []): ?>
                                                    <div class="adm-muted adm-small">ยังไม่มีรายชื่อผู้เข้าอบรมในรุ่นนี้</div>
                                                <?php else: ?>
                                                    <table class="adm-table" style="background:#fff">
                                                        <thead>
                                                            <tr>
                                                                <th style="width:45px">#</th>
                                                                <th>คำนำหน้า</th>
                                                                <th>ชื่อ (ภาษาไทย)</th>
                                                                <th>นามสกุล (ภาษาไทย)</th>
                                                                <th>อีเมลผู้เรียน</th>
                                                                <th>เบอร์โทรศัพท์</th>
                                                                <th>เลขบัตรประชาชน (13 หลัก)</th>
                                                                <th>ตำแหน่งงาน</th>
                                                                <th>ความต้องการอื่นๆ</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                        <?php foreach ($roster as $rIdx => $person): ?>
                                                            <?php
                                                            $prefix    = $pickField($person, ['prefix', 'prefix_name', 'title', 'part_prefix_name', 'usr_prefix']);
                                                            $firstName = $pickField($person, ['first_name', 'fname', 'part_first_name', 'usr_firstname']);
                                                            $lastName  = $pickField($person, ['last_name', 'lname', 'part_last_name', 'usr_lastname']);
                                                            if ($firstName === '' && $lastName === '') {
                                                                $full = $pickField($person, ['full_name', 'attendee_name']);
                                                                if ($full !== '') {
                                                                    $parts = preg_split('/\s+/u', $full, 2) ?: [];
                                                                    $firstName = $parts[0] ?? '';
                                                                    $lastName  = $parts[1] ?? '';
                                                                }
                                                            }
                                                            $email     = $pickField($person, ['email', 'attendee_email', 'part_email', 'usr_email'], '-');
                                                            $phone     = $pickField($person, ['phone', 'tel', 'mobile', 'part_phone', 'usr_phone'], '-');
                                                            $idCard    = $pickField($person, ['id_card', 'citizen_id', 'national_id', 'id_number', 'part_id_card', 'usr_id_card'], '-');
                                                            $position  = $pickField($person, ['position', 'job_title', 'job_position', 'role', 'part_position', 'usr_position'], '-');
                                                            $special   = $pickField($person, ['other_requirements', 'other_requirement', 'special_requirements', 'special_request', 'other_request', 'dietary', 'remark', 'note', 'part_other_requirements'], '-');
                                                            ?>
                                                            <tr>
                                                                <td><?= ($rIdx + 1) ?></td>
                                                                <td><?= H::e($prefix !== '' ? $prefix : '-') ?></td>
                                                                <td><?= H::e($firstName !== '' ? $firstName : '-') ?></td>
                                                                <td><?= H::e($lastName !== '' ? $lastName : '-') ?></td>
                                                                <td><?= H::e($email) ?></td>
                                                                <td><?= H::e($phone) ?></td>
                                                                <td><?= H::e($idCard) ?></td>
                                                                <td><?= H::e($position) ?></td>
                                                                <td><?= H::e($special) ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleRowDisplay(rowId) {
    var el = document.getElementById(rowId);
    if (!el) return;
    el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'table-row' : 'none';
}
</script>