<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : App.php
 *    Description : ตัวกลางของระบบ (Application Container): ถือ Router/View/Widgets/Request, เชื่อม Database/Settings, โหลด Module และระบบ Event (on/emit)
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

final class App
{
    public const VERSION = '1.0.0';

    private static ?self $instance = null;

    public readonly Router $router;
    public readonly WidgetRegistry $widgets;
    public readonly View $view;
    public readonly Request $request;

    private ?Database $db = null;
    private ?Settings $settings = null;
    /** @var array<string, ModuleInterface> */
    private array $modules = [];
    /** @var array<string, array<int, callable>> */
    private array $listeners = [];

    private function __construct()
    {
        $this->router  = new Router();
        $this->widgets = new WidgetRegistry();
        $this->view    = new View((string) Env::get('APP_THEME', 'default'));
        $this->request = Request::capture();
    }

    public static function boot(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
            Session::start();
            self::$instance->loadModules();
        }
        return self::$instance;
    }

    public static function instance(): ?self
    {
        return self::$instance;
    }

    /* ---------------- Services ---------------- */

    public function db(): Database
    {
        return $this->db ??= Database::fromEnv();
    }

    public function settings(): Settings
    {
        return $this->settings ??= new Settings($this->db());
    }

    /* ---------------- Modules ---------------- */

    /** @return string[] */
    public function moduleNames(): array
    {
        return array_keys($this->modules);
    }

    public function hasModule(string $name): bool
    {
        return isset($this->modules[$name]);
    }

    private function loadModules(): void
    {
        foreach ((array) require BASE_PATH . '/config/modules.php' as $name) {
            if (!is_string($name) || !preg_match('/^[A-Z][A-Za-z0-9]*$/', $name)) {
                throw new \RuntimeException('ชื่อ Module ไม่ถูกต้องใน config/modules.php');
            }
            $class = "Modules\\{$name}\\Module";
            if (!class_exists($class)) {
                throw new \RuntimeException("ไม่พบ Module: {$name} (ต้องมีไฟล์ modules/{$name}/Module.php)");
            }
            $module = new $class();
            if (!$module instanceof ModuleInterface) {
                throw new \RuntimeException("{$class} ต้อง implement App\\Core\\ModuleInterface");
            }
            $module->register($this);
            $this->modules[$name] = $module;
        }
    }

    /* ---------------- Events ----------------
     * $app->on('order.created', fn(array $p) => ...);
     * $app->emit('order.created', ['order_id' => 10]);
     */
    public function on(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    public function emit(string $event, array $payload = []): void
    {
        foreach ($this->listeners[$event] ?? [] as $listener) {
            try {
                $listener($payload, $event);
            } catch (\Throwable $e) {
                ErrorHandler::log($e); // Event ล้มเหลวต้องไม่ทำให้งานหลักพัง
            }
        }
    }

    /* ---------------- Run ---------------- */

    public function run(): void
    {
        $this->sendSecurityHeaders();
        try {
            $output = $this->router->dispatch($this->request);
            if (is_string($output)) {
                echo $output;
            }
        } catch (HttpException $e) {
            http_response_code($e->status);
            echo $this->renderError($e->status, $e->getMessage());
        }
    }

    public function renderError(int $status, string $message = ''): string
    {
        $titles = [
            400 => 'คำขอไม่ถูกต้อง',
            403 => 'ไม่มีสิทธิ์เข้าถึง',
            404 => 'ไม่พบหน้าที่ต้องการ',
            405 => 'ไม่รองรับวิธีการเรียกนี้',
            419 => 'แบบฟอร์มหมดอายุ',
            429 => 'มีการเรียกใช้งานถี่เกินไป',
        ];
        $title = $titles[$status] ?? 'เกิดข้อผิดพลาด';

        if ($this->request->wantsJson()) {
            header('Content-Type: application/json; charset=utf-8');
            return (string) json_encode(['ok' => false, 'status' => $status, 'message' => $message !== '' ? $message : $title], JSON_UNESCAPED_UNICODE);
        }
        return $this->view->render($status === 404 ? 'errors/404' : 'errors/error', [
            'title' => $title, 'status' => $status, 'message' => $message,
        ]);
    }

    private function sendSecurityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    }
}