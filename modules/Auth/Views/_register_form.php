<?php
declare(strict_types=1);

?>
<div class="auth-form-body" style="padding: 1.5rem;">
    <?php if (!empty($_SESSION['auth_error'])): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 0.6rem 0.75rem; border-radius: 4px; font-size: 0.85rem; margin-bottom: 1rem;">
            <?= e($_SESSION['auth_error']) ?>
            <?php unset($_SESSION['auth_error']); ?>
        </div>
    <?php endif; ?>

    <form action="<?= url('auth/register-process') ?>" method="POST">
        <?= function_exists('csrf_field') ? (csrf_field() ?? '') : '' ?>

        <!-- แถวที่ 1: คำนำหน้า, ชื่อ, นามสกุล -->
        <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
            <div style="flex: 0.8;">
                <label style="font-size:0.72rem; color:var(--muted); display:block; margin-bottom:0.2rem;">คำนำหน้า</label>
                <select name="prefix" class="form-control" style="width:100%; font-size:0.85rem; padding:0.4rem;">
                    <option value="">(เลือก)</option>
                    <option value="นาย">นาย</option>
                    <option value="นาง">นาง</option>
                    <option value="นางสาว">นางสาว</option>
                </select>
            </div>
            <div style="flex: 1.1;">
                <label style="font-size:0.72rem; color:var(--muted); display:block; margin-bottom:0.2rem;">ชื่อ (ภาษาไทย) *</label>
                <input type="text" name="firstname" required placeholder="เช่น สมชาย" class="form-control" style="width:100%; font-size:0.85rem; padding:0.4rem;">
            </div>
            <div style="flex: 1.1;">
                <label style="font-size:0.72rem; color:var(--muted); display:block; margin-bottom:0.2rem;">นามสกุล (ภาษาไทย) *</label>
                <input type="text" name="lastname" required placeholder="เช่น มั่นคง" class="form-control" style="width:100%; font-size:0.85rem; padding:0.4rem;">
            </div>
        </div>

        <!-- แถวที่ 2: อีเมล, เบอร์โทร, เลขบัตรประชาชน -->
        <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
            <div style="flex: 1.2;">
                <label style="font-size:0.72rem; color:var(--muted); display:block; margin-bottom:0.2rem;">อีเมลผู้เรียน *</label>
                <input type="email" name="email" required placeholder="somchai@company.com" class="form-control" style="width:100%; font-size:0.85rem; padding:0.4rem;">
            </div>
            <div style="flex: 1;">
                <label style="font-size:0.72rem; color:var(--muted); display:block; margin-bottom:0.2rem;">เบอร์โทรศัพท์</label>
                <input type="text" name="phone" placeholder="081-234-5678" class="form-control" style="width:100%; font-size:0.85rem; padding:0.4rem;">
            </div>
            <div style="flex: 1.2;">
                <label style="font-size:0.72rem; color:var(--muted); display:block; margin-bottom:0.2rem;">เลขบัตรประชาชน (13 หลัก)</label>
                <input type="text" name="id_card" placeholder="1-2345-67890-12-3" class="form-control" style="width:100%; font-size:0.85rem; padding:0.4rem;">
            </div>
        </div>

        <!-- แถวที่ 3: ตำแหน่งงาน, ความต้องการอื่นๆ -->
        <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
            <div style="flex: 1;">
                <label style="font-size:0.72rem; color:var(--muted); display:block; margin-bottom:0.2rem;">ตำแหน่งงาน</label>
                <input type="text" name="position" placeholder="เช่น วิศวกร" class="form-control" style="width:100%; font-size:0.85rem; padding:0.4rem;">
            </div>
            <div style="flex: 1;">
                <label style="font-size:0.72rem; color:var(--muted); display:block; margin-bottom:0.2rem;">ความต้องการอื่นๆ (ถ้ามี)</label>
                <input type="text" name="other_requirements" placeholder="เช่น อาหารมังสวิรัติ, อิสลาม" class="form-control" style="width:100%; font-size:0.85rem; padding:0.4rem;">
            </div>
        </div>

        <!-- แถวที่ 4: รหัสผ่าน -->
        <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
            <div class="input-group" style="flex: 1; margin-bottom: 0;">
                <input type="password" name="password" required placeholder="รหัสผ่าน" class="form-control" style="width:100%; font-size:0.85rem; padding:0.4rem;">
            </div>
            <div class="input-group" style="flex: 1; margin-bottom: 0;">
                <input type="password" name="password_confirmation" required placeholder="ยืนยันรหัสผ่านอีกครั้ง" class="form-control" style="width:100%; font-size:0.85rem; padding:0.4rem;">
            </div>
        </div>

        <div style="margin-bottom: 1rem; font-size: 0.85rem;">
            <label style="display: flex; align-items: center; cursor: pointer; color: #4b5563;">
                <input type="checkbox" name="terms" value="1" required style="margin-right: 0.4rem; width: 16px; height: 16px;"> 
                ยอมรับการให้บริการและ<a href="<?= url('/policy') ?>" target="_blank" style="color: #2563eb; text-decoration: underline; margin-left: 3px;">นโยบายความเป็นส่วนตัว</a>
            </label>
        </div>

        <div class="recaptcha-box" style="margin-bottom: 1rem;">
            <label style="display: flex; align-items: center; cursor: pointer; font-size: 0.9rem; font-weight: 500; color: #374151; width: 100%;">
                <input type="checkbox" name="register_recaptcha" value="1" required style="width: 20px; height: 20px; margin-right: 0.75rem; cursor: pointer;"> ฉันไม่ใช่โปรแกรมอัตโนมัติ
            </label>
        </div>

        <div>
            <button type="submit" style="width: 100%; padding: 0.75rem; font-size: 0.95rem; font-weight: 600; border-radius: 4px; background: #16a34a; color: #fff; border: none; cursor: pointer;">ลงทะเบียนใหม่</button>
        </div>
    </form>
</div>

<!--
<div class="auth-form-body" style="padding: 1.5rem;">
    <?php if (!empty($_SESSION['auth_error'])): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 0.6rem 0.75rem; border-radius: 4px; font-size: 0.85rem; margin-bottom: 1rem;">
            <?= e($_SESSION['auth_error']) ?>
            <?php unset($_SESSION['auth_error']); ?>
        </div>
    <?php endif; ?>

    <form action="<?= url('auth/register-process') ?>" method="POST">
        <?= function_exists('csrf_field') ? (csrf_field() ?? '') : '' ?>

        <div class="input-group">
            <span class="input-group-icon">✉️</span>
            <input type="email" name="email" required placeholder="E-mail ผู้เข้ารับอบรม">
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <div class="input-group" style="flex: 1;">
                <span class="input-group-icon">👤</span>
                <input type="text" name="firstname" required placeholder="ชื่อ ผู้เข้ารับอบรม">
            </div>
            <div class="input-group" style="flex: 1;">
                <span class="input-group-icon">👤</span>
                <input type="text" name="lastname" required placeholder="นามสกุล ผู้เข้ารับอบรม">
            </div>
        </div>

        <div class="input-group">
            <span class="input-group-icon">🔑</span>
            <input type="password" name="password" required placeholder="รหัสผ่าน">
        </div>

        <div class="input-group">
            <span class="input-group-icon">🔑</span>
            <input type="password" name="password_confirmation" required placeholder="ยืนยัน รหัสผ่านอีกครั้ง">
        </div>

        <div style="margin-bottom: 1rem; font-size: 0.85rem;">
            <label style="display: flex; align-items: center; cursor: pointer; color: #4b5563;">
                <input type="checkbox" name="terms" value="1" required style="margin-right: 0.4rem; width: 16px; height: 16px;"> 
                ยอมรับการให้บริการและ<a href="<?= url('/policy') ?>" target="_blank" style="color: #2563eb; text-decoration: underline; margin-left: 3px;">นโยบายความเป็นส่วนตัว</a>
            </label>
        </div>

        <div class="recaptcha-box">
            <label style="display: flex; align-items: center; cursor: pointer; font-size: 0.9rem; font-weight: 500; color: #374151; width: 100%;">
                <input type="checkbox" name="register_recaptcha" value="1" required style="width: 20px; height: 20px; margin-right: 0.75rem; cursor: pointer;"> ฉันไม่ใช่โปรแกรมอัตโนมัติ
            </label>
        </div>

        <div>
            <button type="submit" style="width: 100%; padding: 0.75rem; font-size: 0.95rem; font-weight: 600; border-radius: 4px; background: #16a34a; color: #fff; border: none; cursor: pointer;">ลงทะเบียนใหม่</button>
        </div>
    </form>
</div>
-->