<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : AuthController.php
 * Description : จัดการระบบสมาชิก (Login, Register, Logout)
 * ====================================================================
 * Author : TaRote SiRi
 * Created Date : 2026-11-01
 * Version : 1.0.0
 * License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ====================================================================
 */
declare(strict_types=1);

namespace Modules\Auth\Controllers;

// ... (โค้ดเมธอดเดิมของคุณด้านบนคงไว้ตามปกติ แล้วเพิ่มเมธอด logout นี้เข้าไปด้านล่าง)

    /**
     * ออกจากระบบ (Logout)
     */
    public function logout(): void
    {
        // ล้างค่า Session ที่เกี่ยวข้องกับการล็อกอินทั้งหมด
        unset($_SESSION['user_id']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_name']);
        unset($_SESSION['user_role']);
        unset($_SESSION['user_logged_in']);
        
        $_SESSION['auth_success'] = 'ออกจากระบบเรียบร้อยแล้ว';
        
        if (function_exists('redirect')) {
            redirect(url('auth/login'));
        } else {
            header('Location: ' . url('auth/login'));
        }
        exit;
    }