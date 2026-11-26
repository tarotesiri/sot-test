<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : ModuleInterface.php
 *    Description : Interface ที่ทุก Module ต้อง implement (Modules\<ชื่อ>\Module) เพื่อลงทะเบียน Route / Widget / Event
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 1.0.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);

namespace App\Core;

interface ModuleInterface
{
    /** ลงทะเบียน Route, Widget, Event listener, View share ของ Module */
    public function register(App $app): void;
}