<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : flash.php
 *    Description : Partial แสดง Flash Message แจ้งเตือน (Success/Error/Warning/Info) พร้อมปุ่มปิดและซ่อนอัตโนมัติ
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 1.0.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);
$flashes = \App\Core\Session::pullFlashes();
$icons = ['success' => 'check', 'error' => 'alert', 'warning' => 'alert', 'info' => 'info'];
if ($flashes): ?>
<div class="container flash-stack">
    <?php foreach ($flashes as $f): $type = $icons[$f['type']] ?? 'info'; ?>
    <div class="alert alert--<?= e($f['type']) ?>" role="alert" data-autohide>
        <?= icon($type) ?>
        <div class="alert__body"><?= e($f['message']) ?></div>
        <button type="button" class="alert__close" data-dismiss aria-label="ปิด"><?= icon('x') ?></button>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>