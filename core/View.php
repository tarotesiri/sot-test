<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : View.php
 *    Description : Template Engine (PHP ล้วน): render View ของ Module, Theme Override, Partial/Component, Widget Slot และ Stack สำหรับแทรก CSS/JS รายหน้า
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

/**
 * ชื่อ View:
 *  - 'Home::index' -> themes/<theme>/modules/Home/index.php (ถ้ามี = override)
 *                     หรือ modules/Home/Views/index.php
 *  - 'errors/404'  -> themes/<theme>/errors/404.php (fallback themes/default)
 *
 * ภายในไฟล์ View ใช้ได้:
 *  - $this->partial('header')               ใช้ข้อมูลของหน้าปัจจุบัน
 *  - $this->component('field', [...])       ใช้เฉพาะข้อมูลที่ส่งเข้าไป
 *  - $this->widgets('home')                 แสดง Widget ใน slot
 *  - $this->startPush('scripts') ... $this->endPush()   แทรก JS/CSS เฉพาะหน้า
 *  - $this->stack('scripts')                แสดงผลที่ push ไว้ (ใช้ใน layout)
 */
final class View
{
    private array $shared = [];
    private array $current = [];
    private array $stacks = [];
    private array $pushing = [];

    public function __construct(private readonly string $theme) {}

    public function theme(): string
    {
        return $this->theme;
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    public function shared(string $key, mixed $default = null): mixed
    {
        return $this->shared[$key] ?? $default;
    }

    public function exists(string $view): bool
    {
        try {
            $this->resolve($view);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function render(string $view, array $data = [], ?string $layout = 'layout'): string
    {
        $data = array_merge($this->shared, $data);
        $previous = $this->current;
        $this->current = $data;
        try {
            $content = $this->renderFile($this->resolve($view), $data);
            if ($layout === null) {
                return $content;
            }
            return $this->renderFile($this->themeFile($layout), $data + ['content' => $content]);
        } finally {
            $this->current = $previous;
        }
    }

    public function partial(string $name, array $data = []): string
    {
        return $this->renderFile($this->themeFile('partials/' . $name), array_merge($this->shared, $this->current, $data));
    }

    public function component(string $name, array $data = []): string
    {
        return $this->renderFile($this->themeFile('partials/' . $name), $data);
    }

    public function widgets(string $slot, array $context = []): string
    {
        return App::instance()?->widgets->render($slot, $context) ?? '';
    }

    /* ---------------- Stacks ---------------- */

    public function push(string $name, string $html): void
    {
        $this->stacks[$name][] = $html;
    }

    public function startPush(string $name): void
    {
        $this->pushing[] = $name;
        ob_start();
    }

    public function endPush(): void
    {
        $name = array_pop($this->pushing);
        if ($name === null) {
            throw new \LogicException('endPush() ถูกเรียกโดยไม่มี startPush()');
        }
        $this->stacks[$name][] = (string) ob_get_clean();
    }

    public function stack(string $name): string
    {
        return implode("\n", $this->stacks[$name] ?? []);
    }

    /* ---------------- Internal ---------------- */

    private function resolve(string $view): string
    {
        $this->guard($view);
        if (!str_contains($view, '::')) {
            return $this->themeFile($view);
        }
        [$module, $path] = explode('::', $view, 2);
        foreach ([
            BASE_PATH . "/themes/{$this->theme}/modules/{$module}/{$path}.php",
            BASE_PATH . "/modules/{$module}/Views/{$path}.php",
        ] as $file) {
            if (is_file($file)) {
                return $file;
            }
        }
        throw new \RuntimeException("ไม่พบไฟล์ View: {$view}");
    }

    private function themeFile(string $relative): string
    {
        $this->guard($relative);
        foreach ([
            BASE_PATH . "/themes/{$this->theme}/{$relative}.php",
            BASE_PATH . "/themes/default/{$relative}.php",
        ] as $file) {
            if (is_file($file)) {
                return $file;
            }
        }
        throw new \RuntimeException("ไม่พบไฟล์ Theme: {$relative}");
    }

    private function guard(string $name): void
    {
        if (!preg_match('#^[A-Za-z0-9_/\-:.]+$#', $name) || str_contains($name, '..')) {
            throw new \InvalidArgumentException("ชื่อ view ไม่ถูกต้อง: {$name}");
        }
    }

    private function renderFile(string $__file, array $__data): string
    {
        unset($__data['this'], $__data['__file'], $__data['__data']);
        extract($__data, EXTR_SKIP);
        $__level = ob_get_level();
        ob_start();
        try {
            include $__file;
        } catch (\Throwable $ex) {
            while (ob_get_level() > $__level) {
                ob_end_clean();
            }
            throw $ex;
        }
        return (string) ob_get_clean();
    }
}