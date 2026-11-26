<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : Controller.php
 *    Description : Base Controller: render View, ตรวจข้อมูล (validate แล้วเด้งกลับพร้อม error), ส่ง JSON และเข้าถึง Database/Settings
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

abstract class Controller
{
    public function __construct(protected readonly App $app) {}

    protected function view(string $view, array $data = [], ?string $layout = 'layout'): string
    {
        return $this->app->view->render($view, $data, $layout);
    }

    protected function request(): Request
    {
        return $this->app->request;
    }

    protected function db(): Database
    {
        return $this->app->db();
    }

    protected function setting(string $key, mixed $default = null): mixed
    {
        return $this->app->settings()->get($key, $default);
    }

    /**
     * ตรวจข้อมูลจากฟอร์ม — ไม่ผ่านจะ redirect กลับหน้าเดิมพร้อม error + ค่าเดิม
     * AJAX จะได้ JSON 422 แทน
     */
    protected function validate(array $rules, array $labels = []): array
    {
        $v = Validator::make($this->request()->all(), $rules, $labels);
        if ($v->fails()) {
            if ($this->request()->wantsJson()) {
                $this->json(['ok' => false, 'errors' => $v->errors()], 422);
            }
            Session::flashInput($this->request()->all());
            Session::flashErrors($v->errors());
            back();
        }
        return $v->validated();
    }

    protected function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}