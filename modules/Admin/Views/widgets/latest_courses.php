<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : latest_courses.php
 * Description : Latest Courses Widget
 * ====================================================================
 * Author : TaRote SiRi
 * Created Date : 2026-11-01
 * Version : 1.1.0
 * ====================================================================
**/
declare(strict_types=1);

use Modules\Admin\Support\AdminHelper as H;
$courses = $courses ?? [];
?>
<section class="adm-card">
    <div class="adm-card-head">
        <h2>หลักสูตรที่แก้ไขล่าสุด</h2>
        <a class="adm-btn is-light is-sm" href="<?= H::e(H::url('admin/courses')) ?>">ดูทั้งหมด</a>
    </div>
    <div class="adm-table-wrap">
        <table class="adm-table">
            <thead><tr><th style="width:80px"></th><th>หลักสูตร</th><th>สถานะ</th><th>แก้ไขล่าสุด</th></tr></thead>
            <tbody>
            <?php if ($courses === []): ?>
                <tr><td colspan="4" class="adm-empty">ยังไม่มีหลักสูตร</td></tr>
            <?php endif; ?>
            <?php foreach ($courses as $c): $cover = H::uploadUrl($c['cover_image'] ?? null); ?>
                <tr>
                    <td><?php if ($cover !== ''): ?><img class="adm-thumb" src="<?= H::e($cover) ?>" alt=""><?php else: ?><span class="adm-thumb is-empty">-</span><?php endif; ?></td>
                    <td><a class="adm-strong" href="<?= H::e(H::url('admin/courses/' . (int) $c['id'] . '/edit')) ?>"><?= H::e($c['title'] ?? '') ?></a></td>
                    <td><?= H::badge($c['status'] ?? '') ?></td>
                    <td><?= H::e(H::thaiDate($c['updated_at'] ?? '', true)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>