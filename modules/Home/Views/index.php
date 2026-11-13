<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : index.php
 *    Description : View หน้าแรกของระบบ: มี Widget Slots (slide, online, clip) รองรับการเสียบเนื้อหาจาก Module ถัดไป
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 1.0.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);
$siteName = (string) setting('site_name', 'SIAM Online Training');
?>
<!-- 1) Slide Slot (M14 จะเข้ามาแทนที่) -->
<?= $this->widgets('home.slide', [
    'default' => '<section class="hero"><div class="slider"><div class="slider__track">'
        . '<div class="slide slide--green"><div class="container slide__content">'
        . '<span class="slide__eyebrow">ศูนย์ฝึกอบรมออนไลน์</span>'
        . '<h2 class="slide__title">' . e($siteName) . '</h2>'
        . '<p class="slide__text">ยกระดับศักยภาพบุคลากรและองค์กรด้วยหลักสูตรฝึกอบรมคุณภาพ ทั้งรูปแบบ <strong class="hl">Live Online</strong> และ <strong class="hl">Clip VDO</strong> เรียนได้ทุกที่ ออกใบเซอร์อัตโนมัติ</p>'
        . '<div class="btn-group"><a class="btn btn--white" href="' . e(url('/courses')) . '">' . icon('book-open') . ' ดูหลักสูตรทั้งหมด</a>'
        . '<a class="btn btn--blue" href="' . e(url('/system/status')) . '">' . icon('check-circle') . ' ตรวจสถานะระบบ</a></div>'
        . '</div></div></div></div></section>'
]) ?>

<!-- 2) Online Course Slot (M1 จะเข้ามาแทนที่แสดงหลักสูตร Live Online ล่าสุด) -->
<?= $this->widgets('home.online', [
    'default' => '<section class="section"><div class="container">'
        . '<div class="section__head"><div><h2 class="section__title">หลักสูตร <span class="hl">Live Online</span> ยอดนิยม</h2>'
        . '<p class="section__subtitle">อบรมสดผ่านระบบออนไลน์ พร้อมวิทยากรผู้เชี่ยวชาญ (ข้อมูลหลักสูตรจริงจะแสดงใน Module 1)</p></div>'
        . '<a class="btn btn--outline btn--sm" href="' . e(url('/courses')) . '">ดูทั้งหมด ' . icon('arrow-right') . '</a></div>'
        . '<div class="empty-state">หลักสูตรจะแสดงผลเมื่อติดตั้ง <strong class="hl">Module 1 (Course Catalog)</strong> เรียบร้อยแล้ว</div>'
        . '</div></section>'
]) ?>

<!-- 3) Clip VDO Slot (M8/M14 จะเข้ามาแทนที่) -->
<?= $this->widgets('home.clip', [
    'default' => '<section class="section section--alt"><div class="container">'
        . '<div class="section__head"><div><h2 class="section__title">หลักสูตร <span class="hl">Clip VDO</span> เรียนได้ทันที</h2>'
        . '<p class="section__subtitle">เรียนทบทวนได้ตลอด 24 ชั่วโมง ภายในระยะเวลาที่กำหนด</p></div></div>'
        . '<div class="empty-state">หลักสูตร Clip VDO จะแสดงผลใน Module ถัดไป</div>'
        . '</div></section>'
]) ?>