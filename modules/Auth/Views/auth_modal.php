<?php
declare(strict_types=1);
?>
<style>
    .auth-modal-overlay { display: none !important; position: fixed !important; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.6); z-index: 99999; justify-content: center; align-items: center; padding: 1rem; box-sizing: border-box; }
    .auth-modal-overlay.active { display: flex !important; }
    .auth-modal-box { background: #fff; width: 100%; max-width: 480px; border-radius: 6px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); overflow: hidden; position: relative; }
    .auth-tabs { display: flex; background: #f3f4f6; border-bottom: 1px solid #e5e7eb; }
    .auth-tab-btn { flex: 1; padding: 1rem; text-align: center; background: #e5e7eb; border: none; font-size: 0.95rem; font-weight: 600; color: #4b5563; cursor: pointer; transition: all 0.2s; }
    .auth-tab-btn.active { background: #3b82f6; color: #fff; }
    .input-group { display: flex; align-items: center; border: 1px solid #d1d5db; border-radius: 4px; margin-bottom: 1rem; background: #fff; overflow: hidden; }
    .input-group:focus-within { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15); }
    .input-group-icon { padding: 0.65rem 0.85rem; background: #f3f4f6; color: #6b7280; border-right: 1px solid #d1d5db; display: flex; align-items: center; justify-content: center; min-width: 42px; }
    .input-group input { width: 100%; padding: 0.65rem 0.75rem; border: none; outline: none; font-size: 0.9rem; color: #1f2937; }
    .recaptcha-box { display: flex; align-items: center; background: #f9fafb; border: 1px solid #d1d5db; border-radius: 4px; padding: 0.75rem 1rem; margin-bottom: 1.25rem; }
</style>

<div id="authModal" class="auth-modal-overlay" onclick="outsideClickCloseAuth(event)">
    <div class="auth-modal-box">
        <div class="auth-tabs">
            <button type="button" id="modalTabLoginBtn" class="auth-tab-btn active" onclick="switchModalTab('login')">เข้าสู่ระบบ</button>
            <button type="button" id="modalTabRegisterBtn" class="auth-tab-btn" onclick="switchModalTab('register')">ลงทะเบียนใหม่</button>
        </div>

        <div id="modalLoginSection">
            <?php include __DIR__ . '/_login_form.php'; ?>
        </div>
        <div id="modalRegisterSection" style="display: none;">
            <?php include __DIR__ . '/_register_form.php'; ?>
        </div>
    </div>
</div>

<script>
function openAuthModal(tabName = 'login') {
    const modal = document.getElementById('authModal');
    if (modal) {
        modal.classList.add('active');
        switchModalTab(tabName);
    }
}
function closeAuthModal() {
    const modal = document.getElementById('authModal');
    if (modal) { modal.classList.remove('active'); }
}
function switchModalTab(tab) {
    const loginBtn = document.getElementById('modalTabLoginBtn');
    const registerBtn = document.getElementById('modalTabRegisterBtn');
    const loginSec = document.getElementById('modalLoginSection');
    const registerSec = document.getElementById('modalRegisterSection');

    if (!loginBtn || !registerBtn || !loginSec || !registerSec) return;

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