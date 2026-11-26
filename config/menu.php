<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : menu.php
 *    Description : เมนูหลักและเมนูท้ายเว็บไซต์ (M14 จะย้ายไปจัดการผ่านฐานข้อมูลหลังบ้าน)
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 1.0.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);

return [
    'main' => [
        ['label' => 'หน้าแรก', 'url' => '/'],
        ['label' => 'หลักสูตรทั้งหมด', 'url' => '/courses', 'children' => [
            ['label' => 'Live Online', 'url' => '/courses?type=live_online'],
            ['label' => 'Clip VDO', 'url' => '/courses?type=clip_vdo'],
        ]],
        ['label' => 'แจ้งชำระเงิน', 'url' => '/payment'],
        ['label' => 'Roadmap', 'url' => '/roadmap'], // เพิ่มเมนู Roadmap ตรงนี้
		['label' => 'ตรวจสอบสถานะ', 'url' => '/system/status'],
    ],
    'footer' => [
        ['label' => 'หลักสูตรทั้งหมด', 'url' => '/courses'],
        ['label' => 'สถานะระบบ', 'url' => '/system/status'],
        ['label' => 'ตรวจสอบใบรับรอง', 'url' => '/verify'],
    ],
];