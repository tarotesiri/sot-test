<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : header.php
 *    Description : Partial ส่วนหัวเว็บ (Topbar แสดงข้อมูลติดต่อและที่ว่างสำหรับ Login/Register / Header โลโก้และช่องค้นหา)
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 1.2.1
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);
$siteName = (string) setting('site_name', env('APP_NAME', 'SIAM Training'));
$logo = setting('logo_path');
$phone = (string) setting('contact_phone', '');
$email = (string) setting('contact_email', '');
$line = (string) setting('contact_line', '');
$q = request()->query('q');
?>
<div class="topbar">
    <div class="container topbar__inner">
        <div class="topbar__contact">
            <span class="topbar__company"><?= icon('home') ?> <?= e($siteName) ?></span>
            <?php if ($phone !== ''): ?>
                <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= icon('phone') ?> <?= e($phone) ?></a>
            <?php endif; ?>
            <?php if ($email !== ''): ?>
                <a href="mailto:<?= e($email) ?>"><?= icon('mail') ?> <?= e($email) ?></a>
            <?php endif; ?>
            <?php if ($line !== ''): ?>
                <span><?= icon('message') ?> Line: <?= e($line) ?></span>
            <?php endif; ?>
        </div>
        <div>
        	<a href="<?= e(url('home')) ?>" target="_top">หน้าเว็บไซต์หลัก</a>
        </div>
        <div class="topbar_auth-placeholder" style="white-space: nowrap;">
            <?php if (!empty($_SESSION['user_id'])): ?>
                <?= icon('user') ?> <a href="<?= e(url('profile')) ?>">ข้อมูลส่วนตัว</a> | <?= icon('book') ?> <a href="<?= e(url('dashboard')) ?>">คอร์สของฉัน</a> | <?= icon('sign-out') ?> <a href="<?= e(url('auth/logout')) ?>">ออกจากระบบ</a>
            <?php else: ?>
                <?= icon('user') ?> <a href="#" onclick="openLoginModal(); return false;">เข้าสู่ระบบ</a> | <?= icon('user-plus') ?> <a href="#" onclick="openRegisterModal(); return false;">สมัครสมาชิก</a>
            <?php endif; ?>
        </div>
    </div>
</div>