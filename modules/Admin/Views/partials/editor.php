<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : editor.php
 * Description : Editor Partial
 * ====================================================================
 * Author : TaRote SiRi
 * Created Date : 2026-11-01
 * Version : 1.1.0
 * ====================================================================
**/
declare(strict_types=1);

use Modules\Admin\Support\AdminHelper as H;
$editorName  = $editorName ?? 'description';
$editorValue = $editorValue ?? '';
?>
<textarea class="adm-input adm-editor" id="<?= H::e($editorName) ?>" name="<?= H::e($editorName) ?>" rows="12"><?= H::e($editorValue) ?></textarea>
<p class="adm-help">พิมพ์ได้หลายบรรทัด (รองรับ HTML)</p>