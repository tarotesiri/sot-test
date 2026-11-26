<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : layout.php
 *    Description : Layout หลักของเว็บไซต์ รองรับระบบ Auth Modal (Tab Login/Register) และ Forgot Password
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 2.0.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);
$siteName = (string) setting('site_name', env('APP_NAME', 'SIAM Training'));
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= isset($title) ? e($title) . ' : ' : '' ?><?= e($siteName) ?></title>
    <link rel="icon" href="<?= e(asset('img/logo.svg')) ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600&family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- โหลดสคริปต์จัดการตะกร้าสินค้าผ่าน LocalStorage ส่วนกลาง -->
	<script src="<?= e(asset('js/cart-sync.js')) ?>"></script>
    <link rel="stylesheet" href="<?= e(asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <?= $this->stack('styles') ?>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<a class="skip-link" href="#main">ข้ามไปยังเนื้อหาหลัก</a>
<?php
if ($_SERVER['REQUEST_URI']!='/SOT/admin' || (isset($_SESSION['user_role']) && $_SESSION['user_role']!='admin')) {
?>
	<?= $this->partial('header') ?>
	<?= $this->partial('nav') ?>
<?php
} else {
?>
	<?= $this->partial('admin') ?>
<?php
}
?>
<main id="main" class="site-main">
    <?= $this->partial('flash') ?>
    <?= $content ?>
</main>

<?php
if ($_SERVER['REQUEST_URI']!='/SOT/admin' || (isset($_SESSION['user_role']) && $_SESSION['user_role']!='admin')) {
?>
	<?= $this->partial('footer') ?>
<?php
} else {
?>
	
<?php
}
?>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<?= $this->stack('scripts') ?>

<!-- ============================================================================ -->
<!-- Auth Modals (Login/Register Tab Modal & Forgot Password Modal)               -->
<!-- ============================================================================ -->
<?php 
$authModalPath = __DIR__ . '/../../modules/Auth/Views/auth_modal.php';
if (file_exists($authModalPath)) {
    include $authModalPath;
}

$forgotModalPath = __DIR__ . '/../../modules/Auth/Views/forgot_modal.php';
if (file_exists($forgotModalPath)) {
    include $forgotModalPath;
}
?>

<!-- ฟังก์ชันควบคุม Popup ทั้งหมด -->
<script>
// เชื่อมโยงปุ่มเปิด-ปิด Login / Register Modal
function openLoginModal() { 
    closeForgotModal();

    // === [เพิ่มใหม่] ดึง URL หน้าปัจจุบันมาใส่ในช่องซ่อนของฟอร์ม Login อัตโนมัติ ===
    let currentUrl = window.location.href;
    let redirectInput = document.getElementById('login-redirect-to');
    if (redirectInput) {
        redirectInput.value = currentUrl;
    }
    // ======================================================================

    if (typeof openAuthModal === 'function') {
        openAuthModal('login'); 
    }
}
function openRegisterModal() { 
    closeForgotModal();
    if (typeof openAuthModal === 'function') {
        openAuthModal('register'); 
    }
}
function closeLoginModal() { 
    if (typeof closeAuthModal === 'function') {
        closeAuthModal(); 
    }
}

// ควบคุม Forgot Password Modal
function openForgotModal() {
    if (typeof closeAuthModal === 'function') {
        closeAuthModal();
    }
    const modal = document.getElementById('forgotModal');
    if (modal) { modal.classList.add('active'); }
}
function closeForgotModal() {
    const modal = document.getElementById('forgotModal');
    if (modal) { modal.classList.remove('active'); }
}
function switchToLogin() {
    closeForgotModal();
    openLoginModal();
}
function outsideClickCloseForgot(event) {
    let modalBox = document.querySelector('.forgot-modal-box');
    if (modalBox && !modalBox.contains(event.target)) {
        closeForgotModal();
    }
}
</script>
</body>
</html>