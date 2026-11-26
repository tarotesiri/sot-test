<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training (SOT LMS)
 * File Name    : detail.php
 * Description  : Admin Order Detail, Locked Tax Type, Single-Row
 *                Coordinator Info, Cascading Thai Address (checkout.php
 *                style), 4-Line Financial Summary & WHT Options
 *                (0,1,2,3,5,%), Quo/Inv Print Buttons Beside Save,
 *                Green/Red Flash Messages, Sanitized Address & Clean Log
 * ====================================================================
 */
declare(strict_types=1);

use Modules\Admin\Support\AdminHelper as H;
use Modules\Payment\Services\PaymentApprovalService;

$order               = is_array($order ?? null) ? $order : [];
$companies           = is_array($companies ?? null) ? $companies : [];
$items               = is_array($items ?? null) ? $items : [];
$attendeesByItem     = is_array($attendeesByItem ?? null) ? $attendeesByItem : [];
$payments            = is_array($payments ?? null) ? $payments : [];
$logs                = is_array($logs ?? null) ? $logs : [];
$paymentStatusLabels = is_array($paymentStatusLabels ?? null) ? $paymentStatusLabels : [
    'pending'               => 'รอการชำระ',
    'awaiting_verification' => 'กำลังตรวจสอบ',
    'pending_approval'      => 'รออนุมัติ',
    'paid'                  => 'ชำระเงินแล้ว',
    'cancelled'             => 'ยกเลิก',
    'refunded'              => 'คืนเงิน',
];

$pickAttField = static function (array $row, array $keys, string $default = '-'): string {
    foreach ($keys as $k) {
        if (isset($row[$k]) && is_scalar($row[$k]) && trim((string)$row[$k]) !== '') {
            return trim((string)$row[$k]);
        }
    }
    return $default;
};

$formatRateLabel = static function (float $rate): string {
    $formatted = number_format($rate, 2, '.', '');
    return rtrim(rtrim($formatted, '0'), '.');
};

$sanitizeStreetPart = static function (
    string $street,
    string $district,
    string $amphoe,
    string $province,
    string $zipcode
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

    $clean = preg_replace(
        '/(?<!\pL)(?:ต\.|ตำบล|แขวง|อ\.|อำเภอ|เขต|จ\.|จังหวัด)\s*(?=(?:\s|$|(?:ต\.|ตำบล|แขวง|อ\.|อำเภอ|เขต|จ\.|จังหวัด)))/u',
        ' ',
        $clean
    ) ?? $clean;
    $clean = preg_replace('/\s{2,}/u', ' ', $clean) ?? $clean;

    return trim($clean, " \t\n\r\0\x0B,");
};

// รวบรวมข้อความแจ้งเตือน (msg) สีเขียว (สำเร็จ) / สีแดง (ผิดพลาด/ไม่สำเร็จ)
$alertMessages = [];
$flashKeysSuccess = ['success', 'flash_success', 'adm_success', '_flash_success', 'msg_success', 'ok'];
$flashKeysError   = ['error', 'flash_error', 'adm_error', '_flash_error', 'msg_error', 'danger'];

foreach ($flashKeysSuccess as $k) {
    if (!empty($$k) && is_scalar($$k)) {
        $alertMessages[] = ['type' => 'success', 'text' => (string)$$k];
    }
    if (isset($_GET[$k]) && is_scalar($_GET[$k]) && trim((string)$_GET[$k]) !== '') {
        $alertMessages[] = ['type' => 'success', 'text' => trim((string)$_GET[$k])];
    }
}

foreach ($flashKeysError as $k) {
    if (!empty($$k) && is_scalar($$k)) {
        $alertMessages[] = ['type' => 'error', 'text' => (string)$$k];
    }
    if (isset($_GET[$k]) && is_scalar($_GET[$k]) && trim((string)$_GET[$k]) !== '') {
        $alertMessages[] = ['type' => 'error', 'text' => trim((string)$_GET[$k])];
    }
}

$sessionBuckets = [
    &$_SESSION,
    &$_SESSION['_flash'],
    &$_SESSION['flash'],
    &$_SESSION['admin_flash'],
];

foreach ($sessionBuckets as &$bucket) {
    if (!is_array($bucket)) {
        continue;
    }
    foreach ($flashKeysSuccess as $k) {
        if (!empty($bucket[$k]) && is_scalar($bucket[$k])) {
            $alertMessages[] = ['type' => 'success', 'text' => (string)$bucket[$k]];
            unset($bucket[$k]);
        }
    }
    foreach ($flashKeysError as $k) {
        if (!empty($bucket[$k]) && is_scalar($bucket[$k])) {
            $alertMessages[] = ['type' => 'error', 'text' => (string)$bucket[$k]];
            unset($bucket[$k]);
        }
    }
    foreach (['message', 'flash_message', 'msg', 'notice'] as $k) {
        if (!empty($bucket[$k]) && is_scalar($bucket[$k])) {
            $rawType = strtolower((string)($bucket['type'] ?? $bucket['msg_type'] ?? $bucket['status'] ?? ''));
            $mType = in_array($rawType, ['error', 'err', 'danger', 'fail', 'failed'], true) ? 'error' : 'success';
            $alertMessages[] = ['type' => $mType, 'text' => (string)$bucket[$k]];
            unset($bucket[$k]);
        }
    }
}
unset($bucket);

if (isset($_GET['saved']) && (string)$_GET['saved'] === '1' && $alertMessages === []) {
    $alertMessages[] = ['type' => 'success', 'text' => 'บันทึกข้อมูลคำสั่งซื้อเรียบร้อยแล้ว'];
}

$oid         = (int)($order['id'] ?? 0);
$orderNo     = trim((string)($order['order_no'] ?? '#' . $oid));
$quotationNo = trim((string)($order['quotation_no'] ?? 'QUO-' . ltrim($orderNo, '#')));
$invoiceNo   = trim((string)($order['invoice_no'] ?? 'IVE-' . ltrim($orderNo, '#')));
$createdAt   = trim((string)($order['created_at'] ?? ''));
$createdText = $createdAt !== '' ? date('d/m/Y H:i', strtotime($createdAt)) : '-';
$curPaySt    = trim((string)($order['payment_status'] ?? 'pending'));
$curPayLabel = $paymentStatusLabels[$curPaySt] ?? $curPaySt;

$hasSavedSubtotal = array_key_exists('_total_subtotal', $order) || array_key_exists('total_subtotal', $order);
$totalSubtotal    = round((float)($order['_total_subtotal'] ?? $order['total_subtotal'] ?? 0.0), 2);
$vatRate          = round((float)($order['_vat_rate'] ?? $order['vat_rate'] ?? 7.0), 2);
$totalVat         = array_key_exists('total_vat', $order) && $order['total_vat'] !== null
    ? round((float)$order['total_vat'], 2)
    : round((float)($order['_total_vat'] ?? ($totalSubtotal * ($vatRate / 100))), 2);
$whtRate          = round((float)($order['_wht_rate'] ?? $order['wht_rate'] ?? 0.0), 2);
$totalWht         = array_key_exists('total_wht', $order) && $order['total_wht'] !== null
    ? round((float)$order['total_wht'], 2)
    : round((float)($order['_total_wht'] ?? ($totalSubtotal * ($whtRate / 100))), 2);
$grandTotal       = ($hasSavedSubtotal && array_key_exists('grand_total', $order) && $order['grand_total'] !== null)
    ? round((float)$order['grand_total'], 2)
    : round((float)($order['_grand_total'] ?? ($totalSubtotal + $totalVat - $totalWht)), 2);

$canApproveBatch = PaymentApprovalService::canApproveBatchForOrder($curPaySt, $grandTotal);

$whtPresetRates = [0, 1, 2, 3, 5];
$matchedPreset  = null;
foreach ($whtPresetRates as $preset) {
    if (abs($whtRate - (float)$preset) < 0.005) {
        $matchedPreset = (string)$preset;
        break;
    }
}
$whtSelectVal = $matchedPreset !== null ? $matchedPreset : 'custom';

$coordName  = (string)($order['_coordinator_name'] ?? $order['customer_name'] ?? '');
$coordPhone = (string)($order['_coordinator_phone'] ?? $order['customer_phone'] ?? '');
$coordEmail = (string)($order['_coordinator_email'] ?? $order['customer_email'] ?? '');
$otherNote  = (string)($order['_other_note'] ?? $order['special_requirements'] ?? '');

$taxType    = (string)($order['_tax_type'] ?? 'corporate');
if (!in_array($taxType, ['corporate', 'personal'], true)) {
    $taxType = 'corporate';
}
$compName   = (string)($order['_company_name'] ?? '');
$branch     = (string)($order['_branch'] ?? '');
$taxId      = (string)($order['_tax_id'] ?? '');
$poNumber   = (string)($order['_po_number'] ?? '');
$province   = trim((string)($order['_province'] ?? ''));
$amphoe     = trim((string)($order['_amphoe'] ?? ''));
$district   = trim((string)($order['_district'] ?? ''));
$zipcode    = trim((string)($order['_zipcode'] ?? ''));
$rawStreet  = (string)($order['_street_address'] ?? $order['address'] ?? '');
$streetAddr = $sanitizeStreetPart($rawStreet, $district, $amphoe, $province, $zipcode);

$printQuoUrl = H::url('cart/document') . '?order_id=' . $oid . '&type=quo';
$printInvUrl = H::url('cart/document') . '?order_id=' . $oid . '&type=ive';
?>

<div class="adm-page-head">
    <div>
        <h1 style="margin:0">รายละเอียดคำสั่งซื้อ <?= H::e($orderNo) ?></h1>
        <div class="adm-muted adm-small" style="margin-top:4px">
            วันที่สั่งซื้อ: <?= H::e($createdText) ?>
            <?php if ($quotationNo !== ''): ?>
                · ใบเสนอราคา: <strong><?= H::e($quotationNo) ?></strong>
            <?php endif; ?>
            · ใบแจ้งหนี้: <strong><?= H::e($invoiceNo) ?></strong>
        </div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a class="adm-btn is-light" href="<?= H::e(H::url('admin/orders')) ?>">← กลับหน้ารายการคำสั่งซื้อ</a>
    </div>
</div>

<!-- ข้อความแจ้งผลการบันทึก สีเขียว (สำเร็จ) / สีแดง (ไม่สำเร็จ) -->
<?php if ($alertMessages !== []): ?>
    <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:14px">
        <?php foreach ($alertMessages as $msgItem): ?>
            <?php $isErr = ($msgItem['type'] === 'error'); ?>
            <div style="padding:10px 14px;border-radius:8px;font-size:0.9rem;font-weight:700;border:1px solid <?= $isErr ? '#f87171' : '#4ade80' ?>;background:<?= $isErr ? '#fef2f2' : '#f0fdf4' ?>;color:<?= $isErr ? '#991b1b' : '#166534' ?>">
                <?= $isErr ? '✕ บันทึกไม่สำเร็จ: ' : '✓ บันทึกสำเร็จ: ' ?><?= H::e($msgItem['text']) ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<div id="saveStatusBanner" style="display:none;padding:10px 14px;border-radius:8px;font-size:0.9rem;font-weight:700;margin-bottom:14px"></div>

<!-- แบ่งซ้ายกว้าง ขวาแคบลง -->
<div style="display:grid;grid-template-columns:minmax(430px,1.65fr) minmax(290px,0.95fr);gap:16px;margin-bottom:18px;align-items:start">
    <!-- กล่องซ้าย: ข้อมูลผู้ประสานงาน + ข้อมูลบริษัท / ข้อมูลออกใบเสร็จ -->
    <div class="adm-card" style="padding:16px">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:12px">
            <h2 style="font-size:1.05rem;margin:0">ข้อมูลผู้ประสานงาน &amp; ข้อมูลบริษัท / ออกใบเสร็จ</h2>
            <?php if ($companies !== []): ?>
                <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
                    <select id="companyRefreshSelect" class="adm-input" style="width:auto;max-width:240px;padding:4px 8px;font-size:0.82rem">
                        <?php foreach ($companies as $cOpt): ?>
                            <?php
                            $cOptId = (int)($cOpt['id'] ?? 0);
                            $cOptLabel = (string)($cOpt['company_name'] ?? 'บริษัท #' . $cOptId);
                            if (!empty($cOpt['branch'])) {
                                $cOptLabel .= ' (' . (string)$cOpt['branch'] . ')';
                            }
                            $cOptProv = trim((string)($cOpt['province'] ?? ''));
                            $cOptAmp  = trim((string)($cOpt['amphoe'] ?? ''));
                            $cOptDist = trim((string)($cOpt['district'] ?? ''));
                            $cOptZip  = trim((string)($cOpt['zipcode'] ?? ''));
                            $cOptAddr = $sanitizeStreetPart((string)($cOpt['address'] ?? ''), $cOptDist, $cOptAmp, $cOptProv, $cOptZip);
                            ?>
                            <option value="<?= $cOptId ?>"
                                    <?= ((int)($order['company_id'] ?? 0) === $cOptId) ? 'selected' : '' ?>
                                    data-tax-type="<?= H::e((string)($cOpt['tax_type'] ?? 'corporate')) ?>"
                                    data-company-name="<?= H::e((string)($cOpt['company_name'] ?? '')) ?>"
                                    data-branch="<?= H::e((string)($cOpt['branch'] ?? '')) ?>"
                                    data-tax-id="<?= H::e((string)($cOpt['tax_id'] ?? '')) ?>"
                                    data-address="<?= H::e($cOptAddr) ?>"
                                    data-province="<?= H::e($cOptProv) ?>"
                                    data-amphoe="<?= H::e($cOptAmp) ?>"
                                    data-district="<?= H::e($cOptDist) ?>"
                                    data-zipcode="<?= H::e($cOptZip) ?>"
                                    data-phone="<?= H::e((string)($cOpt['phone'] ?? '')) ?>"
                                    data-billing-email="<?= H::e((string)($cOpt['billing_email'] ?? '')) ?>">
                                <?= H::e($cOptLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="adm-btn is-light is-sm" onclick="refreshFromCompanies()" title="ดึงข้อมูลล่าสุดจาก sot_companies มาใส่ฟอร์ม (ไม่แก้ตาราง sot_companies)">
                        ↻ ดึงจาก companies
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <form id="orderEditForm" method="post" action="<?= H::e(H::url('admin/orders/' . $oid . '/update')) ?>">
            <?= H::csrf() ?>

            <!-- ประเภทผู้เสียภาษี: ล็อกไว้ ห้ามเปลี่ยน -->
            <div style="display:flex;gap:16px;align-items:center;margin-bottom:10px;font-size:0.9rem">
                <input type="hidden" id="hiddenTaxType" name="tax_type" value="<?= H::e($taxType) ?>">
                <label style="display:flex;align-items:center;gap:6px;cursor:not-allowed;opacity:0.8">
                    <input id="radTaxCorp" type="radio" name="_tax_type_locked" value="corporate" <?= $taxType === 'corporate' ? 'checked' : '' ?> disabled>
                    <span>บริษัท (นิติบุคคล)</span>
                </label>
                <label style="display:flex;align-items:center;gap:6px;cursor:not-allowed;opacity:0.8">
                    <input id="radTaxPers" type="radio" name="_tax_type_locked" value="personal" <?= $taxType === 'personal' ? 'checked' : '' ?> disabled>
                    <span>บุคคลทั่วไป</span>
                </label>
                <span class="adm-muted adm-small">(ล็อกตามคำสั่งซื้อ · เปลี่ยนไม่ได้)</span>
            </div>

            <!-- ข้อมูลผู้ประสานงาน: ชื่อ, อีเมล, เบอร์ ในบรรทัดเดียว -->
            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-bottom:10px">
                <div>
                    <label class="adm-small adm-strong">ชื่อผู้ประสานงาน</label>
                    <input id="inpCoordName" class="adm-input" type="text" name="customer_name" value="<?= H::e($coordName) ?>">
                </div>
                <div>
                    <label class="adm-small adm-strong">อีเมลผู้ประสานงาน</label>
                    <input id="inpCoordEmail" class="adm-input" type="email" name="customer_email" value="<?= H::e($coordEmail) ?>">
                </div>
                <div>
                    <label class="adm-small adm-strong">เบอร์โทรศัพท์</label>
                    <input id="inpCoordPhone" class="adm-input" type="text" name="customer_phone" value="<?= H::e($coordPhone) ?>">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:2fr 1fr;gap:10px;margin-bottom:10px">
                <div>
                    <label class="adm-small adm-strong">ชื่อบริษัท / ผู้เสียภาษี</label>
                    <input id="inpCompanyName" class="adm-input" type="text" name="company_name" value="<?= H::e($compName) ?>">
                </div>
                <div>
                    <label class="adm-small adm-strong">สาขา</label>
                    <input id="inpBranch" class="adm-input" type="text" name="branch" value="<?= H::e($branch) ?>" placeholder="เช่น สำนักงานใหญ่">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
                <div>
                    <label class="adm-small adm-strong">เลขประจำตัวผู้เสียภาษีอากร / เลขบัตร</label>
                    <input id="inpTaxId" class="adm-input" type="text" name="tax_id" value="<?= H::e($taxId) ?>">
                </div>
                <div>
                    <label class="adm-small adm-strong">PO Number</label>
                    <input id="inpPoNumber" class="adm-input" type="text" name="po_number" value="<?= H::e($poNumber) ?>" placeholder="ระบุเลขที่ใบสั่งซื้อ (ถ้ามี)">
                </div>
            </div>

            <div style="margin-bottom:10px">
                <label class="adm-small adm-strong">ที่อยู่ (เลขที่, อาคาร, หมู่, ซอย, ถนน)</label>
                <input id="inpAddress" class="adm-input" type="text" name="address" value="<?= H::e($streetAddr) ?>">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
                <div>
                    <label class="adm-small adm-strong">จังหวัด</label>
                    <select id="addr-province" name="province" class="adm-input" style="background:#fff">
                        <option value="">-- กำลังโหลดข้อมูลจังหวัด --</option>
                    </select>
                </div>
                <div>
                    <label class="adm-small adm-strong">อำเภอ / เขต</label>
                    <select id="addr-amphoe" name="amphoe" class="adm-input" disabled style="background:#f8fafc">
                        <option value="">-- เลือกจังหวัดก่อน --</option>
                    </select>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
                <div>
                    <label class="adm-small adm-strong">ตำบล / แขวง</label>
                    <select id="addr-district" name="district" class="adm-input" disabled style="background:#f8fafc">
                        <option value="">-- เลือกอำเภอ/เขตก่อน --</option>
                    </select>
                </div>
                <div>
                    <label class="adm-small adm-strong">รหัสไปรษณีย์</label>
                    <input id="addr-zipcode" class="adm-input" type="text" maxlength="5" name="zipcode" value="<?= H::e($zipcode) ?>" placeholder="กรอกรหัส 5 หลัก หรือเลือกตำบล">
                </div>
            </div>

            <div style="margin-bottom:12px">
                <label class="adm-small adm-strong">อื่นๆ (ความต้องการพิเศษเพิ่มเติมอื่นๆ)</label>
                <textarea id="inpOtherNote" class="adm-input" name="other_note" rows="2" placeholder="เช่น ที่อยู่ในการออกใบเสร็จ, ใบเสร็จแยกตามแผนก..."><?= H::e($otherNote) ?></textarea>
            </div>

            <!-- ซิงก์ค่า WHT ไปบันทึกใน sot_orders -->
            <input type="hidden" id="hiddenWhtMode" name="wht_mode" value="<?= H::e($whtSelectVal) ?>">
            <input type="hidden" id="hiddenWhtRate" name="wht_rate" value="<?= number_format($whtRate, 2, '.', '') ?>">
            <input type="hidden" id="hiddenWhtAmount" name="wht_amount" value="<?= number_format($totalWht, 2, '.', '') ?>">

            <!-- ปุ่มบันทึก + ปุ่ม Print ทั้ง Quo และ Inv ขนาดเล็กเรียบๆ ข้างปุ่มบันทึก -->
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                <button class="adm-btn is-primary" type="submit">บันทึกข้อมูล (ใช้เลขที่ใบแจ้งหนี้เดิม: <?= H::e($invoiceNo) ?>)</button>
                <a class="adm-btn is-light is-sm" href="<?= H::e($printQuoUrl) ?>" target="_blank" style="font-weight:500;color:#475569">
                    Print Quo (<?= H::e($quotationNo) ?>)
                </a>
                <a class="adm-btn is-light is-sm" href="<?= H::e($printInvUrl) ?>" target="_blank" style="font-weight:500;color:#475569">
                    Print Inv (<?= H::e($invoiceNo) ?>)
                </a>
            </div>
        </form>
    </div>

    <!-- แถบด้านขวา: สรุปยอดเงินคำสั่งซื้อ 4 บรรทัด -->
    <div style="display:flex;flex-direction:column;gap:14px">
        <div class="adm-card" style="padding:14px">
            <h2 style="font-size:1.02rem;margin-bottom:10px">สรุปยอดเงินคำสั่งซื้อ</h2>
            <div style="display:flex;flex-direction:column;gap:8px;font-size:0.88rem">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span class="adm-muted">ยอดเงินค่าอบรม</span>
                    <strong><?= number_format($totalSubtotal, 2) ?> บาท</strong>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span class="adm-muted">ภาษีมูลค่าเพิ่ม (Vat <?= H::e($formatRateLabel($vatRate)) ?>%)</span>
                    <span><?= number_format($totalVat, 2) ?> บาท</span>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;gap:6px">
                    <div style="display:flex;align-items:center;gap:4px;flex-wrap:wrap">
                        <span class="adm-muted">ภาษีหัก ณ ที่จ่าย (Wht <span id="displayWhtRate"><?= H::e($formatRateLabel($whtRate)) ?></span>%)</span>
                        <select id="whtModeSelect" class="adm-input" style="width:auto;padding:2px 5px;font-size:0.78rem" onchange="recalcOrderTotals()">
                            <?php foreach ($whtPresetRates as $preset): ?>
                                <option value="<?= $preset ?>" <?= $whtSelectVal === (string)$preset ? 'selected' : '' ?>><?= $preset ?>%</option>
                            <?php endforeach; ?>
                            <option value="custom" <?= $whtSelectVal === 'custom' ? 'selected' : '' ?>>ระบุเอง</option>
                        </select>
                        <input id="whtPercentInput"
                               type="number"
                               step="0.01"
                               min="0"
                               max="100"
                               class="adm-input"
                               style="width:58px;padding:2px 5px;font-size:0.78rem;text-align:right;<?= $whtSelectVal === 'custom' ? '' : 'display:none;' ?>"
                               value="<?= $whtSelectVal === 'custom' ? H::e($formatRateLabel($whtRate)) : '' ?>"
                               placeholder="%"
                               oninput="recalcOrderTotals()">
                        <span id="whtCustomPercentSuffix" class="adm-small adm-muted" style="<?= $whtSelectVal === 'custom' ? '' : 'display:none;' ?>">%</span>
                    </div>
                    <span id="displayWhtAmount">-<?= number_format($totalWht, 2) ?> บาท</span>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;padding-top:8px;border-top:1px solid #e2e8f0;font-size:1rem">
                    <strong>ยอดเงินสุทธิ</strong>
                    <strong id="displayGrandTotal" style="color:#0f766e"><?= number_format($grandTotal, 2) ?> บาท</strong>
                </div>
            </div>
        </div>

        <div class="adm-card" style="padding:14px">
            <h2 style="font-size:1.02rem;margin-bottom:10px">สถานะการชำระเงิน (ปัจจุบัน: <?= H::e($curPayLabel) ?>)</h2>
            <form method="post" action="<?= H::e(H::url('admin/orders/' . $oid . '/payment-status')) ?>"
                  style="display:flex;gap:8px;align-items:center">
                <?= H::csrf() ?>
                <input type="hidden" name="redirect_to" value="detail">
                <select class="adm-input" name="payment_status" style="flex:1">
                    <?php foreach ($paymentStatusLabels as $stKey => $stLbl): ?>
                        <option value="<?= H::e($stKey) ?>" <?= $curPaySt === $stKey ? 'selected' : '' ?>>
                            <?= H::e($stLbl) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="adm-btn is-primary" type="submit">บันทึกสถานะ</button>
            </form>
        </div>

        <div class="adm-card" style="padding:14px">
            <h2 style="font-size:1.02rem;margin-bottom:10px">สลิปแจ้งโอนในคำสั่งซื้อนี้ (<?= count($payments) ?> รายการ)</h2>
            <?php if ($payments === []): ?>
                <div class="adm-muted adm-small">ยังไม่มีสลิปแจ้งโอน</div>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:8px">
                    <?php foreach ($payments as $pay): ?>
                        <?php $pSlip = ltrim((string)($pay['slip_path'] ?? ''), '/'); ?>
                        <div style="border:1px solid #e2e8f0;border-radius:6px;padding:8px 10px;font-size:0.84rem">
                            <div><strong>#<?= (int)($pay['id'] ?? 0) ?></strong> | ยอด <?= number_format((float)($pay['transfer_amount'] ?? 0), 2) ?> บาท</div>
                            <div class="adm-muted adm-small"><?= H::e((string)($pay['bank_name'] ?? '-')) ?> · <?= H::e((string)($pay['transfer_datetime_text'] ?? $pay['transfer_date'] ?? '-')) ?></div>
                            <?php if ($pSlip !== ''): ?>
							    <?php
							    $baseSlipUrl = H::url('admin/slips/view');
							    $pSlipHref   = $baseSlipUrl . (str_contains($baseSlipUrl, '?') ? '&' : '?') . 'path=' . urlencode($pSlip);
							    ?>
							    <a class="adm-btn is-light is-sm" href="<?= H::e($pSlipHref) ?>" target="_blank" style="margin-top:4px">ดูสลิป</a>
							<?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- รายการหลักสูตร จำนวน ราคา วันที่อบรม และคลิกเพื่อขยายดูรายชื่อ -->
<div class="adm-card" style="padding:16px;margin-bottom:18px">
    <h2 style="font-size:1.05rem;margin-bottom:12px">รายการหลักสูตรและรายชื่อผู้เข้าอบรม</h2>

    <?php if ($items === []): ?>
        <div class="adm-muted">ไม่พบรายการหลักสูตรในคำสั่งซื้อนี้</div>
    <?php else: ?>
        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead>
                    <tr>
                        <th>ชื่อหลักสูตร / รุ่น / วันที่อบรม</th>
                        <th style="width:110px;text-align:center">จำนวน</th>
                        <th style="width:130px;text-align:right">ราคา</th>
                        <th style="width:190px;text-align:center">รายชื่อ / อนุมัติ</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <?php
                    $oiid       = (int)($it['id'] ?? 0);
                    $cTitle     = (string)($it['course_title'] ?? 'หลักสูตร');
                    $bNo        = (string)($it['batch_no'] ?? '');
                    $bDateText  = trim((string)($it['batch_date_text'] ?? $it['date_text'] ?? $it['schedule_text'] ?? ''));
                    $bTimeText  = trim((string)($it['batch_time_text'] ?? ''));
                    $appStatus  = trim((string)($it['batch_approval_status'] ?? 'pending'));
                    $isApproved = ($appStatus === 'approved');
                    $rosterList = $attendeesByItem[$oiid] ?? [];
                    $qtyVal     = count($rosterList) > 0 ? count($rosterList) : max(1, (int)($it['qty'] ?? $it['quantity'] ?? 1));
                    $priceVal   = (float)($it['subtotal'] ?? $it['line_total'] ?? $it['total_price'] ?? 0);
                    if ($priceVal <= 0) {
                        $unitP = (float)($it['unit_price'] ?? $it['price'] ?? 0);
                        $priceVal = $unitP * $qtyVal;
                    }
                    $detailRosterId = 'detail-roster-' . $oiid;
                    ?>
                    <tr>
                        <td>
                            <button type="button"
                                    onclick="toggleDetailRoster('<?= H::e($detailRosterId) ?>')"
                                    style="background:none;border:none;padding:0;text-align:left;cursor:pointer;font-weight:700;color:#0f172a">
                                <?= H::e($cTitle) ?>
                            </button>
                            <?php if ($bNo !== '' || $bDateText !== ''): ?>
                                <div class="adm-small" style="color:#1d4ed8;margin-top:2px">
                                    <?= $bNo !== '' ? ('รุ่น: ' . H::e($bNo)) : '' ?>
                                    <?= ($bNo !== '' && $bDateText !== '') ? ' · ' : '' ?>
                                    <?= $bDateText !== '' ? H::e($bDateText) : '' ?>
                                    <?= $bTimeText !== '' ? (' (' . H::e($bTimeText) . ')') : '' ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center">
                            <strong><?= $qtyVal ?></strong> คน
                        </td>
                        <td style="text-align:right">
                            <strong><?= number_format($priceVal, 2) ?></strong> บาท
                        </td>
                        <td style="text-align:center">
                            <div style="display:flex;flex-wrap:wrap;gap:6px;justify-content:center;align-items:center">
                                <button type="button" class="adm-btn is-light is-sm"
                                        onclick="toggleDetailRoster('<?= H::e($detailRosterId) ?>')">
                                    ▾ ดูรายชื่อ
                                </button>
                                <?php if ($isApproved): ?>
                                    <form method="post" action="<?= H::e(H::url('admin/orders/' . $oid . '/batches/' . $oiid . '/unapprove')) ?>"
                                          onsubmit="return confirm('ยืนยันยกเลิกอนุมัติรุ่นนี้? (ไม่ส่งอีเมล)')"
                                          style="margin:0">
                                        <?= H::csrf() ?>
                                        <input type="hidden" name="redirect_to" value="detail">
                                        <button class="adm-btn is-light is-sm" type="submit">
                                            ยกเลิกอนุมัติ
                                        </button>
                                    </form>
                                <?php elseif ($canApproveBatch): ?>
                                    <form method="post" action="<?= H::e(H::url('admin/orders/' . $oid . '/batches/' . $oiid . '/approve')) ?>"
                                          onsubmit="return confirm('ยืนยันอนุมัติรุ่นนี้และส่งอีเมล?')"
                                          style="margin:0">
                                        <?= H::csrf() ?>
                                        <input type="hidden" name="redirect_to" value="detail">
                                        <button class="adm-btn is-primary is-sm" type="submit">
                                            อนุมัติรุ่นนี้
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <tr id="<?= H::e($detailRosterId) ?>" style="display:none;background:#f8fafc">
                        <td colspan="4" style="padding:10px 14px">
                            <?php if ($rosterList === []): ?>
                                <div class="adm-muted adm-small">ไม่พบรายชื่อผู้เข้าอบรม</div>
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
                                    <?php foreach ($rosterList as $rIdx => $person): ?>
                                        <?php
                                        $prefix    = $pickAttField($person, ['prefix', 'prefix_name', 'title', 'part_prefix_name', 'usr_prefix']);
                                        $firstName = trim((string)($person['first_name'] ?? ''));
                                        $lastName  = trim((string)($person['last_name'] ?? ''));
                                        if ($firstName === '' && $lastName === '') {
                                            $full = trim((string)($person['full_name'] ?? $person['attendee_name'] ?? ''));
                                            if ($full !== '') {
                                                $parts = preg_split('/\s+/u', $full, 2) ?: [];
                                                $firstName = $parts[0] ?? '';
                                                $lastName  = $parts[1] ?? '';
                                            }
                                        }
                                        $email     = $pickAttField($person, ['email', 'attendee_email', 'part_email', 'usr_email']);
                                        $phone     = $pickAttField($person, ['phone', 'tel', 'mobile', 'part_phone', 'usr_phone']);
                                        $idCard    = $pickAttField($person, ['id_card', 'citizen_id', 'national_id', 'id_number', 'part_id_card', 'usr_id_card']);
                                        $position  = $pickAttField($person, ['position', 'job_title', 'job_position', 'role', 'part_position', 'usr_position']);
                                        $special   = $pickAttField($person, ['other_requirements', 'other_requirement', 'special_requirements', 'special_request', 'other_request', 'dietary', 'remark', 'note', 'part_other_requirements']);
                                        ?>
                                        <tr>
                                            <td><?= ($rIdx + 1) ?></td>
                                            <td><?= H::e($prefix) ?></td>
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
        </div>
    <?php endif; ?>
</div>

<!-- ประวัติการทำงาน (เหลือ เวลา และ รายละเอียด) -->
<div class="adm-card" style="padding:16px">
    <h2 style="font-size:1.05rem;margin-bottom:10px">ประวัติการดำเนินการ</h2>
    <div class="adm-table-wrap">
        <table class="adm-table">
            <thead>
                <tr>
                    <th style="width:170px">เวลา</th>
                    <th>รายละเอียด</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($logs === []): ?>
                <tr><td colspan="2" class="adm-empty">ยังไม่มีประวัติ</td></tr>
            <?php else: ?>
                <?php foreach ($logs as $lg): ?>
                    <tr>
                        <td class="adm-small"><?= H::e((string)($lg['created_at'] ?? '-')) ?></td>
                        <td class="adm-small"><?= H::e((string)($lg['reason'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const ORDER_TOTAL_SUBTOTAL = <?= json_encode($totalSubtotal, JSON_THROW_ON_ERROR) ?>;
const ORDER_TOTAL_VAT      = <?= json_encode($totalVat, JSON_THROW_ON_ERROR) ?>;

let detailThaiAddressDB = [];
let detailPopulateAmphoes = null;
let detailPopulateDistricts = null;

function showSaveBanner(type, message) {
    const banner = document.getElementById('saveStatusBanner');
    if (!banner) return;
    const isErr = (type === 'error');
    banner.style.display = 'block';
    banner.style.border = '1px solid ' + (isErr ? '#f87171' : '#4ade80');
    banner.style.background = isErr ? '#fef2f2' : '#f0fdf4';
    banner.style.color = isErr ? '#991b1b' : '#166534';
    banner.textContent = (isErr ? '✕ บันทึกไม่สำเร็จ: ' : '✓ บันทึกสำเร็จ: ') + message;
    banner.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function sanitizeStreetJs(street, district, amphoe, province, zipcode) {
    let clean = String(street || '').replace(/\s{2,}/g, ' ').trim();
    if (!clean) return '';

    const d = String(district || '').trim();
    const a = String(amphoe || '').trim();
    const p = String(province || '').trim();
    const z = String(zipcode || '').trim();

    clean = clean.replace(/(?:\s*(?:แขวง|ต\.|ตำบล)\s*[^\s]+\s*(?:เขต|อ\.|อำเภอ)\s*[^\s]+(?:\s*(?:จ\.|จังหวัด)?\s*[^\s]+)?(?:\s*\d{5})?)+$/g, '');

    const tokens = [
        d ? 'แขวง' + d : '',
        d ? 'ต.' + d : '',
        d ? 'ตำบล' + d : '',
        d,
        a ? 'เขต' + a : '',
        a ? 'อ.' + a : '',
        a ? 'อำเภอ' + a : '',
        a,
        p ? 'จ.' + p : '',
        p ? 'จังหวัด' + p : '',
        p,
        z
    ].filter(Boolean).sort((x, y) => y.length - x.length);

    tokens.forEach(tok => {
        clean = clean.split(tok).join(' ');
    });

    clean = clean.replace(/(ต\.|ตำบล|แขวง|อ\.|อำเภอ|เขต|จ\.|จังหวัด)\s*(?=(\s|$|(ต\.|ตำบล|แขวง|อ\.|อำเภอ|เขต|จ\.|จังหวัด)))/g, ' ');
    clean = clean.replace(/\s{2,}/g, ' ').trim();
    return clean.replace(/^[\s,]+|[\s,]+$/g, '');
}

function formatBaht(num) {
    return Number(num || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + ' บาท';
}

function cleanRateText(rate) {
    const n = Number(rate || 0);
    return Number.isInteger(n) ? String(n) : String(Math.round(n * 100) / 100);
}

function recalcOrderTotals() {
    const modeEl     = document.getElementById('whtModeSelect');
    const pctEl      = document.getElementById('whtPercentInput');
    const pctSuffix  = document.getElementById('whtCustomPercentSuffix');
    const dispNetEl  = document.getElementById('displayGrandTotal');
    const dispRateEl = document.getElementById('displayWhtRate');
    const dispAmtEl  = document.getElementById('displayWhtAmount');
    const hidM       = document.getElementById('hiddenWhtMode');
    const hidR       = document.getElementById('hiddenWhtRate');
    const hidA       = document.getElementById('hiddenWhtAmount');
    if (!modeEl || !pctEl || !dispNetEl) return;

    const mode = modeEl.value;
    let whtRateVal = 0;

    if (mode === 'custom') {
        pctEl.style.display = 'inline-block';
        if (pctSuffix) pctSuffix.style.display = 'inline';
        const rawPct = parseFloat(pctEl.value);
        whtRateVal = Number.isFinite(rawPct) ? Math.min(100, Math.max(0, Math.round(rawPct * 100) / 100)) : 0;
    } else {
        pctEl.style.display = 'none';
        if (pctSuffix) pctSuffix.style.display = 'none';
        const parsedPreset = parseFloat(mode);
        whtRateVal = Number.isFinite(parsedPreset) ? Math.max(0, parsedPreset) : 0;
        pctEl.value = cleanRateText(whtRateVal);
    }

    const whtAmt = Math.round(ORDER_TOTAL_SUBTOTAL * (whtRateVal / 100) * 100) / 100;
    const net    = Math.round((ORDER_TOTAL_SUBTOTAL + ORDER_TOTAL_VAT - whtAmt) * 100) / 100;

    dispNetEl.textContent = formatBaht(net);
    if (dispRateEl) {
        dispRateEl.textContent = cleanRateText(whtRateVal);
    }
    if (dispAmtEl) {
        dispAmtEl.textContent = '-' + formatBaht(whtAmt);
    }
    if (hidM) hidM.value = mode;
    if (hidR) hidR.value = whtRateVal.toFixed(2);
    if (hidA) hidA.value = whtAmt.toFixed(2);
}

function applyThaiAddressSelection(prov, amp, dist, zip) {
    const provinceEl = document.getElementById('addr-province');
    const amphoeEl   = document.getElementById('addr-amphoe');
    const districtEl = document.getElementById('addr-district');
    const zipcodeEl  = document.getElementById('addr-zipcode');
    if (!provinceEl || !amphoeEl || !districtEl || !zipcodeEl) return;

    if (provinceEl.tagName === 'SELECT' && typeof detailPopulateAmphoes === 'function' && typeof detailPopulateDistricts === 'function') {
        provinceEl.value = prov || '';
        detailPopulateAmphoes(prov || '', amp || '');
        if (amp) {
            detailPopulateDistricts(prov || '', amp || '', dist || '');
        }
        if (zip) {
            zipcodeEl.value = zip;
        }
    } else {
        provinceEl.value = prov || '';
        amphoeEl.value   = amp || '';
        districtEl.value = dist || '';
        if (zip) {
            zipcodeEl.value = zip;
        }
    }
}

function refreshFromCompanies() {
    const sel = document.getElementById('companyRefreshSelect');
    if (!sel) return;
    const opt = sel.options[sel.selectedIndex];
    if (!opt) return;

    const prov = opt.getAttribute('data-province') || '';
    const amp  = opt.getAttribute('data-amphoe') || '';
    const dist = opt.getAttribute('data-district') || '';
    const zip  = opt.getAttribute('data-zipcode') || '';
    const addr = sanitizeStreetJs(opt.getAttribute('data-address') || '', dist, amp, prov, zip);

    const setField = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.value = val;
    };

    setField('inpCompanyName', opt.getAttribute('data-company-name') || '');
    setField('inpBranch',      opt.getAttribute('data-branch') || '');
    setField('inpTaxId',       opt.getAttribute('data-tax-id') || '');
    setField('inpAddress',     addr);
    setField('inpCoordPhone',  opt.getAttribute('data-phone') || '');
    setField('inpCoordEmail',  opt.getAttribute('data-billing-email') || '');

    applyThaiAddressSelection(prov, amp, dist, zip);
}

async function initDetailThaiAddress() {
    const provinceSelect = document.getElementById('addr-province');
    const amphoeSelect   = document.getElementById('addr-amphoe');
    const districtSelect = document.getElementById('addr-district');
    const zipcodeInput   = document.getElementById('addr-zipcode');
    if (!provinceSelect || !amphoeSelect || !districtSelect || !zipcodeInput) return;

    const targetProvince = <?= json_encode($province, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) ?>;
    const targetAmphoe   = <?= json_encode($amphoe, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) ?>;
    const targetDistrict = <?= json_encode($district, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) ?>;
    const targetZipcode  = <?= json_encode($zipcode, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) ?>;

    try {
        const res = await fetch('https://raw.githubusercontent.com/earthchie/jquery.Thailand.js/master/jquery.Thailand.js/database/raw_database/raw_database.json');
        if (!res.ok) throw new Error('Network error');
        detailThaiAddressDB = await res.json();

        const provinces = [...new Set(detailThaiAddressDB.map(item => item.province))].sort((a, b) => a.localeCompare(b, 'th'));
        provinceSelect.innerHTML = '<option value="">-- เลือกจังหวัด --</option>';
        provinces.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p;
            opt.textContent = p;
            provinceSelect.appendChild(opt);
        });

        detailPopulateAmphoes = function(selectedProv, defaultAmphoe = '') {
            amphoeSelect.innerHTML = '<option value="">-- เลือกอำเภอ / เขต --</option>';
            districtSelect.innerHTML = '<option value="">-- เลือกอำเภอ/เขตก่อน --</option>';
            districtSelect.disabled = true;
            districtSelect.style.background = '#f8fafc';

            if (!selectedProv) {
                amphoeSelect.disabled = true;
                amphoeSelect.style.background = '#f8fafc';
                return;
            }

            const amphoes = [...new Set(detailThaiAddressDB.filter(item => item.province === selectedProv).map(item => item.amphoe))].sort((a, b) => a.localeCompare(b, 'th'));
            amphoes.forEach(a => {
                const opt = document.createElement('option');
                opt.value = a;
                opt.textContent = a;
                if (a === defaultAmphoe) opt.selected = true;
                amphoeSelect.appendChild(opt);
            });

            amphoeSelect.disabled = false;
            amphoeSelect.style.background = '#fff';
        };

        detailPopulateDistricts = function(selectedProv, selectedAmphoe, defaultDistrict = '') {
            districtSelect.innerHTML = '<option value="">-- เลือกตำบล / แขวง --</option>';

            if (!selectedProv || !selectedAmphoe) {
                districtSelect.disabled = true;
                districtSelect.style.background = '#f8fafc';
                return;
            }

            const districts = detailThaiAddressDB
                .filter(item => item.province === selectedProv && item.amphoe === selectedAmphoe)
                .sort((a, b) => a.district.localeCompare(b.district, 'th'));

            const seen = new Set();
            districts.forEach(d => {
                const key = d.district + '_' + d.zipcode;
                if (seen.has(key)) return;
                seen.add(key);

                const opt = document.createElement('option');
                opt.value = d.district;
                opt.textContent = d.district;
                opt.dataset.zipcode = d.zipcode;
                if (d.district === defaultDistrict) opt.selected = true;
                districtSelect.appendChild(opt);
            });

            districtSelect.disabled = false;
            districtSelect.style.background = '#fff';
        };

        provinceSelect.addEventListener('change', function() {
            detailPopulateAmphoes(this.value);
            zipcodeInput.value = '';
        });

        amphoeSelect.addEventListener('change', function() {
            detailPopulateDistricts(provinceSelect.value, this.value);
            zipcodeInput.value = '';
        });

        districtSelect.addEventListener('change', function() {
            const selectedOpt = this.options[this.selectedIndex];
            if (selectedOpt && selectedOpt.dataset.zipcode) {
                zipcodeInput.value = selectedOpt.dataset.zipcode;
            }
        });

        zipcodeInput.addEventListener('input', function() {
            const zip = this.value.trim();
            if (zip.length === 5) {
                const matches = detailThaiAddressDB.filter(item => String(item.zipcode) === zip);
                if (matches.length > 0) {
                    const first = matches[0];
                    provinceSelect.value = first.province;
                    detailPopulateAmphoes(first.province, first.amphoe);
                    detailPopulateDistricts(first.province, first.amphoe, matches.length === 1 ? first.district : '');
                }
            }
        });

        if (targetProvince) {
            applyThaiAddressSelection(targetProvince, targetAmphoe, targetDistrict, targetZipcode);
        }
    } catch (err) {
        console.error('ไม่สามารถโหลดข้อมูลจังหวัด/อำเภอ/ตำบลได้:', err);
        provinceSelect.outerHTML = `<input type="text" name="province" id="addr-province" class="adm-input" value="${targetProvince}" placeholder="ระบุจังหวัด">`;
        amphoeSelect.outerHTML   = `<input type="text" name="amphoe" id="addr-amphoe" class="adm-input" value="${targetAmphoe}" placeholder="ระบุอำเภอ / เขต">`;
        districtSelect.outerHTML = `<input type="text" name="district" id="addr-district" class="adm-input" value="${targetDistrict}" placeholder="ระบุตำบล / แขวง">`;
    }
}

function toggleDetailRoster(rowId) {
    var el = document.getElementById(rowId);
    if (!el) return;
    el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'table-row' : 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    recalcOrderTotals();
    initDetailThaiAddress();

    const editForm = document.getElementById('orderEditForm');
    if (editForm) {
        editForm.addEventListener('submit', function(ev) {
            recalcOrderTotals();
            const coordName = (document.getElementById('inpCoordName')?.value || '').trim();
            const compName  = (document.getElementById('inpCompanyName')?.value || '').trim();
            if (!coordName && !compName) {
                ev.preventDefault();
                showSaveBanner('error', 'กรุณาระบุชื่อผู้ประสานงานหรือชื่อบริษัทอย่างน้อย 1 รายการ');
            }
        });
    }
});
</script>