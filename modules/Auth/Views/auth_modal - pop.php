<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : auth_modal.php
 *    Description : Popup Modal รวมระบบเข้าสู่ระบบและสมัครสมาชิก (นำคำนำหน้าชื่อออกตามคำขอ)
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-10-29
 *    Version : 2.4.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);
?>
<style>
    .auth-modal-overlay {
        display: none !important;
        position: fixed !important;
        top: 0; left: 0;
        width: 100vw; height: 100vh;
        background: rgba(0, 0, 0, 0.6);
        z-index: 99999;
        justify-content: center; align-items: center;
        padding: 1rem;
        box-sizing: border-box;
    }
    .auth-modal-overlay.active {
        display: flex !important;
    }
    .auth-modal-box {
        background: #fff;
        width: 100%; max-width: 480px;
        border-radius: 6px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        overflow: hidden;
        position: relative;
    }
    .auth-tabs {
        display: flex;
        background: #f3f4f6;
        border-bottom: 1px solid #e5e7eb;
    }
    .auth-tab-btn {
        flex: 1;
        padding: 1rem;
        text-align: center;
        background: #e5e7eb;
        border: none;
        font-size: 0.95rem;
        font-weight: 600;
        color: #4b5563;
        cursor: pointer;
        transition: all 0.2s;
    }
    .auth-tab-btn.active {
        background: #3b82f6;
        color: #fff;
    }
    .auth-modal-body {
        padding: 1.5rem;
        max-height: 80vh;
        overflow-y: auto;
    }
    .input-group {
        display: flex;
        align-items: center;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        margin-bottom: 1rem;
        background: #fff;
        overflow: hidden;
    }
    .input-group:focus-within {
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
    }
    .input-group-icon {
        padding: 0.65rem 0.85rem;
        background: #f3f4f6;
        color: #6b7280;
        border-right: 1px solid #d1d5db;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 42px;
    }
    .input-group input {
        width: 100%;
        padding: 0.65rem 0.75rem;
        border: none;
        outline: none;
        font-size: 0.9rem;
        color: #1f2937;
    }
    .recaptcha-box {
        display: flex;
        align-items: center;
        background: #f9fafb;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        padding: 0.75rem 1rem;
        margin-bottom: 1.25rem;
    }
</style>

<div id="authModal" class="auth-modal-overlay" onclick="outsideClickCloseAuth(event)">
    <div class="auth-modal-box">
        <!-- Tab Navigation -->
        <div class="auth-tabs">
            <button type="button" id="tabLoginBtn" class="auth-tab-btn active" onclick="switchAuthTab('login')">เข้าสู่ระบบ</button>
            <button type="button" id="tabRegisterBtn" class="auth-tab-btn" onclick="switchAuthTab('register')">ลงทะเบียนใหม่</button>
        </div>

        <div class="auth-modal-body">
            <!-- Global Alert Messages -->
            <?php if (!empty($_SESSION['auth_error'])): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 0.6rem 0.75rem; border-radius: 4px; font-size: 0.85rem; margin-bottom: 1rem;">
                    <?= e($_SESSION['auth_error']) ?>
                    <?php unset($_SESSION['auth_error']); ?>
                </div>
            <?php endif; ?>

            <!-- ==================== FORM 1: LOGIN ==================== -->
            <div id="formLoginSection">
                <form action="<?= url('auth/login-process') ?>" method="POST">
                    <?= function_exists('csrf_field') ? (csrf_field() ?? '') : '' ?>

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

                    <!-- reCAPTCHA Box (Login) -->
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

            <!-- ==================== FORM 2: REGISTER ==================== -->
            <div id="formRegisterSection" style="display: none;">
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

                    <!-- reCAPTCHA Box (Register) -->
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

        </div>
    </div>
</div>

<script>
function openAuthModal(tabName = 'login') {
    const modal = document.getElementById('authModal');
    if (modal) {
        modal.classList.add('active');
        switchAuthTab(tabName);
    }
}
function closeAuthModal() {
    const modal = document.getElementById('authModal');
    if (modal) {
        modal.classList.remove('active');
    }
}
function switchAuthTab(tab) {
    const loginBtn = document.getElementById('tabLoginBtn');
    const registerBtn = document.getElementById('tabRegisterBtn');
    const loginSec = document.getElementById('formLoginSection');
    const registerSec = document.getElementById('formRegisterSection');

    if (tab === 'login') {
        loginBtn.classList.add('active');
        registerBtn.classList.remove('active');
        loginSec.style.display = 'block';
        registerSec.style.display = 'none';
    } else {
        registerBtn.classList.add('active');
        loginBtn.classList.remove('active');
        registerSec.style.display = 'block';
        loginSec.style.display = 'none';
    }
}
function outsideClickCloseAuth(event) {
    let modalBox = document.querySelector('.auth-modal-box');
    if (modalBox && !modalBox.contains(event.target)) {
        closeAuthModal();
    }
}
</script>