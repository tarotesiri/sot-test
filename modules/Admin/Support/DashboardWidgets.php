<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : DashboardWidgets.php
 * Description : Dashboard Widgets
 * ====================================================================
 * Author : TaRote SiRi
 * Created Date : 2026-11-01
 * Version : 1.1.0
 * ====================================================================
**/
declare(strict_types=1);

namespace Modules\Admin\Support;

use Modules\Admin\Models\CourseAdminModel;
use Throwable;

final class DashboardWidgets
{
    private static array $widgets = [];
    private static bool $booted   = false;

    public static function register(string $name, callable $render, int $priority = 50): void
    {
        self::$widgets[$name] = [$priority, $render];
    }

    public static function registerDefaults(): void
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;
        self::register('course_stats', static fn (object $db): string => AdminView::render('widgets/course_stats', [
            'stats' => (new CourseAdminModel($db))->stats(),
        ]), 10);
        self::register('upcoming_sessions', static fn (object $db): string => AdminView::render('widgets/upcoming_sessions', [
            'sessions' => (new CourseAdminModel($db))->upcomingSessions(8),
        ]), 20);
        self::register('latest_courses', static fn (object $db): string => AdminView::render('widgets/latest_courses', [
            'courses' => (new CourseAdminModel($db))->latest(6),
        ]), 30);
    }

    public static function render(object $db): string
    {
        self::registerDefaults();
        $list = self::$widgets;
        uasort($list, static fn (array $a, array $b): int => $a[0] <=> $b[0]);
        $html = '';
        foreach ($list as $name => [, $render]) {
            try {
                $html .= (string) $render($db);
            } catch (Throwable $e) {
                $html .= '<div class="adm-alert is-error">Widget "' . AdminHelper::e($name) . '" โหลดไม่สำเร็จ: ' . AdminHelper::e($e->getMessage()) . '</div>';
            }
        }
        return $html;
    }
}