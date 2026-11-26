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

    <form action="<?= url('auth/login-process') ?>" method="POST">
        <?= function_exists('csrf_field') ? (csrf_field() ?? '') : '' ?>
		<input type="hidden" name="redirect_to" id="login-redirect-to" value="">
        <div class="input-group">
            <span class="input-group-icon">👤</span>
            <input type="email" name="email" required placeholder="อีเมล (E-mail) ผู้เข้ารับอบรม">
        </div>

        <div class="input-group">
            <span class="input-group-icon">🔑</span>
            <input type="password" name="password" required placeholder="รหัสผ่าน">
        </div>

        <div style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 1.25rem; font-size: 0.85rem;">
            <a href="#" onclick="openForgotModal(); return false;" style="color: #2563eb; text-decoration: none;">เปลี่ยนรหัสผ่านใหม่</a>
        </div>

        <div class="recaptcha-box">
            <label style="display: flex; align-items: center; cursor: pointer; font-size: 0.9rem; font-weight: 500; color: #374151; width: 100%;">
                <input type="checkbox" name="recaptcha_check" value="1" required style="width: 20px; height: 20px; margin-right: 0.75rem; cursor: pointer;"> ฉันไม่ใช่โปรแกรมอัตโนมัติ
            </label>
        </div>

        <div>
            <button type="submit" style="width: 100%; padding: 0.75rem; font-size: 0.95rem; font-weight: 600; border-radius: 4px; background: #16a34a; color: #fff; border: none; cursor: pointer;">เข้าสู่ระบบ</button>
        </div>
    </form>
</div>