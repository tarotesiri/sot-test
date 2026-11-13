<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : course_stats.php
 * Description : Course Stats Widget
 * ====================================================================
 * Author : TaRote SiRi
 * Created Date : 2026-11-01
 * Version : 1.1.0
 * ====================================================================
**/
declare(strict_types=1);

use Modules\Admin\Support\AdminHelper as H;
$stats = $stats ?? [];
$cards = [
    ['หลักสูตรทั้งหมด', (int) ($stats['total'] ?? 0), '', 'is-blue'],
    ['เปิดรับสมัคร', (int) ($stats['open'] ?? 0), 'open', 'is-green'],
    ['ฉบับร่าง', (int) ($stats['draft'] ?? 0), 'draft', 'is-gray'],
    ['ปิดรับสมัคร', (int) ($stats['closed'] ?? 0), 'closed', 'is-amber'],
    ['ยกเลิก', (int) ($stats['cancelled'] ?? 0), 'cancelled', 'is-red'],
];
?>
<section class="adm-card">
    <div class="adm-card-head"><h2>ภาพรวมหลักสูตร</h2></div>
    <div class="adm-card-body">
        <div class="adm-stats">
            <?php foreach ($cards as [$label, $value, $status, $tone]): ?>
                <a class="adm-stat <?= H::e($tone) ?>" href="<?= H::e(H::url('admin/courses') . ($status !== '' ? '?status=' . $status : '')) ?>">
                    <span><?= H::e($label) ?></span>
                    <strong><?= number_format($value) ?></strong>
                </a>
            <?php endforeach; ?>
            <a class="adm-stat is-blue" href="<?= H::e(H::url('admin/categories')) ?>"><span>หมวดหมู่</span><strong><?= number_format((int) ($stats['categories'] ?? 0)) ?></strong></a>
            <a class="adm-stat is-green" href="<?= H::e(H::url('admin/courses')) ?>"><span>รอบเรียนที่จะถึง</span><strong><?= number_format((int) ($stats['upcoming'] ?? 0)) ?></strong></a>
            <a class="adm-stat is-red" href="<?= H::e(H::url('admin/trash')) ?>"><span>ในถังขยะ</span><strong><?= number_format((int) ($stats['trashed'] ?? 0)) ?></strong></a>
        </div>
    </div>
</section>