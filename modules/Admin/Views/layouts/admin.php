<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : admin.php
 * Description : Layout
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.10.0
 * ====================================================================
**/
declare(strict_types=1);

use Modules\Admin\Support\AdminHelper as H;
$menu = [
    'dashboard'     => ['แดชบอร์ด', 'admin'],
    'courses'       => ['หลักสูตรทั้งหมด', 'admin/courses'],
    'batches'       => ['หลักสูตร (แยกตามรุ่นวันที่อบรม)', 'admin/batches'],
    'orders'        => ['คำสั่งซื้อทั้งหมด', 'admin/orders'],
    'order_batches' => ['คำสั่งซื้อ (แยกตามรุ่นวันที่อบรม)', 'admin/order_batches'],
    'payments'      => ['รายการแจ้งชำระเงิน', 'admin/payments'],
    'categories'    => ['หมวดหมู่ (Tags)', 'admin/categories'],
    'settings'      => ['ตั้งค่ามาตรฐาน', 'admin/settings'],
    'trash'         => ['ถังขยะ', 'admin/trash'],
];

$reqPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '';
$reqPath = trim($reqPath, '/');
$activeKey = (string)($active ?? '');

if ($activeKey === '' && $reqPath !== '') {
    foreach ($menu as $mKey => [, $mPath]) {
        $normMenuPath = trim($mPath, '/');
        if ($normMenuPath !== '' && (str_ends_with($reqPath, '/' . $normMenuPath) || $reqPath === $normMenuPath)) {
            $activeKey = $mKey;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= H::e($pageTitle ?? 'Admin') ?> | SIAM Training</title>
    <link rel="stylesheet" href="<?= H::e(H::asset('admin.css')) ?>">
</head>
<body class="adm">
<div class="adm-shell">
    <aside class="adm-sidebar">
        <div class="adm-brand"><strong>SIAM Training</strong><small>หลังบ้าน</small></div>
        <nav class="adm-nav">
            <?php foreach ($menu as $key => [$label, $path]): ?>
                <a class="adm-nav-link<?= $activeKey === $key ? ' is-active' : '' ?>" href="<?= H::e(H::url($path)) ?>"><?= H::e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="adm-sidebar-foot"><a href="<?= H::e(H::url('')) ?>" target="_blank">ดูหน้าเว็บ &#8599;</a></div>
    </aside>
    <div class="adm-main">
        <header class="adm-topbar">
            <div class="adm-top-title"><?= H::e($pageTitle ?? '') ?></div>
            <span><?= H::e(H::userName()) ?></span>
        </header>
        <main class="adm-content">
            <?php foreach (H::pullFlash() as $f): ?>
                <div class="adm-alert is-<?= H::e($f['type']) ?>" style="padding:12px 16px;border-radius:8px;margin-bottom:16px;background:<?= $f['type']==='success'?'#dcfce7':'#fee2e2' ?>;color:<?= $f['type']==='success'?'#166534':'#991b1b' ?>;border:1px solid <?= $f['type']==='success'?'#bbf7d0':'#fecaca' ?>"><?= H::e($f['message']) ?></div>
            <?php endforeach; ?>
            <?= $content ?? '' ?>
        </main>
    </div>
</div>
<script>
document.addEventListener('submit', function (e) {
    var msg = e.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) { e.preventDefault(); }
}, true);
</script>
</body>
</html>