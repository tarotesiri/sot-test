<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : index.php
 * Description : Categories
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.4.0
 * ====================================================================
**/
declare(strict_types=1);

use Modules\Admin\Support\AdminHelper as H;
$categories = $categories ?? [];
?>
<div class="adm-page-head"><h1>หมวดหมู่ (Tags)</h1></div>
<form class="adm-card" method="post" action="<?= H::e(H::url('admin/categories/store')) ?>" style="max-width:400px;margin-bottom:20px">
    <?= H::csrf() ?>
    <div class="adm-card-body">
        <label class="adm-field"><span class="adm-label">ชื่อหมวดหมู่ *</span><input class="adm-input" name="name" required></label>
    </div>
    <div class="adm-form-foot"><button class="adm-btn is-primary" type="submit">เพิ่มหมวดหมู่</button></div>
</form>
<div class="adm-card" style="max-width:600px">
    <table class="adm-table">
        <thead><tr><th>ชื่อ</th><th>Slug</th><th style="width:100px"></th></tr></thead>
        <tbody>
        <?php foreach($categories as $cat): ?>
            <tr><td><?= H::e($cat['name']) ?></td><td><?= H::e($cat['slug']) ?></td><td>
                <form method="post" action="<?= H::e(H::url('admin/categories/' . $cat['id'] . '/trash')) ?>" data-confirm="ลบ?"><?= H::csrf() ?><button class="adm-btn is-danger-ghost is-sm" type="submit">ลบ</button></form>
            </td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>