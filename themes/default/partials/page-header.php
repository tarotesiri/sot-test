<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : page-header.php
 *    Description : Partial หัวข้อหน้า (Page Header) พร้อม Breadcrumb นำทาง
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 1.0.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);
?>
<section class="page-header">
    <div class="container">
        <?php if (!empty($breadcrumbs)): ?>
        <ol class="breadcrumb">
            <?php foreach ($breadcrumbs as $label => $link): ?>
            <li><?php if ($link): ?><a href="<?= e(url($link)) ?>"><?= e($label) ?></a><?php else: ?><span aria-current="page"><?= e($label) ?></span><?php endif; ?></li>
            <?php endforeach; ?>
        </ol>
        <?php endif; ?>
        <h1><?= e($title ?? '') ?></h1>
        <?php if (!empty($subtitle)): ?><p class="page-header__sub"><?= e($subtitle) ?></p><?php endif; ?>
    </div>
</section>