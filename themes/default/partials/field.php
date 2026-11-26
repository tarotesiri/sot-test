<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : field.php
 *    Description : Component ฟอร์ม (Input) พร้อมดึง Error และ Old input อัตโนมัติ
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 1.0.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);
$name = $name ?? '';
$id = $id ?? $name;
$type = $type ?? 'text';
$err = error_for($name);
$val = old($name, $value ?? '');
?>
<div class="form-group">
    <label class="form-label" for="<?= e($id) ?>"><?= e($label ?? $name) ?><?= !empty($required) ? ' <span class="req">*</span>' : '' ?></label>
    <input class="form-control<?= $err ? ' is-invalid' : '' ?>" type="<?= e($type) ?>" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e(is_scalar($val) ? (string) $val : '') ?>"<?= !empty($placeholder) ? ' placeholder="' . e($placeholder) . '"' : '' ?>>
    <?php if ($err): ?><div class="form-error"><?= e($err) ?></div><?php elseif (!empty($hint)): ?><div class="form-hint"><?= e($hint) ?></div><?php endif; ?>
</div>