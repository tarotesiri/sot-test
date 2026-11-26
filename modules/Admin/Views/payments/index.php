<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name    : index.php
 * Description  : Admin Payment Slip Log View (Read-only List)
 * ====================================================================
 */
declare(strict_types=1);

use Modules\Admin\Support\AdminHelper as H;

$payments     = is_array($payments ?? null) ? $payments : [];
$statusLabels = is_array($statusLabels ?? null) ? $statusLabels : [
    'pending'               => 'รอการชำระ',
    'awaiting_verification' => 'กำลังตรวจสอบ',
    'pending_approval'      => 'รออนุมัติ',
    'paid'                  => 'ชำระเงินแล้ว',
    'cancelled'             => 'ยกเลิก',
    'refunded'              => 'คืนเงิน',
];
?>

<div class="adm-page-head">
    <h1>รายการแจ้งชำระเงิน (ประวัติการแจ้งโอน)</h1>
    <div style="display:flex;gap:8px">
        <a class="adm-btn is-light" href="<?= H::e(H::url('admin/orders')) ?>">ไปหน้าจัดการคำสั่งซื้อ</a>
    </div>
</div>

<div class="adm-card">
    <div class="adm-table-wrap">
        <table class="adm-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>เลขที่อ้างอิง / Order ที่ผูก</th>
                    <th>ผู้แจ้ง / บริษัท / โทร</th>
                    <th>ธนาคาร / วันโอน</th>
                    <th style="text-align:right">ยอดโอน / ยอด Order</th>
                    <th>สลิป</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($payments === []): ?>
                <tr>
                    <td colspan="6" class="adm-empty">ยังไม่มีรายการแจ้งชำระเงิน</td>
                </tr>
            <?php else: ?>
                <?php foreach ($payments as $row): ?>
                    <?php
                    $pid          = (int)($row['id'] ?? 0);
                    $curOrderId   = (int)($row['order_id'] ?? 0);
                    $curStatus    = (string)($row['current_order_status'] ?? 'pending');
                    $curStatusLbl = $statusLabels[$curStatus] ?? $curStatus;
                    $slipRel      = ltrim((string)($row['slip_path'] ?? ''), '/');
                    $paidAmt      = (float)($row['transfer_amount'] ?? 0);
                    $hasOrdTotal  = isset($row['matched_grand_total']) && $row['matched_grand_total'] !== null && $row['matched_grand_total'] !== '';
                    $ordTotal     = $hasOrdTotal ? (float)$row['matched_grand_total'] : null;
                    $amtMismatch  = ($ordTotal !== null && abs($paidAmt - $ordTotal) >= 0.01);
                    ?>
                    <tr>
                        <td><strong>#<?= $pid ?></strong></td>
                        <td>
                            <div class="adm-strong" style="color:#1d4ed8">
                                <?= H::e((string)($row['reference_no'] ?? $row['matched_order_no'] ?? '-')) ?>
                            </div>
                            <?php if (!empty($row['matched_order_no']) || $curOrderId > 0): ?>
                                <div class="adm-small" style="color:#15803d">
                                    ผูกแล้ว: <?= H::e((string)($row['matched_order_no'] ?? ('#' . $curOrderId))) ?> (order_id: <?= $curOrderId ?>)
                                </div>
                                <div class="adm-muted adm-small">
                                    สถานะ Order: <strong><?= H::e($curStatusLbl) ?></strong>
                                </div>
                            <?php else: ?>
                                <div class="adm-small" style="color:#b91c1c">ยังไม่ได้ผูก order_id</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="adm-strong"><?= H::e((string)($row['payer_name'] ?? '-')) ?></div>
                            <div class="adm-small"><?= H::e((string)($row['company_name'] ?? '-')) ?></div>
                            <div class="adm-muted adm-small"><?= H::e((string)($row['payer_phone'] ?? '-')) ?></div>
                        </td>
                        <td>
                            <div><?= H::e((string)($row['bank_name'] ?? '-')) ?></div>
                            <div class="adm-muted adm-small">
                                <?= H::e((string)($row['transfer_datetime_text'] ?? $row['transfer_date'] ?? '-')) ?>
                            </div>
                        </td>
                        <td style="text-align:right">
                            <div class="adm-strong" style="color:<?= $amtMismatch ? '#b91c1c' : '#0f172a' ?>">
                                <?= number_format($paidAmt, 2) ?>
                            </div>
                            <?php if ($ordTotal !== null): ?>
                                <div class="adm-small" style="color:<?= $amtMismatch ? '#b91c1c' : '#64748b' ?>">
                                    Order: <?= number_format($ordTotal, 2) ?><?= $amtMismatch ? ' (ไม่ตรง)' : '' ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($slipRel !== ''): ?>
                                <a class="adm-btn is-light is-sm" href="<?= H::e(H::url($slipRel)) ?>" target="_blank">ดูสลิป</a>
                            <?php else: ?>
                                <span class="adm-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>