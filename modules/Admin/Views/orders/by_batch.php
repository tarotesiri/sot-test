<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name    : by_batch.php
 * Description  : Orders Grouped by Course, Batch & Training Date
 * ====================================================================
**/
declare(strict_types=1);

use Modules\Admin\Support\AdminHelper as H;
use Modules\Payment\Services\PaymentApprovalService;

$groups    = is_array($groups ?? null) ? $groups : [];
$filters   = is_array($filters ?? null) ? $filters : [];
$qVal      = (string)($filters['q'] ?? '');
$payVal    = (string)($filters['payment_status'] ?? '');
$batchApp  = (string)($filters['batch_approval'] ?? '');
$courseId  = (int)($filters['course_id'] ?? 0);
$batchId   = (int)($filters['batch_id'] ?? 0);

$statusLabels = is_array($paymentStatusLabels ?? null) && $paymentStatusLabels !== []
    ? $paymentStatusLabels
    : PaymentApprovalService::PAYMENT_STATUS_LABELS;

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

// 1. จัดเรียงกลุ่มรุ่นอบรม:
//    - สถานะเปิด/เต็ม ขึ้นมาก่อน (วันที่ใกล้ถึงขึ้นก่อน)
//    - สถานะปิด/ยกเลิก อยู่ถัดไป (วันที่ใหม่อยู่บน)
usort($groups, static function (array $a, array $b): int {
    $stA = strtolower(trim((string)($a['batch_status'] ?? 'open')));
    $stB = strtolower(trim((string)($b['batch_status'] ?? 'open')));

    $closedStatuses = ['closed', 'cancelled', 'canceled', 'inactive', 'ended'];
    $isClosedA = in_array($stA, $closedStatuses, true) ? 1 : 0;
    $isClosedB = in_array($stB, $closedStatuses, true) ? 1 : 0;

    if ($isClosedA !== $isClosedB) {
        return $isClosedA <=> $isClosedB;
    }

    $rawDateA = trim((string)($a['date_start'] ?? ''));
    $rawDateB = trim((string)($b['date_start'] ?? ''));
    $tsA = ($rawDateA !== '' && $rawDateA !== '0000-00-00') ? (strtotime($rawDateA) ?: 0) : 0;
    $tsB = ($rawDateB !== '' && $rawDateB !== '0000-00-00') ? (strtotime($rawDateB) ?: 0) : 0;

    if ($tsA === 0 && $tsB !== 0) {
        return 1;
    }
    if ($tsA !== 0 && $tsB === 0) {
        return -1;
    }

    if ($tsA !== $tsB) {
        if ($isClosedA === 0) {
            return $tsA <=> $tsB; // เปิด/เต็ม: วันที่ใกล้ถึงขึ้นก่อน
        }
        return $tsB <=> $tsA; // ปิด/ยกเลิก: วันที่ใหม่อยู่บน
    }

    return ((int)($b['batch_id'] ?? $b['id'] ?? 0)) <=> ((int)($a['batch_id'] ?? $a['id'] ?? 0));
});

// 2. เรียงลำดับคำสั่งซื้อภายในแต่ละกลุ่มให้ "ใหม่ล่าสุดอยู่บนสุด" เสมอ
foreach ($groups as &$grp) {
    $ordersList = is_array($grp['orders'] ?? null) ? $grp['orders'] : [];
    usort($ordersList, static function (array $a, array $b): int {
        $tA = strtotime((string)($a['created_at'] ?? '')) ?: 0;
        $tB = strtotime((string)($b['created_at'] ?? '')) ?: 0;
        if ($tA !== $tB) {
            return $tB <=> $tA;
        }
        return ((int)($b['id'] ?? 0)) <=> ((int)($a['id'] ?? 0));
    });
    $grp['orders'] = $ordersList;
}
unset($grp);
?>

<div style="min-width:0;max-width:100%;box-sizing:border-box">
    <div class="adm-page-head" style="gap:10px;flex-wrap:wrap">
        <div style="min-width:0;flex:1">
            <h1 style="margin:0;word-break:break-word">คำสั่งซื้อแยกตามหลักสูตร-รุ่นวันที่อบรม</h1>
            <div style="font-size:0.84rem;color:#64748b;margin-top:4px;line-height:1.45">
                เรียงรุ่นเปิด/เต็ม (วันที่ใกล้ถึงขึ้นก่อน) ตามด้วยรุ่นปิด/ยกเลิก (วันที่ใหม่อยู่บน) และเรียงคำสั่งซื้อใหม่ล่าสุดไว้บนสุดของแต่ละรุ่น
            </div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;flex-shrink:0">
            <a class="adm-btn is-light" href="<?= H::e(H::url('admin/orders')) ?>">คำสั่งซื้อทั้งหมด</a>
            <a class="adm-btn is-light" href="<?= H::e(H::url('admin/batches')) ?>">หลักสูตร/รุ่นที่จัดอบรม</a>
        </div>
    </div>

    <!-- ตัวกรองค้นหา -->
    <section class="adm-card" style="margin-bottom:16px;min-width:0;max-width:100%;box-sizing:border-box">
        <div class="adm-card-body">
            <form method="get" action="<?= H::e(H::url('admin/order_batches')) ?>" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                <?php if ($courseId > 0): ?>
                    <input type="hidden" name="course_id" value="<?= $courseId ?>">
                <?php endif; ?>
                <?php if ($batchId > 0): ?>
                    <input type="hidden" name="batch_id" value="<?= $batchId ?>">
                <?php endif; ?>

                <input class="adm-input" type="text" name="q" value="<?= H::e($qVal) ?>"
                       placeholder="ค้นหาเลขที่คำสั่งซื้อ, ผู้ประสานงาน, ใบเสร็จ, หลักสูตร หรือรุ่น..."
                       style="flex:1 1 200px;min-width:180px;box-sizing:border-box">

                <select class="adm-input" name="payment_status" style="width:auto;min-width:155px;box-sizing:border-box">
                    <option value="">สถานะชำระเงินทั้งหมด</option>
                    <?php foreach ($statusLabels as $stKey => $stLabel): ?>
                        <option value="<?= H::e((string)$stKey) ?>"<?= H::sel($payVal, (string)$stKey) ?>><?= H::e((string)$stLabel) ?></option>
                    <?php endforeach; ?>
                </select>

                <select class="adm-input" name="batch_approval" style="width:auto;min-width:150px;box-sizing:border-box">
                    <option value="">สถานะอนุมัติรุ่นทั้งหมด</option>
                    <option value="pending"<?= H::sel($batchApp, 'pending') ?>>รออนุมัติรุ่น</option>
                    <option value="approved"<?= H::sel($batchApp, 'approved') ?>>อนุมัติรุ่นแล้ว</option>
                </select>

                <button type="submit" class="adm-btn is-primary is-sm">ค้นหา</button>
                <?php if ($qVal !== '' || $payVal !== '' || $batchApp !== '' || $courseId > 0 || $batchId > 0): ?>
                    <a href="<?= H::e(H::url('admin/order_batches')) ?>" class="adm-btn is-light is-sm">ล้างตัวกรอง</a>
                <?php endif; ?>
            </form>
        </div>
    </section>

    <!-- รายการแยกตามหลักสูตร - รุ่น - วันที่อบรม -->
    <?php if ($groups === []): ?>
        <section class="adm-card" style="min-width:0;max-width:100%;box-sizing:border-box">
            <div class="adm-card-body adm-empty">ไม่พบข้อมูลคำสั่งซื้อตามเงื่อนไขที่เลือก</div>
        </section>
    <?php endif; ?>

    <?php foreach ($groups as $idx => $grp):
        $courseTitle = (string)($grp['course_title'] ?? 'ไม่ระบุหลักสูตร');
        $batchNo     = (string)($grp['batch_no'] ?? '-');
        $dateText    = trim((string)($grp['date_text'] ?? ''));
        $timeText    = trim((string)($grp['time_text'] ?? ''));
        $batchStatus = (string)($grp['batch_status'] ?? '');
        $ordersList  = is_array($grp['orders'] ?? null) ? $grp['orders'] : [];

        $uniqueOrderKeys    = [];
        $paidOrderKeys      = [];
        $followUpOrderKeys  = [];
        $attendeeTotalCount = 0;
        $approvedAttendeeCnt = 0;
        $pendingAttendeeCnt  = 0;
        $amountTotal         = 0.0;

        foreach ($ordersList as $ord) {
            $ordIdVal = (int)($ord['order_id'] ?? $ord['id'] ?? 0);
            $ordNoVal = trim((string)($ord['order_no'] ?? ''));
            $ordKey   = $ordIdVal > 0 ? ('id:' . $ordIdVal) : ($ordNoVal !== '' ? ('no:' . $ordNoVal) : ('row:' . spl_object_id((object)$ord)));
            $uniqueOrderKeys[$ordKey] = true;

            $attCnt     = (int)($ord['attendee_count'] ?? $ord['quantity'] ?? 1);
            $amountVal  = (float)($ord['line_total'] ?? $ord['grand_total'] ?? 0);
            $bAppStatus = strtolower(trim((string)($ord['batch_approval_status'] ?? 'pending')));
            $pStatusRaw = strtolower(trim((string)($ord['payment_status'] ?? 'pending')));

            $attendeeTotalCount += $attCnt;
            $amountTotal        += $amountVal;

            if ($bAppStatus === 'approved') {
                $approvedAttendeeCnt += $attCnt;
            } elseif (!in_array($pStatusRaw, ['cancelled', 'canceled', 'refunded'], true)) {
                $pendingAttendeeCnt += $attCnt;
            }

            if ($pStatusRaw === 'paid') {
                $paidOrderKeys[$ordKey] = true;
            } elseif (!in_array($pStatusRaw, ['cancelled', 'canceled', 'refunded'], true)) {
                $followUpOrderKeys[$ordKey] = true;
            }
        }

        $orderTotalCount    = count($uniqueOrderKeys);
        $paidOrderCount     = count($paidOrderKeys);
        $followUpOrderCount = count($followUpOrderKeys);
        $collapseId         = 'batchOrdersGroup_' . $idx;
    ?>
    <section class="adm-card" style="margin-bottom:16px;min-width:0;max-width:100%;box-sizing:border-box">
        <!-- ส่วนหัวของแต่ละรุ่น -->
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;padding:12px 16px;background:#f8fafc;border-bottom:1px solid #e2e8f0;box-sizing:border-box">
            <div style="display:flex;align-items:flex-start;gap:8px;min-width:0;flex:1 1 340px">
                <button type="button"
                        onclick="toggleBatchGroup('<?= H::e($collapseId) ?>', this)"
                        style="border:1px solid #cbd5e1;background:#fff;color:#334155;border-radius:6px;padding:4px 8px;font-size:12px;cursor:pointer;flex-shrink:0;margin-top:2px">
                    ย่อ
                </button>
                <div style="min-width:0;flex:1">
                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;font-size:0.98rem;font-weight:700;color:#0f172a;line-height:1.4">
                        <span style="word-break:break-word"><?= H::e($courseTitle) ?></span>
                        <span style="color:#2563eb;white-space:nowrap"><?= H::e($batchNo) ?></span>
                        <?php if ($batchStatus !== ''): ?>
                            <span><?= H::badge($batchStatus) ?></span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:0.82rem;color:#475569;margin-top:3px;line-height:1.45;word-break:break-word">
                        <strong>วันที่อบรม:</strong> <?= H::e($dateText !== '' ? $dateText : 'ไม่ระบุวันอบรม') ?>
                        <?php if ($timeText !== ''): ?>
                            <span style="margin-left:8px"><strong>เวลา:</strong> <?= H::e($timeText) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- สรุปตัวเลขของรุ่น -->
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;font-size:0.82rem;color:#334155;flex-shrink:0">
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;justify-content:flex-end">
                    <div>คำสั่งซื้อ: <strong><?= number_format($orderTotalCount) ?></strong></div>
                    <div>ชำระเงินแล้ว: <strong style="color:#15803d"><?= number_format($paidOrderCount) ?></strong></div>
                    <div>ต้องติดตาม: <strong style="color:#dc2626"><?= number_format($followUpOrderCount) ?></strong></div>
                    <div>ยอดเงินรวม: <strong><?= number_format($amountTotal, 2) ?></strong> บาท</div>
                </div>
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;justify-content:flex-end">
                    <div>ผู้สมัครเข้าอบรม: <strong><?= number_format($attendeeTotalCount) ?></strong> ท่าน</div>
                    <div>อนุมัติแล้ว: <strong style="color:#15803d"><?= number_format($approvedAttendeeCnt) ?></strong> ท่าน</div>
                    <div>รออนุมัติอยู่: <strong style="color:#b45309"><?= number_format($pendingAttendeeCnt) ?></strong> ท่าน</div>
                </div>
            </div>
        </div>

        <!-- ตารางคำสั่งซื้อในรุ่น (ใหม่สุดอยู่บนสุด) -->
        <div id="<?= H::e($collapseId) ?>" class="adm-table-wrap" style="overflow-x:auto;max-width:100%">
            <table class="adm-table" style="table-layout:fixed;width:100%;min-width:940px">
                <colgroup>
                    <col style="width:13%"> <!-- คำสั่งซื้อ / วันที่ -->
                    <col style="width:15%"> <!-- ชื่อผู้ประสานงาน -->
                    <col style="width:28%"> <!-- ข้อมูลออกใบเสร็จ -->
                    <col style="width:14%"> <!-- อื่นๆ -->
                    <col style="width:7%">  <!-- จำนวน -->
                    <col style="width:9%">  <!-- ยอดเงิน -->
                    <col style="width:8%">  <!-- สถานะชำระเงิน -->
                    <col style="width:6%">  <!-- สลิป -->
                </colgroup>
                <thead>
                    <tr>
                        <th style="padding:8px 8px">คำสั่งซื้อ / วันที่</th>
                        <th style="padding:8px 8px">ชื่อผู้ประสานงาน</th>
                        <th style="padding:8px 8px">ข้อมูลออกใบเสร็จ</th>
                        <th style="padding:8px 8px">อื่นๆ</th>
                        <th style="padding:8px 6px;text-align:center">จำนวน</th>
                        <th style="padding:8px 8px;text-align:right">ยอดเงิน</th>
                        <th style="padding:8px 6px;text-align:center">สถานะชำระเงิน</th>
                        <th style="padding:8px 6px;text-align:center">สลิป</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($ordersList === []): ?>
                    <tr><td colspan="8" class="adm-empty">ไม่มีคำสั่งซื้อในรุ่นนี้</td></tr>
                <?php endif; ?>
                <?php foreach ($ordersList as $ord):
                    $oid        = (int)($ord['id'] ?? 0);
                    $orderNo    = $pickField($ord, ['order_no'], '#' . $oid);
                    $createdAt  = $pickField($ord, ['created_at']);
                    $dateText   = $createdAt !== '' ? date('d/m/Y H:i', strtotime($createdAt)) : '-';

                    $coordName  = $pickField($ord, ['customer_name'], '-');
                    $coordPhone = $pickField($ord, ['customer_phone']);
                    $coordEmail = $pickField($ord, ['customer_email']);

                    $compName   = $pickField($ord, ['inv_company_name', 'company_name']);
                    $branchLbl  = $pickField($ord, ['inv_branch', 'branch_name']);
                    $rawAddr    = $pickField($ord, ['inv_full_address', 'inv_address', 'address']);
                    $fullAddr   = $formatOrderAddress($ord, $rawAddr);
                    $taxId      = $pickField($ord, ['inv_tax_id', 'tax_id']);
                    $poNumber   = $pickField($ord, ['inv_po_number', 'po_number']);

                    $otherNote  = $pickField($ord, ['other_note', 'special_requirements']);

                    $attCnt     = (int)($ord['attendee_count'] ?? $ord['quantity'] ?? 1);
                    $amountVal  = (float)($ord['line_total'] ?? $ord['grand_total'] ?? 0);

                    $stKey      = strtolower(trim($pickField($ord, ['payment_status'], 'pending')));
                    $bAppStatus = strtolower(trim($pickField($ord, ['batch_approval_status'], 'pending')));
                    $isApproved = ($stKey === 'paid' && $bAppStatus === 'approved');
                    $stLbl      = $isApproved
                        ? 'อนุมัติแล้ว'
                        : ($statusLabels[$stKey] ?? PaymentApprovalService::paymentStatusLabel($stKey));

                    $slips = is_array($ord['slips'] ?? null) ? $ord['slips'] : [];
                    if ($slips === [] && $pickField($ord, ['slip_path']) !== '') {
                        $slips = [$pickField($ord, ['slip_path'])];
                    }
                ?>
                    <tr>
                        <!-- คำสั่งซื้อ / วันที่ -->
                        <td style="padding:8px 8px;word-break:break-word;vertical-align:top">
                            <a class="adm-strong" href="<?= H::e(H::url('admin/orders/' . $oid)) ?>" style="color:#1d4ed8">
                                <?= H::e($orderNo) ?>
                            </a>
                            <div class="adm-muted adm-small" style="margin-top:2px"><?= H::e($dateText) ?></div>
                        </td>

                        <!-- ชื่อผู้ประสานงาน -->
                        <td style="padding:8px 8px;word-break:break-word;vertical-align:top">
                            <div class="adm-strong"><?= H::e($coordName) ?></div>
                            <?php if ($coordPhone !== ''): ?>
                                <div class="adm-muted adm-small"><?= H::e($coordPhone) ?></div>
                            <?php endif; ?>
                            <?php if ($coordEmail !== ''): ?>
                                <div class="adm-muted adm-small"><?= H::e($coordEmail) ?></div>
                            <?php endif; ?>
                        </td>

                        <!-- ข้อมูลออกใบเสร็จ -->
                        <td style="padding:8px 8px;white-space:normal;word-break:break-word;line-height:1.35;vertical-align:top">
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

                        <!-- อื่นๆ -->
                        <td style="padding:8px 8px;white-space:normal;word-break:break-word;line-height:1.35;vertical-align:top">
                            <?php if ($otherNote === ''): ?>
                                <span class="adm-muted">-</span>
                            <?php else: ?>
                                <div class="adm-small"><?= nl2br(H::e($otherNote)) ?></div>
                            <?php endif; ?>
                        </td>

                        <!-- จำนวน -->
                        <td style="padding:8px 6px;text-align:center;vertical-align:top;font-size:0.85rem;white-space:nowrap">
                            <strong><?= $attCnt ?></strong> ท่าน
                        </td>

                        <!-- ยอดเงิน -->
                        <td style="padding:8px 8px;text-align:right;vertical-align:top;font-size:0.85rem;font-weight:600;color:#0f172a;white-space:nowrap">
                            <?= number_format($amountVal, 2) ?> บาท
                        </td>

                        <!-- สถานะชำระเงิน -->
                        <td style="padding:8px 6px;text-align:center;vertical-align:top">
                            <span style="display:inline-block;padding:4px 9px;border-radius:999px;font-size:0.75rem;font-weight:700;<?= $statusBadgeStyle($stKey) ?>">
                                <?= H::e($stLbl) ?>
                            </span>
                        </td>

                        <!-- สลิป -->
                        <td style="padding:8px 6px;vertical-align:top">
                            <?php if ($slips === []): ?>
                                <span class="adm-muted adm-small">—</span>
                            <?php else: ?>
                                <div style="display:flex;flex-direction:column;gap:4px;align-items:flex-start">
                                    <?php foreach ($slips as $sIdx => $sPath): ?>
                                        <?php $sRel = ltrim(str_replace('\\', '/', (string)$sPath), '/'); ?>
                                        <?php if ($sRel !== ''): ?>
                                            <?php
                                            $sep = str_contains(H::url('admin/slips/view'), '?') ? '&' : '?';
                                            $slipViewUrl = H::url('admin/slips/view') . $sep . 'path=' . urlencode($sRel);
                                            ?>
                                            <a class="adm-btn is-light is-sm"
                                               href="<?= H::e($slipViewUrl) ?>"
                                               target="_blank"
                                               style="padding:2px 8px;font-size:0.75rem">
                                                สลิป #<?= ($sIdx + 1) ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endforeach; ?>
</div>

<script>
function toggleBatchGroup(targetId, btnEl) {
    var el = document.getElementById(targetId);
    if (!el) return;
    var isHidden = (el.style.display === 'none');
    el.style.display = isHidden ? '' : 'none';
    if (btnEl) {
        btnEl.textContent = isHidden ? 'ย่อ' : 'กาง';
    }
}
</script>