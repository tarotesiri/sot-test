<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : error.php
 *    Description : หน้าแสดง Error ทั่วไปตาม HTTP Status Code
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 1.0.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);
$status = $status ?? 500;
?>
<section class="container error-page">
    <div class="error-page__code"><?= (int) $status ?></div>
    <h1><?= e($title ?? 'เกิดข้อผิดพลาด') ?></h1>
    <?php if (!empty($message)): ?><p><?= e($message) ?></p><?php endif; ?>
    <div class="btn-group">
        <a class="btn btn--primary" href="<?= e(url('/')) ?>"><?= icon('home') ?> กลับหน้าแรก</a>
    </div>
</section>