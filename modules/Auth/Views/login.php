<?php
declare(strict_types=1);
$title = "เข้าสู่ระบบ / ลงทะเบียน";
$activeTab = $activeTab ?? 'login';
?>
<div style="max-width: 480px; margin: 3rem auto; background: #fff; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); overflow: hidden;">
    <div class="auth-tabs" style="display: flex; background: #f3f4f6; border-bottom: 1px solid #e5e7eb;">
        <button type="button" id="pageTabLoginBtn" class="auth-tab-btn <?= $activeTab === 'login' ? 'active' : '' ?>" onclick="switchPageTab('login')" style="flex: 1; padding: 1rem; text-align: center; background: <?= $activeTab === 'login' ? '#3b82f6' : '#e5e7eb' ?>; border: none; font-size: 0.95rem; font-weight: 600; color: <?= $activeTab === 'login' ? '#fff' : '#4b5563' ?>; cursor: pointer;">เข้าสู่ระบบ</button>
        <button type="button" id="pageTabRegisterBtn" class="auth-tab-btn <?= $activeTab === 'register' ? 'active' : '' ?>" onclick="switchPageTab('register')" style="flex: 1; padding: 1rem; text-align: center; background: <?= $activeTab === 'register' ? '#3b82f6' : '#e5e7eb' ?>; border: none; font-size: 0.95rem; font-weight: 600; color: <?= $activeTab === 'register' ? '#fff' : '#4b5563' ?>; cursor: pointer;">ลงทะเบียนใหม่</button>
    </div>

    <div id="pageLoginSection" style="display: <?= $activeTab === 'login' ? 'block' : 'none' ?>;">
        <?php include __DIR__ . '/_login_form.php'; ?>
    </div>
    <div id="pageRegisterSection" style="display: <?= $activeTab === 'register' ? 'block' : 'none' ?>;">
        <?php include __DIR__ . '/_register_form.php'; ?>
    </div>
</div>

<script>
function switchPageTab(tab) {
    const loginBtn = document.getElementById('pageTabLoginBtn');
    const registerBtn = document.getElementById('pageTabRegisterBtn');
    const loginSec = document.getElementById('pageLoginSection');
    const registerSec = document.getElementById('pageRegisterSection');

    if (!loginBtn || !registerBtn || !loginSec || !registerSec) return;

    if (tab === 'login') {
        loginBtn.classList.add('active');
        loginBtn.style.background = '#3b82f6';
        loginBtn.style.color = '#fff';
        registerBtn.classList.remove('active');
        registerBtn.style.background = '#e5e7eb';
        registerBtn.style.color = '#4b5563';
        loginSec.style.display = 'block';
        registerSec.style.display = 'none';
    } else {
        registerBtn.classList.add('active');
        registerBtn.style.background = '#3b82f6';
        registerBtn.style.color = '#fff';
        loginBtn.classList.remove('active');
        loginBtn.style.background = '#e5e7eb';
        loginBtn.style.color = '#4b5563';
        registerSec.style.display = 'block';
        loginSec.style.display = 'none';
    }
}
</script>

<style>
    .input-group { display: flex; align-items: center; border: 1px solid #d1d5db; border-radius: 4px; margin-bottom: 1rem; background: #fff; overflow: hidden; }
    .input-group:focus-within { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15); }
    .input-group-icon { padding: 0.65rem 0.85rem; background: #f3f4f6; color: #6b7280; border-right: 1px solid #d1d5db; display: flex; align-items: center; justify-content: center; min-width: 42px; }
    .input-group input { width: 100%; padding: 0.65rem 0.75rem; border: none; outline: none; font-size: 0.9rem; color: #1f2937; }
    .recaptcha-box { display: flex; align-items: center; background: #f9fafb; border: 1px solid #d1d5db; border-radius: 4px; padding: 0.75rem 1rem; margin-bottom: 1.25rem; }
</style>