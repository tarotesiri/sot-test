<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : forgot_modal.php
 *    Description : Popup Modal สำหรับลืมรหัสผ่าน (Forgot Password)
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-10-29
 *    Version : 1.0.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);
?>
<style>
    .forgot-modal-overlay {
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
    .forgot-modal-overlay.active {
        display: flex !important;
    }
    .forgot-modal-box {
        background: #fff;
        width: 100%; max-width: 440px;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        overflow: hidden;
        position: relative;
    }
    .forgot-modal-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex; justify-content: space-between; align-items: center;
    }
    .forgot-modal-close {
        background: none; border: none; font-size: 1.5rem;
        color: #6b7280; cursor: pointer; line-height: 1;
    }
    .forgot-modal-body {
        padding: 1.5rem;
    }
</style>

<div id="forgotModal" class="forgot-modal-overlay" onclick="outsideClickCloseForgot(event)">
    <div class="forgot-modal-box">
        <div class="forgot-modal-header">
            <h3 style="font-size: 1.2rem; font-weight: 700; color: #111827; margin: 0;">ลืมรหัสผ่าน</h3>
            <button type="button" class="forgot-modal-close" onclick="closeForgotModal()">&times;</button>
        </div>
        <div class="forgot-modal-body">
            
            <?php if (!empty($_SESSION['forgot_error'])): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 0.6rem 0.75rem; border-radius: 6px; font-size: 0.85rem; margin-bottom: 1rem;">
                    <?= e($_SESSION['forgot_error']) ?>
                    <?php unset($_SESSION['forgot_error']); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['forgot_success'])): ?>
                <div style="background: #dcfce7; color: #166534; padding: 0.6rem 0.75rem; border-radius: 6px; font-size: 0.85rem; margin-bottom: 1rem;">
                    <?= e($_SESSION['forgot_success']) ?>
                    <?php unset($_SESSION['forgot_success']); ?>
                </div>
            <?php endif; ?>

            <p style="font-size: 0.88rem; color: #4b5563; margin-top: 0; margin-bottom: 1.2rem;">
                กรุณากรอกอีเมลที่คุณใช้ลงทะเบียน ระบบจะจัดส่งลิงก์สำหรับตั้งค่ารหัสผ่านใหม่ไปให้คุณ
            </p>

            <form action="<?= url('auth/forgot-process') ?>" method="POST" id="forgotFormModal">
                <?= function_exists('csrf_field') ? (csrf_field() ?? '') : '' ?>

                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #111827; margin-bottom: 0.3rem;">อีเมลที่ใช้สมัคร (Email Address)</label>
                    <input type="email" name="email" class="form-control" required placeholder="name@example.com" style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 6px; border: 1px solid #e5e7eb; font-size: 0.9rem;">
                </div>

                <div style="margin-bottom: 1rem;">
                    <button type="submit" class="btn btn--primary btn--block" style="width: 100%; padding: 0.7rem; font-size: 0.9rem; font-weight: 600; border-radius: 6px; background: #2563eb; color: #fff; border: none; cursor: pointer;">ส่งลิงก์รีเซ็ตรหัสผ่าน</button>
                </div>

                <div style="text-align: center; margin-top: 1rem;">
                    <a href="#" onclick="switchToLogin(); return false;" style="font-size: 0.85rem; color: #4b5563; text-decoration: none;">← กลับไปหน้าเข้าสู่ระบบ</a>
                </div>
            </form>

        </div>
    </div>
</div>