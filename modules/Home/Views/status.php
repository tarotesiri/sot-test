<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : status.php
 *    Description : View หน้าตรวจสอบสถานะระบบ ตรวจ PHP, Database, Writable folders และแสดงรายชื่อ Module/Route ทั้งหมด
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
<section class="section">
    <div class="container">
        <h2 class="section__title">สถานะระบบและความพร้อม</h2>
        <p class="section__subtitle">ตรวจสอบความพร้อมของเซิร์ฟเวอร์ ฐานข้อมูล และ Module ที่เปิดใช้งานในระบบ</p>

        <div class="card" style="margin-top:1.5rem">
            <div class="card__body" style="padding:0">
                <table class="table">
                    <thead><tr><th>รายการตรวจสอบ</th><th>สถานะ / รายละเอียด</th><th>ผลลัพธ์</th></tr></thead>
                    <tbody>
                        <?php foreach ($checks as [$label, $ok, $detail]): ?>
                        <tr>
                            <td><strong><?= e($label) ?></strong></td>
                            <td><code><?= e((string) $detail) ?></code></td>
                            <td><span class="badge <?= $ok ? 'badge--success' : 'badge--danger' ?>"><?= $ok ? 'ผ่าน' : 'ไม่ผ่าน' ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid mt-3" style="grid-template-columns:repeat(auto-fit,minmax(320px,1fr))">
            <div class="card">
                <div class="card__body">
                    <h3>Modules ที่เปิดใช้งาน (<?= count($modules) ?>)</h3>
                    <ul class="meta-list">
                        <?php foreach ($modules as $m): ?>
                        <li><?= icon('check') ?> <strong><?= e($m) ?></strong></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card__body">
                    <h3>Routes ทั้งหมดในระบบ (<?= count($routes) ?>)</h3>
                    <div style="max-height:220px;overflow:auto">
                        <table class="table" style="font-size:.85rem">
                            <?php foreach ($routes as $r): ?>
                            <tr>
                                <td><code><?= e(implode('|', $r['methods'])) ?></code></td>
                                <td><a href="<?= e(url($r['path'])) ?>"><?= e($r['path']) ?></a></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <a class="btn btn--primary" href="<?= e(url('/')) ?>"><?= icon('home') ?> กลับหน้าแรก</a>
        </div>
    </div>
</section>