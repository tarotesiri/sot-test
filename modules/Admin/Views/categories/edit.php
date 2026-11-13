<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : edit.php
 * Description : Edit Category
 * ====================================================================
 * Author : TaRote SiRi
 * Created Date : 2026-11-01
 * Version : 1.2.0
 * ====================================================================
**/
declare(strict_types=1);

use Modules\Admin\Support\AdminHelper as H;
$category = $category ?? [];
?>
<div class="adm-page-head"><h1>แก้ไขหมวดหมู่</h1><a class="adm-btn is-light" href="<?= H::e(H::url('admin/categories')) ?>">&larr; กลับ</a></div>
<form class="adm-card" method="post" action="<?= H::e(H::url('admin/categories/' . (int) $category['id'] . '/update')) ?>" style="max-width:500px">
    <?= H::csrf() ?>
    <div class="adm-card-body">
        <label class="adm-field">
            <span class="adm-label">ชื่อหมวดหมู่ *</span>
            <input class="adm-input" name="name" maxlength="150" required value="<?= H::e($category['name']) ?>">
        </label>
        <label class="adm-field" style="margin-top:10px">
            <span class="adm-label">Slug</span>
            <input class="adm-input" name="slug" maxlength="150" value="<?= H::e($category['slug']) ?>">
        </label>
    </div>
    <div class="adm-form-foot"><button class="adm-btn is-primary" type="submit">บันทึก</button></div>
</form>