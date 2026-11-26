<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : AuthController.php
 *    Description : Controller สำหรับจัดการระบบสมาชิก (Login & Recaptcha Verification)
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-10-29
 *    Version : 1.0.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);

namespace Modules\Auth\Controllers;

use App\Core\App;
use App\Core\Controller;
use Modules\Auth\Models\UserModel;


final class AuthController extends Controller
{
    public function loginProcess(): void
{
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $recaptcha = $_POST['recaptcha_check'] ?? '';
    
    $redirectTo = $_POST['redirect_to'] ?? '';

    if (empty($recaptcha)) {
        $_SESSION['auth_error'] = 'กรุณายืนยันว่าคุณไม่ใช่โปรแกรมอัตโนมัติ';
        redirect(url('auth/login'));
        return;
    }

    if (empty($email) || empty($password)) {
        $_SESSION['auth_error'] = 'กรุณากรอกอีเมลและรหัสผ่านให้ครบถ้วน';
        redirect(url('auth/login'));
        return;
    }

    try {
        $model = new \Modules\Auth\Models\UserModel($this->db());
        $user = $model->findByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['firstname'] . ' ' . $user['lastname'];
            $_SESSION['user_role'] = $user['role'];

            unset($_SESSION['auth_error']);

            if (!empty($redirectTo)) {
                redirect($redirectTo);
            } else {
                redirect(url('dashboard'));
            }
            return;

        } else {
            $_SESSION['auth_error'] = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
            redirect(url('auth/login'));
            return;
        }

    } catch (\Throwable $e) {
        $_SESSION['auth_error'] = 'เกิดข้อผิดพลาดในระบบ: ' . $e->getMessage();
        redirect(url('auth/login'));
        return;
    }
}
    
    public function registerProcess(): void
	{
	    $email                 = trim($_POST['email'] ?? '');
        $prefix                = trim($_POST['prefix'] ?? '');
	    $firstname             = trim($_POST['firstname'] ?? '');
	    $lastname              = trim($_POST['lastname'] ?? '');
        $phone                 = trim($_POST['phone'] ?? '');
        $idCard                = trim($_POST['id_card'] ?? '');
        $position              = trim($_POST['position'] ?? '');
        $otherRequirements     = trim($_POST['other_requirements'] ?? '');
	    $password              = $_POST['password'] ?? '';
	    $passwordConfirmation  = $_POST['password_confirmation'] ?? '';
	    $recaptcha             = $_POST['register_recaptcha'] ?? '';

	    if (empty($email) || empty($firstname) || empty($lastname) || empty($password)) {
	        $_SESSION['auth_error'] = 'กรุณากรอกข้อมูลให้ครบถ้วน';
	        redirect(url('auth/register'));
	        return;
	    }

	    if ($password !== $passwordConfirmation) {
	        $_SESSION['auth_error'] = 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน';
	        redirect(url('auth/register'));
	        return;
	    }

	    if (empty($recaptcha) || $recaptcha !== '1') {
	        $_SESSION['auth_error'] = 'กรุณายืนยันว่าคุณไม่ใช่โปรแกรมอัตโนมัติ';
	        redirect(url('auth/register'));
	        return;
	    }
	    
	    try {
	        $model = new \Modules\Auth\Models\UserModel($this->db());
	        
	        $existingUser = $model->findByEmail($email);
	        if ($existingUser) {
	            $_SESSION['auth_error'] = 'อีเมลนี้ถูกใช้งานในระบบแล้ว กรุณาใช่อีเมลอื่น';
	            redirect(url('auth/register'));
	            return;
	        }

	        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
	        
	        $userId = $model->create([
			    'email'              => $email,
                'prefix'             => $prefix,
			    'firstname'          => $firstname,
			    'lastname'           => $lastname,
                'phone'              => $phone,
                'id_card'            => $idCard,
                'position'           => $position,
                'other_requirements' => $otherRequirements,
			    'password'           => $hashedPassword,
			    'role'               => 'user',
			    'created_at'         => date('Y-m-d H:i:s')
			]);

	        if ($userId) {
	            $_SESSION['user_id'] = $userId;
	            $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $firstname . ' ' . $lastname;
	            
	            unset($_SESSION['auth_error']);
	            redirect(url('auth/login')); 
	            return;
	        } else {
	            $_SESSION['auth_error'] = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล';
	            redirect(url('auth/register'));
	            return;
	        }

	    } catch (\Throwable $e) {
	        $_SESSION['auth_error'] = 'เกิดข้อผิดพลาดในระบบ: ' . $e->getMessage();
	        redirect(url('auth/register'));
	        return;
	    }
	}
    
    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), 
                '', 
                time() - 42000,
                $params["path"], 
                $params["domain"],
                $params["secure"], 
                $params["httponly"]
            );
        }

        session_destroy();

        redirect(url('auth/login'));
        return;
    }
    
    public function forgotProcess(): void
    {
        $email = $_POST['email'] ?? '';

        if (empty($email)) {
            $_SESSION['forgot_error'] = 'กรุณากรอกอีเมลของคุณ';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        if ($email === 'admin@sot.com' || filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['forgot_success'] = 'ระบบได้ส่งลิงก์รีเซ็ตรหัสผ่านไปที่อีเมลของคุณแล้ว (จำลอง)';
        } else {
            $_SESSION['forgot_error'] = 'ไม่พบอีเมลนี้ในระบบฐานข้อมูล';
        }

        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
    
    public function showLoginForm(): void
    {
        $activeTab = 'login'; 

        echo view('Auth::login', [
            'activeTab' => $activeTab
        ]);
    }
    
    public function showRegisterForm(): void
    {
        $activeTab = 'register'; 

        echo view('Auth::login', [
            'activeTab' => $activeTab
        ]);
    }
    
	public function setRedirectSession(): void
	{
	    $redirectUrl = $_POST['redirect_url'] ?? '';
	    
	    if (empty($redirectUrl)) {
	        $json = json_decode(file_get_contents('php://input'), true);
	        $redirectUrl = $json['redirect_url'] ?? '';
	    }

	    if (!empty($redirectUrl)) {
	        $_SESSION['redirect_after_login'] = $redirectUrl;
	    }

	    header('Content-Type: application/json');
	    echo json_encode(['status' => 'success']);
	}
}