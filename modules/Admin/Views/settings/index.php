<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : index.php
 * Description : Settings View
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.22.0
 * ====================================================================
**/
declare(strict_types=1);

use Modules\Admin\Support\AdminHelper as H;
$days = $days ?? 20;
$name_x = $name_x ?? 100;
$name_y = $name_y ?? 200;
$date_x = $date_x ?? 100;
$date_y = $date_y ?? 300;
?>
<div class="adm-page-head"><h1>ตั้งค่ามาตรฐานระบบ</h1></div>
<form class="adm-card" method="post" action="<?= H::e(H::url('admin/settings/update')) ?>" style="max-width:600px" onsubmit="return confirm('คุณต้องการบันทึกการตั้งค่ามาตรฐานนี้ใช่หรือไม่?');">
    <?= H::csrf() ?>
    <div class="adm-card-head"><h2>กำหนดค่าเริ่มต้นมาตรฐาน</h2></div>
    <div class="adm-card-body adm-form-grid">
        <label class="adm-field is-full">
            <span class="adm-label">จำนวนวันชำระก่อนถึงวันอบรม</span>
            <input class="adm-input" type="number" min="1" name="default_payment_days" required value="<?= (int)$days ?>">
            <small class="adm-muted" style="font-style:italic">กำหนดจำนวนวันเริ่มต้น (เช่น 20 วัน) นำไปใช้กับรุ่นอบรมใหม่</small>
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
    <div class="adm-form-foot"><button class="adm-btn is-primary" type="submit">บันทึกการตั้งค่ามาตรฐาน</button></div>
</form>