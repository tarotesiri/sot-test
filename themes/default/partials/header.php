<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : header.php
 *    Description : Partial ส่วนหัวเว็บ (Topbar แสดงข้อมูลติดต่อและที่ว่างสำหรับ Login/Register / Header โลโก้ ช่องค้นหา และไอคอนตะกร้าสินค้า)
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 1.3.0
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
        	<?php
        	if (isset($_SESSION['user_role']) && $_SESSION['user_role']=='admin') {
        		echo icon('bookmark').' <a href="admin" target="_top">Admin Panel</a> '.icon('bookmark');
        	}
        	?>
        </div>
        <div class="topbar_auth-placeholder" style="white-space: nowrap;">
            <?php if (!empty($_SESSION['user_id'])): ?>
                <?= icon('user') ?> <a href="<?= e(url('profile')) ?>">ข้อมูลส่วนตัว</a> | <?= icon('book') ?> <a href="<?= e(url('dashboard')) ?>">คอร์สของฉัน</a> | <?= icon('log-out') ?> <a href="<?= e(url('auth/logout')) ?>">ออกจากระบบ</a>
            <?php else: ?>
                <?= icon('user') ?> <a href="#" onclick="openLoginModal(); return false;">เข้าสู่ระบบ</a> | <?= icon('plus') ?> <a href="#" onclick="openRegisterModal(); return false;">สมัครสมาชิก</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<header class="site-header">
    <div class="container site-header__inner">
        <a class="brand" href="<?= e(url('/')) ?>">
            <img class="brand__logo" src="<?= e($logo ? url((string) $logo) : asset('img/logo.svg')) ?>" alt="<?= e($siteName) ?>" width="48" height="48">
            <span class="brand__text">
                <span class="brand__name"><?= e($siteName) ?></span>
                <span class="brand__tagline"><?= e(setting('site_tagline', '')) ?></span>
            </span>
        </a>

        <div style="display: flex; align-items: center; gap: 1rem; flex: 1; justify-content: flex-end;">
            <form class="header-search" action="<?= e(url('/courses')) ?>" method="get" role="search" style="margin-bottom: 0;">
                <input type="search" name="q" placeholder="ค้นหาหลักสูตร..." aria-label="ค้นหาหลักสูตร" value="<?= e(is_string($q) ? $q : '') ?>">
                <button type="submit" aria-label="ค้นหา"><?= icon('search') ?></button>
            </form>

            <!-- ตะกร้าสินค้า Position 1 (ข้างกล่องค้นหา) -->
            <?php 
                $totalCoursesCount = 0;
                $totalAttendeesCount = 0;
                if (class_exists('\Modules\Cart\Services\CartService')) {
                    $cartService = new \Modules\Cart\Services\CartService();
                    $items = $cartService->getItems();
                    $totalCoursesCount = count($items);
                    foreach ($items as $item) {
                        $totalAttendeesCount += (int)($item['quantity'] ?? 0);
                    }
                }
                $cartBadgeText = $totalCoursesCount > 0 ? "{$totalCoursesCount}/{$totalAttendeesCount}" : '0/0';
                $cartTooltipText = "จำนวน {$totalCoursesCount} หลักสูตร\nผู้อบรม {$totalAttendeesCount} ท่าน";
            ?>
            <a href="<?= e(url('/cart')) ?>" id="nav-cart-btn" class="header-cart-btn" title="<?= e($cartTooltipText) ?>" style="position: relative; display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 44px; background: #f1f5f9; border: 1px solid var(--line, #e2e8f0); border-radius: 50%; color: var(--ink, #1e293b); text-decoration: none; transition: all 0.2s ease;">
                <span style="font-size: 1.25rem; display: flex; align-items: center; justify-content: center;">
                    <?= icon('cart') ?>
                </span>
                <span id="nav-cart-count" style="position: absolute; top: -6px; right: -8px; background: #16a34a; color: #ffffff; font-size: 0.68rem; font-weight: 700; height: 20px; min-width: 24px; padding: 0 4px; border-radius: 10px; display: flex; align-items: center; justify-content: center; border: 2px solid #ffffff; box-shadow: 0 2px 4px rgba(0,0,0,0.15); font-family: sans-serif; <?= $totalCoursesCount > 0 ? '' : 'display: none;' ?>">
                    <?= e($cartBadgeText) ?>
                </span>
            </a>
        </div>
    </div>
</header>