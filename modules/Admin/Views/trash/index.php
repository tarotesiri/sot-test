<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : index.php
 * Description : Trash
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.4.0
 * ====================================================================
**/
declare(strict_types=1);

use Modules\Admin\Support\AdminHelper as H;
$courses = $courses ?? [];
?>
<div class="adm-page-head"><h1>ถังขยะ</h1></div>
<div class="adm-card">
    <div class="adm-card-head"><h2>หลักสูตรในถังขยะ</h2></div>
    <table class="adm-table">
        <thead><tr><th>หลักสูตร</th><th style="width:160px"></th></tr></thead>
        <tbody>
        <?php if($courses === []): ?><tr><td colspan="2" class="adm-empty">ไม่มีข้อมูล</td></tr><?php endif; ?>
        <?php foreach($courses as $c): ?>
            <tr><td><?= H::e($c['title']) ?></td><td>
                <form style="display:inline" method="post" action="<?= H::e(H::url('admin/courses/' . $c['id'] . '/restore')) ?>"><?= H::csrf() ?><button class="adm-btn is-sm is-primary" type="submit">กู้คืน</button></form>
                <form style="display:inline" method="post" action="<?= H::e(H::url('admin/courses/' . $c['id'] . '/destroy')) ?>" data-confirm="ลบถาวร?"><?= H::csrf() ?><input type="hidden" name="confirm" value="1"><button class="adm-btn is-sm is-danger" type="submit">ลบถาวร</button></form>
            </td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>