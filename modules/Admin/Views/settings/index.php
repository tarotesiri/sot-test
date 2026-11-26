<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : index.php
 * Description : Settings View
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.25.0
 * ====================================================================
**/
declare(strict_types=1);

use Modules\Admin\Support\AdminHelper as H;

$days             = $days ?? 20;
$clip_access_days = $clip_access_days ?? 30;
$name_x           = $name_x ?? 100;
$name_y           = $name_y ?? 200;
$date_x           = $date_x ?? 100;
$date_y           = $date_y ?? 300;
$vat_rate         = $vat_rate ?? '7';
$wht_rate         = $wht_rate ?? '3';
$site_name        = $site_name ?? 'SIAM Online Training';
$site_tagline     = $site_tagline ?? '';
$footer_about     = $footer_about ?? '';
$logo_path        = $logo_path ?? '';
$contact_phone    = $contact_phone ?? '';
$contact_email    = $contact_email ?? '';
$contact_line     = $contact_line ?? '';
$contact_address  = $contact_address ?? '';
$office_hours     = $office_hours ?? '';
?>
<div class="adm-page-head"><h1>ตั้งค่ามาตรฐานระบบ</h1></div>

<form class="adm-card" method="post" enctype="multipart/form-data" action="<?= H::e(H::url('admin/settings/update')) ?>" style="max-width:780px" onsubmit="return confirm('คุณต้องการบันทึกการตั้งค่ามาตรฐานนี้ใช่หรือไม่?');">
    <?= H::csrf() ?>

    <!-- 1. เงื่อนไขเวลาหลักสูตรและพิกัดใบเซอร์ -->
    <div class="adm-card-head"><h2>ค่าเริ่มต้นการทำงานของระบบ (การอบรมและใบเซอร์)</h2></div>
    <div class="adm-card-body adm-form-grid">
        <label class="adm-field">
            <span class="adm-label">จำนวนวันชำระก่อนถึงวันอบรม</span>
            <input class="adm-input" type="number" min="1" name="default_payment_days" required value="<?= (int)$days ?>">
            <small class="adm-muted" style="font-style:italic">จำนวนวันก่อนวันอบรมวันแรก สำหรับราคาชำระก่อน (Live Online)</small>
        </label>

        <label class="adm-field">
            <span class="adm-label">จำนวนวันเข้าเรียน/พิมพ์ใบเซอร์ หลักสูตร Clip นับจากวันอนุมัติ</span>
            <input class="adm-input" type="number" min="1" name="clip_access_days" value="<?= (int)$clip_access_days ?>">
            <small class="adm-muted" style="font-style:italic">ระยะเวลาสิทธิ์เข้าเรียนและพิมพ์ใบเซอร์หลังได้รับอนุมัติ</small>
        </label>

        <div class="adm-field is-full" style="margin-top:10px">
            <strong>ตำแหน่งข้อมูลเพื่อปริ้นลงใบเซอร์ (พิกัดเริ่มต้น)</strong>
        </div>

        <label class="adm-field">
            <span class="adm-label">ชื่อ นามสกุล (พิกัด X)</span>
            <input class="adm-input" type="number" name="default_cert_name_x" required value="<?= (int)$name_x ?>">
        </label>
        <label class="adm-field">
            <span class="adm-label">ชื่อ นามสกุล (พิกัด Y)</span>
            <input class="adm-input" type="number" name="default_cert_name_y" required value="<?= (int)$name_y ?>">
        </label>

        <label class="adm-field">
            <span class="adm-label">วันที่ออกใบเซอร์ (พิกัด X)</span>
            <input class="adm-input" type="number" name="default_cert_date_x" required value="<?= (int)$date_x ?>">
        </label>
        <label class="adm-field">
            <span class="adm-label">วันที่ออกใบเซอร์ (พิกัด Y)</span>
            <input class="adm-input" type="number" name="default_cert_date_y" required value="<?= (int)$date_y ?>">
        </label>
    </div>

    <!-- 2. การเงินและภาษี -->
    <div class="adm-card-head" style="border-top:1px solid #e2e8f0"><h2>การเงินและภาษี</h2></div>
    <div class="adm-card-body adm-form-grid">
        <label class="adm-field">
            <span class="adm-label">อัตรา VAT (%)</span>
            <input class="adm-input" type="number" step="0.01" min="0" name="vat_rate" value="<?= H::e((string)$vat_rate) ?>">
        </label>
        <label class="adm-field">
            <span class="adm-label">อัตราหัก ณ ที่จ่ายเริ่มต้น (%)</span>
            <input class="adm-input" type="number" step="0.01" min="0" name="wht_rate" value="<?= H::e((string)$wht_rate) ?>">
        </label>
    </div>

    <!-- 3. ข้อมูลเว็บไซต์และโลโก้ -->
    <div class="adm-card-head" style="border-top:1px solid #e2e8f0"><h2>ข้อมูลเว็บไซต์และโลโก้</h2></div>
    <div class="adm-card-body adm-form-grid">
        <label class="adm-field">
            <span class="adm-label">ชื่อเว็บไซต์</span>
            <input class="adm-input" type="text" name="site_name" value="<?= H::e($site_name) ?>">
        </label>
        <label class="adm-field">
            <span class="adm-label">คำโปรย</span>
            <input class="adm-input" type="text" name="site_tagline" value="<?= H::e($site_tagline) ?>">
        </label>
        <label class="adm-field is-full">
            <span class="adm-label">ข้อความท้ายเว็บ</span>
            <textarea class="adm-input" name="footer_about" rows="2"><?= H::e($footer_about) ?></textarea>
        </label>
        <label class="adm-field is-full">
            <span class="adm-label">ไฟล์โลโก้ (ว่าง = ใช้โลโก้เริ่มต้น)</span>
            <input class="adm-input" type="file" name="logo_file" accept="image/*,.svg">
            <?php if ($logo_path !== ''): ?>
                <div style="margin-top:8px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                    <img src="<?= H::e(H::uploadUrl($logo_path)) ?>" alt="Logo" style="max-height:56px;width:auto;border:1px solid #cbd5e1;border-radius:4px;padding:4px;background:#fff">
                    <label style="font-size:13px;color:#b91c1c;cursor:pointer">
                        <input type="checkbox" name="remove_logo" value="1"> ลบโลโก้ปัจจุบัน
                    </label>
                </div>
            <?php endif; ?>
        </label>
    </div>

    <!-- 4. ข้อมูลติดต่อ -->
    <div class="adm-card-head" style="border-top:1px solid #e2e8f0"><h2>ข้อมูลติดต่อ</h2></div>
    <div class="adm-card-body adm-form-grid">
        <label class="adm-field">
            <span class="adm-label">เบอร์โทร</span>
            <input class="adm-input" type="text" name="contact_phone" value="<?= H::e($contact_phone) ?>">
        </label>
        <label class="adm-field">
            <span class="adm-label">อีเมล</span>
            <input class="adm-input" type="email" name="contact_email" value="<?= H::e($contact_email) ?>">
        </label>
        <label class="adm-field">
            <span class="adm-label">LINE ID</span>
            <input class="adm-input" type="text" name="contact_line" value="<?= H::e($contact_line) ?>">
        </label>
        <label class="adm-field">
            <span class="adm-label">เวลาทำการ</span>
            <input class="adm-input" type="text" name="office_hours" value="<?= H::e($office_hours) ?>">
        </label>
        <label class="adm-field is-full">
            <span class="adm-label">ที่อยู่</span>
            <textarea class="adm-input" name="contact_address" rows="2"><?= H::e($contact_address) ?></textarea>
        </label>
    </div>

    <div class="adm-form-foot"><button class="adm-btn is-primary" type="submit">บันทึกการตั้งค่ามาตรฐาน</button></div>
</form>