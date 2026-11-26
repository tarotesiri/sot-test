<?php
/**
 * ============================================================================
 * Project Name : SIAM Online Training (SOT LMS)
 * File Name    : my-orders.php
 * Module       : Auth View (My Orders)
 * Description  : หน้า "คำสั่งซื้อของฉัน" แสดงชื่อหลักสูตรเต็ม บรรทัดล่างมีวันที่
 *                อยู่บรรทัดเดียวกับจำนวนผู้เข้าอบรมและราคาต่อหน่วย
 * ============================================================================
 */

declare(strict_types=1);

$orders       = is_array($orders ?? null) ? $orders : [];
$statusLabels = is_array($statusLabels ?? null) ? $statusLabels : [];

$defaultStatusLabels = [
    'pending'               => 'รอการชำระ',
    'awaiting_verification' => 'กำลังตรวจสอบ',
    'pending_approval'      => 'รอการอนุมัติ',
    'paid'                  => 'ชำระเงินแล้ว',
    'cancelled'             => 'ยกเลิก',
    'refunded'              => 'คืนเงิน',
];
?>

<div style="max-width: 1200px; margin: 30px auto; padding: 0 15px;">
    <div style="display: flex; flex-wrap: wrap; gap: 20px;">

        <!-- เมนูด้านซ้าย (Sidebar) - ไฮไลท์ "คำสั่งซื้อของฉัน" -->
        <div style="flex: 1; min-width: 250px; max-width: 280px;">
            <div style="background: #fff; border: 1px solid #dee2e6; border-radius: 6px; overflow: hidden; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);">
                <a href="<?= e(url('dashboard')) ?>" style="display: block; padding: 12px 20px; color: #212529; text-decoration: none;">ภาพรวมระบบ (Overview)</a>
                <a href="<?= e(url('my-orders')) ?>" style="display: block; padding: 12px 20px; background: #0d6efd; color: #fff; text-decoration: none; font-weight: 500; border-top: 1px solid #dee2e6;">คำสั่งซื้อของฉัน</a>
                <a href="<?= e(url('my-courses')) ?>" style="display: block; padding: 12px 20px; color: #212529; text-decoration: none; border-top: 1px solid #dee2e6;">หลักสูตรของฉัน</a>
                <a href="<?= e(url('profile')) ?>" style="display: block; padding: 12px 20px; color: #212529; text-decoration: none; border-top: 1px solid #dee2e6;">แก้ไขข้อมูลส่วนตัว</a>
                <a href="<?= e(url('auth/logout')) ?>" style="display: block; padding: 12px 20px; color: #dc3545; text-decoration: none; border-top: 1px solid #dee2e6;">ออกจากระบบ</a>
            </div>
        </div>

        <!-- พื้นที่แสดงข้อมูลฝั่งขวา (Main Content) -->
        <div style="flex: 3; min-width: 300px;">
            <?php if (!empty($_SESSION['flash_success'])): ?>
                <div style="background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;padding:12px 16px;border-radius:6px;margin-bottom:16px;font-weight:600;">
                    <?= htmlspecialchars((string)$_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?>
                </div>
                <?php unset($_SESSION['flash_success']); ?>
            <?php endif; ?>

            <div style="background: #fff; border: 1px solid #dee2e6; border-radius: 6px; padding: 25px; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
                    <div>
                        <h3 style="margin:0;font-weight:700;color:#212529;">คำสั่งซื้อของฉัน</h3>
                        <div style="font-size:0.85rem;color:#6c757d;margin-top:4px;">คลิกที่ <strong>เลขที่คำสั่งซื้อ</strong> เพื่อดูหลักสูตรที่สั่งซื้อ และคลิกที่ <strong>หลักสูตร</strong> เพื่อดูรายชื่อผู้เข้าอบรม</div>
                    </div>
                    <a href="<?= e(url('cart')) ?>" style="display:inline-block;padding:8px 14px;border-radius:6px;background:#0d6efd;color:#fff;text-decoration:none;font-size:0.875rem;font-weight:500;">สั่งซื้ออบรมเพิ่ม</a>
                </div>

                <?php if (empty($orders)): ?>
                    <div style="text-align:center;padding:36px 16px;color:#6c757d;border-top:1px solid #dee2e6;">
                        ยังไม่มีรายการคำสั่งซื้อในระบบ
                    </div>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table style="width:100%;border-collapse:collapse;font-size:0.9rem;">
                            <thead>
                                <tr style="background:#f8f9fa;border-top:1px solid #dee2e6;border-bottom:2px solid #dee2e6;text-align:left;">
                                    <th style="padding:10px 12px;">คำสั่งซื้อ</th>
                                    <th style="padding:10px 12px;">ใบเสนอราคา / ใบแจ้งค่าบริการ</th>
                                    <th style="padding:10px 12px;text-align:right;">ยอดสุทธิ</th>
                                    <th style="padding:10px 12px;text-align:center;">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $idx => $ord): ?>
                                    <?php
                                    $orderId     = (int)($ord['id'] ?? 0);
                                    $detailRowId = 'order-detail-' . $idx . '-' . $orderId;
                                    $grandTotal  = (float)($ord['grand_total'] ?? 0);
                                    $hasPaidDue  = round($grandTotal, 2) > 0.0;
                                    $rawStatus   = trim((string)($ord['payment_status'] ?? 'pending'));
                                    $orderNo     = trim((string)($ord['order_no'] ?? ''));
                                    $quoNo       = trim((string)($ord['quotation_no'] ?? ''));
                                    $iveNo       = trim((string)($ord['invoice_no'] ?? ''));
                                    $items       = is_array($ord['items'] ?? null) ? $ord['items'] : [];

                                    $isOrderFullyApproved = !empty($ord['_is_fully_approved']);
                                    if (!$isOrderFullyApproved && $rawStatus === 'paid' && !empty($items)) {
                                        $allApproved = true;
                                        foreach ($items as $chkIt) {
                                            if (strtolower(trim((string)($chkIt['batch_approval_status'] ?? ''))) !== 'approved') {
                                                $allApproved = false;
                                                break;
                                            }
                                        }
                                        $isOrderFullyApproved = $allApproved;
                                    }

                                    $canPay = in_array($rawStatus, ['pending'], true) && $hasPaidDue;

                                    $statusPalette = [
                                        'pending'               => ['label' => 'รอการชำระ',     'bg' => '#fef9c3', 'color' => '#854d0e'],
                                        'awaiting_verification' => ['label' => 'กำลังตรวจสอบ', 'bg' => '#e0f2fe', 'color' => '#075985'],
                                        'pending_approval'      => ['label' => 'รอการอนุมัติ',  'bg' => '#ffedd5', 'color' => '#9a3412'],
                                        'paid'                  => ['label' => 'ชำระเงินแล้ว',  'bg' => '#dcfce7', 'color' => '#166534'],
                                        'cancelled'             => ['label' => 'ยกเลิก',        'bg' => '#fee2e2', 'color' => '#991b1b'],
                                        'refunded'              => ['label' => 'คืนเงิน',        'bg' => '#f3e8ff', 'color' => '#6b21a8'],
                                    ];

                                    $stMeta = $statusPalette[$rawStatus] ?? [
                                        'label' => $defaultStatusLabels[$rawStatus] ?? $rawStatus,
                                        'bg'    => '#f1f5f9',
                                        'color' => '#334155',
                                    ];

                                    if (isset($statusLabels[$rawStatus])) {
                                        $customLabel = $statusLabels[$rawStatus];
                                        if (is_array($customLabel)) {
                                            if (!empty($customLabel['label']) && is_scalar($customLabel['label'])) {
                                                $stMeta['label'] = trim((string)$customLabel['label']);
                                            }
                                            if (!empty($customLabel['bg']) && is_string($customLabel['bg'])) {
                                                $stMeta['bg'] = $customLabel['bg'];
                                            }
                                            if (!empty($customLabel['color']) && is_string($customLabel['color'])) {
                                                $stMeta['color'] = $customLabel['color'];
                                            }
                                        } elseif (is_scalar($customLabel) && trim((string)$customLabel) !== '') {
                                            $stMeta['label'] = trim((string)$customLabel);
                                        }
                                    }

                                    if ($rawStatus === 'paid' && $isOrderFullyApproved) {
                                        $stMeta['label'] = 'อนุมัติแล้ว';
                                        $stMeta['bg']    = '#dcfce7';
                                        $stMeta['color'] = '#166534';
                                    }
                                    ?>
                                    <tr style="border-bottom:1px solid #dee2e6;">
                                        <td style="padding:10px 12px;vertical-align:top;">
                                            <button type="button"
                                                    class="js-toggle-order"
                                                    data-target="<?= htmlspecialchars($detailRowId, ENT_QUOTES, 'UTF-8') ?>"
                                                    aria-expanded="false"
                                                    style="background:none;border:none;padding:0;margin:0;cursor:pointer;text-align:left;display:inline-flex;align-items:center;gap:6px;color:#0d6efd;font-weight:700;font-size:0.92rem;">
                                                <span class="sot-arrow" style="display:inline-block;font-size:0.75rem;transition:transform 0.15s;">▶</span>
                                                <span><?= e($orderNo !== '' ? $orderNo : '-') ?></span>
                                            </button>
                                            <div style="font-size:0.78rem;color:#6c757d;margin-top:2px;padding-left:14px;"><?= e((string)($ord['created_at'] ?? '')) ?></div>
                                        </td>
                                        <td style="padding:10px 12px;vertical-align:top;">
                                            <?php if ($hasPaidDue): ?>
                                                <?php if ($quoNo !== ''): ?>
                                                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:5px;">
                                                        <span style="color:#6c757d;font-size:0.82rem;">QUO:</span>
                                                        <strong style="color:#212529;"><?= e($quoNo) ?></strong>
                                                        <?php if ($orderId > 0): ?>
                                                            <a href="<?= e(url('my-orders/document?order_id=' . $orderId . '&type=quo')) ?>" target="_blank" style="display:inline-block;padding:2px 7px;border-radius:4px;border:1px solid #ced4da;background:#f8f9fa;color:#212529;text-decoration:none;font-size:0.74rem;font-weight:600;">ดาวน์โหลด</a>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($iveNo !== ''): ?>
                                                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                                        <span style="color:#6c757d;font-size:0.82rem;">IVE:</span>
                                                        <strong style="color:#0d6efd;"><?= e($iveNo) ?></strong>
                                                        <?php if ($orderId > 0): ?>
                                                            <a href="<?= e(url('my-orders/document?order_id=' . $orderId . '&type=ive')) ?>" target="_blank" style="display:inline-block;padding:2px 7px;border-radius:4px;border:1px solid #badbcc;background:#d1e7dd;color:#0f5132;text-decoration:none;font-size:0.74rem;font-weight:600;">ดาวน์โหลด</a>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div style="font-size:0.8rem;color:#15803d;font-weight:600;line-height:1.45;">
                                                    -
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding:10px 12px;text-align:right;font-weight:700;vertical-align:top;"><?= ($grandTotal>0?number_format($grandTotal, 2).' บาท':'หลักสูตรฟรี') ?></td>
                                        <td style="padding:10px 12px;text-align:center;vertical-align:top;">
                                            <div>
                                                <span style="display:inline-block;padding:3px 10px;border-radius:999px;font-size:0.78rem;font-weight:600;background:<?= e($stMeta['bg']) ?>;color:<?= e($stMeta['color']) ?>;">
                                                    <?= e($stMeta['label']) ?>
                                                </span>
                                            </div>
                                            <?php if ($canPay && $orderId > 0): ?>
                                                <div style="margin-top:6px;">
                                                    <a href="<?= e(url('payment?order_id=' . $orderId)) ?>"
                                                       style="display:inline-block;padding:3px 10px;border-radius:5px;background:#198754;color:#fff;text-decoration:none;font-size:0.76rem;font-weight:600;">
                                                        แจ้งชำระ
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- แถวขยาย Detail หลักสูตรในคำสั่งซื้อ -->
                                    <tr id="<?= htmlspecialchars($detailRowId, ENT_QUOTES, 'UTF-8') ?>" style="display:none;background:#f8f9fa;border-bottom:1px solid #dee2e6;">
                                        <td colspan="4" style="padding:14px 16px;">
                                            <div style="font-weight:700;font-size:0.86rem;color:#212529;margin-bottom:8px;">
                                                หลักสูตรที่สั่งซื้อใน <?= e($orderNo !== '' ? $orderNo : '-') ?> (<?= count($items) ?> รายการ)
                                            </div>

                                            <?php if (empty($items)): ?>
                                                <div style="font-size:0.84rem;color:#6c757d;">ไม่พบข้อมูลรายการหลักสูตร</div>
                                            <?php else: ?>
                                                <div style="display:flex;flex-direction:column;gap:8px;">
                                                    <?php foreach ($items as $itemIdx => $it): ?>
                                                        <?php
                                                        $itemId      = (int)($it['id'] ?? 0);
                                                        $attPanelId  = 'att-panel-' . $idx . '-' . $itemIdx . '-' . $itemId;
                                                        $attendees   = is_array($it['attendees'] ?? null) ? $it['attendees'] : [];

                                                        $courseTitle = trim((string)($it['course_title'] ?? $it['course_name'] ?? ''));
                                                        if ($courseTitle === '') {
                                                            $courseTitle = 'หลักสูตร #' . (int)($it['course_id'] ?? 0);
                                                        }

                                                        $itemBadge = null;
                                                        if (in_array($rawStatus, ['paid', 'pending_approval'], true)) {
                                                            $batchApprovalStatus = strtolower(trim((string)($it['batch_approval_status'] ?? '')));
                                                            if ($rawStatus === 'paid' && $batchApprovalStatus === 'approved') {
                                                                $itemBadge = [
                                                                    'label' => 'อนุมัติแล้ว',
                                                                    'bg'    => '#dcfce7',
                                                                    'color' => '#166534',
                                                                ];
                                                            } else {
                                                                $itemBadge = [
                                                                    'label' => 'รออนุมัติ',
                                                                    'bg'    => '#ffedd5',
                                                                    'color' => '#9a3412',
                                                                ];
                                                            }
                                                        }

                                                        $rawDateText = trim((string)($it['date_text'] ?? $it['start_date'] ?? $it['training_date'] ?? $it['session_date'] ?? ''));
                                                        $decodedDateText = trim(html_entity_decode($rawDateText, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                                                        $attendeeCount = !empty($attendees)
                                                            ? count($attendees)
                                                            : max(1, (int)($it['quantity'] ?? 1));

                                                        $unitPrice = (float)($it['unit_price'] ?? 0);
                                                        if ($unitPrice <= 0 && (float)($it['subtotal'] ?? 0) > 0 && $attendeeCount > 0) {
                                                            $unitPrice = (float)$it['subtotal'] / $attendeeCount;
                                                        }
                                                        ?>
                                                        <div style="background:#fff;border:1px solid #dee2e6;border-radius:6px;overflow:hidden;">
                                                            <button type="button"
                                                                    class="js-toggle-attendees"
                                                                    data-target="<?= htmlspecialchars($attPanelId, ENT_QUOTES, 'UTF-8') ?>"
                                                                    aria-expanded="false"
                                                                    style="width:100%;background:#fff;border:none;padding:10px 14px;cursor:pointer;display:flex;align-items:flex-start;gap:8px;text-align:left;">
                                                                <span class="sot-item-arrow" style="font-size:0.75rem;color:#0d6efd;margin-top:3px;flex-shrink:0;transition:transform 0.15s;">▶</span>
                                                                <div style="flex:1;min-width:0;">
                                                                    <div style="font-weight:700;color:#212529;font-size:0.9rem;line-height:1.45;">
                                                                        <span>หลักสูตร "<?= e($courseTitle) ?>"</span>
                                                                        <?php if ($itemBadge !== null): ?>
                                                                            <span style="display:inline-block;padding:2px 8px;border-radius:999px;font-size:0.74rem;font-weight:600;white-space:nowrap;vertical-align:middle;margin-left:6px;background:<?= e($itemBadge['bg']) ?>;color:<?= e($itemBadge['color']) ?>;">
                                                                                <?= e($itemBadge['label']) ?>
                                                                            </span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-top:4px;font-size:0.82rem;color:#495057;">
                                                                        <div style="line-height:1.4;">
                                                                            <?= $decodedDateText !== '' ? e($decodedDateText) : '' ?>
                                                                        </div>
                                                                        <div style="white-space:nowrap;">
                                                                            <span>ระบุจำนวนผู้เข้าอบรม <?= $attendeeCount ?> ท่าน</span>
                                                                            <span style="margin:0 6px;">·</span>
                                                                            <?php if (!$hasPaidDue && $unitPrice <= 0): ?>
                                                                                <strong style="color:#15803d;">ฟรีไม่มีค่าใช้จ่าย</strong>
                                                                            <?php else: ?>
                                                                                <strong style="color:#212529;">ราคา <?= number_format($unitPrice, 2) ?> บาท/ท่าน</strong>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </button>

                                                            <!-- กล่องขยายรายชื่อผู้เข้าอบรมในหลักสูตร -->
                                                            <div id="<?= htmlspecialchars($attPanelId, ENT_QUOTES, 'UTF-8') ?>" style="display:none;border-top:1px solid #e9ecef;background:#fcfcfd;padding:10px 14px;">
                                                                <div style="font-size:0.8rem;font-weight:700;color:#495057;margin-bottom:6px;">
                                                                    รายชื่อผู้เข้าอบรมที่ระบุในหลักสูตรนี้
                                                                </div>
                                                                <?php if (empty($attendees)): ?>
                                                                    <div style="font-size:0.82rem;color:#6c757d;">ยังไม่มีการระบุรายชื่อผู้เข้าอบรม</div>
                                                                <?php else: ?>
                                                                    <table style="width:100%;border-collapse:collapse;font-size:0.82rem;background:#fff;">
                                                                        <thead>
                                                                            <tr style="background:#f1f3f5;border-bottom:1px solid #dee2e6;text-align:left;">
                                                                                <th style="padding:6px 8px;width:42px;">#</th>
                                                                                <th style="padding:6px 8px;">ชื่อ - นามสกุล</th>
                                                                                <th style="padding:6px 8px;">อีเมล / โทรศัพท์</th>
                                                                                <th style="padding:6px 8px;text-align:center;">รหัส PIN</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <?php foreach ($attendees as $aIdx => $att): ?>
                                                                                <?php
                                                                                $fullName = trim(
                                                                                    (string)($att['prefix'] ?? '') . ' '
                                                                                    . (string)($att['first_name'] ?? '') . ' '
                                                                                    . (string)($att['last_name'] ?? '')
                                                                                );
                                                                                $contactInfo = trim(
                                                                                    (string)($att['email'] ?? '')
                                                                                    . (((string)($att['email'] ?? '') !== '' && (string)($att['phone'] ?? '') !== '') ? ' · ' : '')
                                                                                    . (string)($att['phone'] ?? '')
                                                                                );
                                                                                ?>
                                                                                <tr style="border-bottom:1px solid #edf2f7;">
                                                                                    <td style="padding:6px 8px;color:#6c757d;"><?= $aIdx + 1 ?></td>
                                                                                    <td style="padding:6px 8px;font-weight:600;color:#212529;"><?= e($fullName !== '' ? $fullName : '-') ?></td>
                                                                                    <td style="padding:6px 8px;color:#495057;"><?= e($contactInfo !== '' ? $contactInfo : '-') ?></td>
                                                                                    <td style="padding:6px 8px;text-align:center;font-family:monospace;"><?= e((string)($att['pin_code'] ?? '-')) ?></td>
                                                                                </tr>
                                                                            <?php endforeach; ?>
                                                                        </tbody>
                                                                    </table>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('click', function (ev) {
    var orderBtn = ev.target.closest('.js-toggle-order');
    if (orderBtn) {
        var rowId = orderBtn.getAttribute('data-target');
        var row = rowId ? document.getElementById(rowId) : null;
        if (!row) return;
        var isOpen = row.style.display !== 'none';
        row.style.display = isOpen ? 'none' : 'table-row';
        orderBtn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        var arrow = orderBtn.querySelector('.sot-arrow');
        if (arrow) {
            arrow.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(90deg)';
        }
        return;
    }

    var attBtn = ev.target.closest('.js-toggle-attendees');
    if (attBtn) {
        var panelId = attBtn.getAttribute('data-target');
        var panel = panelId ? document.getElementById(panelId) : null;
        if (!panel) return;
        var panelOpen = panel.style.display !== 'none';
        panel.style.display = panelOpen ? 'none' : 'block';
        attBtn.setAttribute('aria-expanded', panelOpen ? 'false' : 'true');
        var itemArrow = attBtn.querySelector('.sot-item-arrow');
        if (itemArrow) {
            itemArrow.style.transform = panelOpen ? 'rotate(0deg)' : 'rotate(90deg)';
        }
    }
});
</script>