<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : index.php
 *    Description : View หน้า Roadmap แสดงตารางสรุปข้อมูลทดสอบตามภาพต้นฉบับ
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 2.1.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);
$this->partial('page-header', ['title' => $title, 'subtitle' => $subtitle, 'breadcrumbs' => $breadcrumbs]);
?>
<section class="section">
    <div class="container">
        <!-- ตารางสรุป: ข้อมูลทดสอบมาจากไหน (ตามภาพต้นฉบับ) -->
        <h2 class="section__title mb-1">ตารางสรุป: ข้อมูลทดสอบมาจากไหน</h2>
        <p class="section__subtitle mb-2">ตารางแสดงความสัมพันธ์ของแต่ละโมดูล, ข้อมูลทดสอบชั่วคราว (Seed) และแหล่งที่มาของข้อมูลจริง</p>

        <div class="card" style="margin-top:1.5rem">
            <div class="card__body" style="padding:0">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Module</th>
                                <th>ต้องมี Module ก่อนหน้า</th>
                                <th>ข้อมูลทดสอบชั่วคราว (Seed)</th>
                                <th>ข้อมูลจริงมาจาก</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($seedSources as $row): ?>
                            <tr>
                                <td><strong><?= e($row['module']) ?></strong></td>
                                <td><?= e($row['dep']) ?></td>
                                <td><?= e($row['seed']) ?></td>
                                <td><?= e($row['real']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- รายการ 14 Modules ด้านล่าง -->
        <div style="margin-top:3.5rem">
            <h2 class="section__title mb-2">รายละเอียดรายชื่อ 14 Modules</h2>
            <div class="grid" style="--min:320px">
                <?php foreach ($modules as $m): 
                    $isDone = $m['status'] === 'completed';
                ?>
                <div class="card" style="display:flex;flex-direction:column;justify-content:space-between">
                    <div class="card__body">
                        <div class="flex justify-between items-center mb-1">
                            <span class="badge badge--solid"><?= e($m['num']) ?></span>
                            <span class="badge <?= $isDone ? 'badge--success' : 'badge--warning' ?>">
                                <?= icon($isDone ? 'check-circle' : 'clock') ?> <?= e($m['status_label']) ?>
                            </span>
                        </div>
                        <h3 style="margin-top:.75rem"><a href="<?= e(url('/roadmap/' . $m['id'])) ?>"><?= e($m['title']) ?></a></h3>
                        <p style="color:var(--muted);font-size:.92rem"><?= e($m['desc']) ?></p>
                    </div>
                    <div style="padding:0 1.35rem 1.35rem">
                        <a href="<?= e(url('/roadmap/' . $m['id'])) ?>" class="btn btn--outline btn--sm btn--block">
                            ดูรายละเอียดฟีเจอร์ <?= icon('arrow-right') ?>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>