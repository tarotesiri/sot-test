<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : modules.php
 *    Description : รายชื่อ Module ที่เปิดใช้งานตามลำดับ (Module ที่โหลดทีหลังสามารถแทนที่ Route/Widget ของ Module ก่อนหน้าได้)
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
    'Home',     // M0 : หน้าแรก + Widget Slots + สถานะระบบ
    'Roadmap',  // เพิ่ม Roadmap เข้ามา
    'Catalog',  // M1 : หลักสูตรและรายละเอียด
    'Auth',     // M2 : สมาชิกและบัญชีผู้ใช้
    'Admin',    // M3 : ระบบหลังบ้าน
    'Cart',		// M4 : ระบบตะกร้า
];