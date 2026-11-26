<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : 404.php
 *    Description : หน้าแจ้งเตือน 404 ไม่พบหน้าที่ต้องการ
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
    <div class="error-page__code">404</div>
    <h1>ไม่พบหน้าที่คุณต้องการ</h1>
    <p>หน้านี้อาจถูกย้าย ลบ หรือ<strong>อยู่ระหว่างการพัฒนาใน Module ถัดไป</strong></p>
    <div class="btn-group">
        <a class="btn btn--primary" href="<?= e(url('/')) ?>"><?= icon('home') ?> กลับหน้าแรก</a>
        <a class="btn btn--ghost" href="#" data-back><?= icon('chevron-left') ?> ย้อนกลับ</a>
    </div>
</section>