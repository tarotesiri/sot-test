<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : detail.php
 *    Description : View รายละเอียดฟีเจอร์และสถานะของแต่ละ Module (รองรับ HTML แท็ก)
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 2.0.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);
$this->partial('page-header', ['title' => $title, 'subtitle' => $subtitle, 'breadcrumbs' => $breadcrumbs]);
$isDone = $mod['status'] === 'completed';
?>
<section class="section">
    <div class="container" style="max-width:850px">
        <div class="card">
            <div class="card__body" style="padding:2rem">
                <div class="flex justify-between items-center mb-2">
                    <span class="badge badge--solid" style="font-size:.9rem;padding:.3rem .8rem"><?= e($mod['num']) ?></span>
                    <span class="badge <?= $isDone ? 'badge--success' : 'badge--warning' ?>" style="font-size:.9rem;padding:.3rem .8rem">
                        <?= icon($isDone ? 'check-circle' : 'clock') ?> <?= e($mod['status_label']) ?>
                    </span>
                </div>

                <h2 style="font-size:1.6rem;margin-bottom:.5rem"><?= e($mod['title']) ?></h2>
                <p style="color:var(--muted);font-size:1.05rem"><?= e($mod['desc']) ?></p>

                <hr style="border:0;border-top:1px solid var(--line);margin:1.5rem 0">

                <h3 style="margin-bottom:1rem">ฟีเจอร์และองค์ประกอบหลัก:</h3>
                <ul class="meta-list" style="gap:1rem">
                    <?php foreach ($mod['features'] as $f): ?>
                    <li style="font-size:1rem;align-items:flex-start;line-height:1.6">
                        <span style="color:var(--brand-600);margin-top:.25em"><?= icon('check') ?></span>
                        <span><?= $f ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <div class="mt-3 flex justify-between items-center" style="border-top:1px solid var(--line);padding-top:1.5rem">
                    <a href="<?= e(url('/roadmap')) ?>" class="btn btn--outline">
                        <?= icon('chevron-left') ?> กลับหน้า Roadmap
                    </a>
                    <?php if ($isDone): ?>
                    <span class="text-brand" style="font-weight:600;font-size:.95rem"><?= icon('check-circle') ?> โมดูลนี้พร้อมใช้งานบนระบบแล้ว</span>
                    <?php else: ?>
                    <span class="text-muted" style="font-size:.95rem"><?= icon('clock') ?> กำลังพัฒนาในลำดับถัดไป</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>