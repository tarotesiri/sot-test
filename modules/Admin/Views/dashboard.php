<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : dashboard.php
 * Description : Dashboard
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.9.0
 * ====================================================================
**/
declare(strict_types=1);

use Modules\Admin\Support\AdminHelper as H;
$stats = $stats ?? [];
?>
<div class="adm-page-head"><h1>สวัสดี, <?= H::e(H::userName()) ?></h1></div>
<div class="adm-stats" style="margin-bottom:24px">
    <a class="adm-stat is-blue" href="<?= H::e(H::url('admin/courses')) ?>"><span>หลักสูตร</span><strong><?= number_format($stats['courses'] ?? 0) ?></strong></a>
    <a class="adm-stat is-green" href="<?= H::e(H::url('admin/categories')) ?>"><span>หมวดหมู่</span><strong><?= number_format($stats['categories'] ?? 0) ?></strong></a>
    <a class="adm-stat is-amber" href="<?= H::e(H::url('admin/courses')) ?>"><span>รุ่นอบรม</span><strong><?= number_format($stats['batches'] ?? 0) ?></strong></a>
    <a class="adm-stat is-red" href="<?= H::e(H::url('admin/trash')) ?>"><span>ถังขยะ</span><strong><?= number_format($stats['trashed'] ?? 0) ?></strong></a>
</div>
<p><a class="adm-btn is-primary" href="<?= H::e(H::url('admin/courses/create')) ?>">+ เพิ่มหลักสูตรใหม่</a></p>