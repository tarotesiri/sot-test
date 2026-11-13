<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : nav.php
 *    Description : Partial แถบเมนูนำทาง (Sticky Navbar) พร้อมเมนูย่อยและปุ่มเข้าสู่ระบบ
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 1.0.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);
$menu = config('menu')['main'] ?? [];
?>
<nav class="site-nav" aria-label="เมนูหลัก">
    <div class="container site-nav__inner">
        <button class="nav-toggle" type="button" data-nav-toggle aria-controls="nav-menu" aria-expanded="false"><?= icon('menu') ?><span>เมนู</span></button>
        <ul class="nav-menu" id="nav-menu">
            <?php foreach ($menu as $item): $has = !empty($item['children']); ?>
            <li class="nav-item">
                <a class="nav-link<?= is_active($item['url']) ? ' is-active' : '' ?>" href="<?= e(url($item['url'])) ?>"><?= e($item['label']) ?><?= $has ? icon('chevron-down', 'nav-caret') : '' ?></a>
                <?php if ($has): ?>
                <ul class="nav-dropdown">
                    <?php foreach ($item['children'] as $child): ?>
                    <li><a href="<?= e(url($child['url'])) ?>"><?= e($child['label']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <div class="nav-actions"><?= $this->widgets('nav.actions') ?></div>
    </div>
</nav>