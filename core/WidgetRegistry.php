<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : WidgetRegistry.php
 *    Description : ระบบ Widget Slot ให้ Module เสียบเนื้อหาลงหน้าเว็บ (เช่น หน้าแรก slide/online/clip) โดยไม่ต้องแก้ Layout หลัก
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
 * register('home', 'online', fn(array $ctx) => '...html...', 30)
 * ลงทะเบียน slot + id เดิมซ้ำ = แทนที่ของเดิม (Module ที่โหลดทีหลังชนะ)
 */
final class WidgetRegistry
{
    private array $slots = [];

    public function register(string $slot, string $id, callable $render, int $order = 10): void
    {
        $this->slots[$slot][$id] = ['order' => $order, 'render' => $render];
    }

    public function remove(string $slot, string $id): void
    {
        unset($this->slots[$slot][$id]);
    }

    public function has(string $slot, ?string $id = null): bool
    {
        return $id === null ? !empty($this->slots[$slot]) : isset($this->slots[$slot][$id]);
    }

    public function list(string $slot): array
    {
        $items = $this->slots[$slot] ?? [];
        uasort($items, static fn (array $a, array $b): int => $a['order'] <=> $b['order']);
        return $items;
    }

    public function render(string $slot, array $context = []): string
    {
        $html = '';
        foreach ($this->list($slot) as $id => $widget) {
            try {
                $html .= (string) ($widget['render'])($context);
            } catch (\Throwable $e) {
                ErrorHandler::log($e); // Widget พังต้องไม่ทำให้ทั้งหน้าพัง
                if (ErrorHandler::debug()) {
                    $html .= '<div class="container"><div class="alert alert--error"><div class="alert__body">Widget "'
                        . e($slot . '.' . $id) . '" ผิดพลาด: ' . e($e->getMessage()) . '</div></div></div>';
                }
            }
        }
        return $html;
    }
}