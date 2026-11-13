<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : footer.php
 *    Description : Partial ส่วนท้ายเว็บ (Footer ส่วนบนพื้นขาว / Footer ส่วนล่างใต้เส้น พื้นเขียวเข้มแบบกะทัดรัด)
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 1.2.1
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);
$siteName = (string) setting('site_name', env('APP_NAME', 'SIAM Training'));
$logo = setting('logo_path');
$links = config('menu')['footer'] ?? [];
$contacts = [
    ['map-pin', setting('contact_address', ''), null],
    ['phone', setting('contact_phone', ''), 'tel:' . preg_replace('/[^0-9+]/', '', (string) setting('contact_phone', ''))],
    ['mail', setting('contact_email', ''), 'mailto:' . setting('contact_email', '')],
    ['message', setting('contact_line', '') ? 'LINE: ' . setting('contact_line') : '', null],
    ['clock', setting('office_hours', ''), null],
];
?>
<footer class="site-footer">
    <div class="container footer-top">
        <div>
            <div class="footer-brand">
                <img src="<?= e($logo ? url((string) $logo) : asset('img/logo.svg')) ?>" alt="">
                <strong><?= e($siteName) ?></strong>
            </div>
            <p><?= e(setting('footer_about', '')) ?></p>
        </div>
        <div>
            <h4>เมนูลัด</h4>
            <ul class="footer-links">
                <?php foreach ($links as $l): ?><li><a href="<?= e(url($l['url'])) ?>"><?= e($l['label']) ?></a></li><?php endforeach; ?>
            </ul>
        </div>
        <div>
            <h4>ติดต่อเรา</h4>
            <ul class="footer-contact">
                <?php foreach ($contacts as [$ic, $text, $href]): if ((string) $text === '') continue; ?>
                <li><?= icon($ic) ?><span><?php if ($href): ?><a href="<?= e($href) ?>"><?= e($text) ?></a><?php else: ?><?= e($text) ?><?php endif; ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <!-- ส่วนท้าย (2 ใต้เส้น): สีเขียวเข้มแบบบางกระชับ -->
    <div class="footer-bottom-wrap">
        <div class="container footer-bottom">
            <span>© <?= (int) date('Y') + 543 ?> <?= e($siteName) ?> สงวนลิขสิทธิ์</span>
            <span>Online Training &amp; LMS Platform</span>
        </div>
    </div>
</footer>