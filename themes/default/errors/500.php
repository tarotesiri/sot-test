<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : 500.php
 *    Description : หน้าแจ้งเตือน 500 ระบบขัดข้อง
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
<section class="container error-page">
    <div class="error-page__code">500</div>
    <h1>ระบบขัดข้องชั่วคราว</h1>
    <p>ขออภัยในความไม่สะดวก ทีมงานกำลังดำเนินการตรวจสอบแก้ไข</p>
    <?php if (!empty($exception)): ?>
    <div class="alert alert--error" style="margin-top:1.5rem">
        <div class="alert__body">
            <strong><?= e($exception::class) ?>:</strong> <?= e($exception->getMessage()) ?>
            <div style="margin-top:.5rem;font-size:.85rem"><?= e($exception->getFile()) ?> : <?= $exception->getLine() ?></div>
        </div>
    </div>
    <pre class="error-trace"><?= e($exception->getTraceAsString()) ?></pre>
    <?php endif; ?>
    <div class="btn-group">
        <a class="btn btn--primary" href="<?= e(url('/')) ?>"><?= icon('home') ?> กลับหน้าแรก</a>
    </div>
</section>