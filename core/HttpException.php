<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : HttpException.php
 *    Description : Exception สำหรับ HTTP Status (403, 404, 405, 419, 429 ฯลฯ) ใช้ผ่านฟังก์ชัน abort()
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

final class HttpException extends \RuntimeException
{
    public function __construct(public readonly int $status, string $message = '')
    {
        parent::__construct($message, $status);
    }
}