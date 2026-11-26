<?php
/**
 * ==============================================================================
 * Project Name : SIAM Online Training
 * File Name    : DashboardController.php
 * Description  : Controller สำหรับจัดการหน้าแดชบอร์ดผู้ใช้งาน
 * ==============================================================================
 * Author       : TaRote SiRi
 * Email        : *******.***@*****.***
 * Created Date : 2026-11-01
 * Version      : 2.1.0
 * License      : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ==============================================================================
 */

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use App\Core\App;
use PDO;
use ReflectionObject;
use RuntimeException;
use Throwable;

final class DashboardController
{
    private ?string $tablePrefix = null;

    private function resolvePdo($db): PDO
    {
        if ($db instanceof PDO) {
            return $db;
        }

        if (is_object($db)) {
            if (method_exists($db, 'pdo')) {
                $pdo = $db->pdo();
                if ($pdo instanceof PDO) {
                    return $pdo;
                }
            }
            if (method_exists($db, 'getPdo')) {
                $pdo = $db->getPdo();
                if ($pdo instanceof PDO) {
                    return $pdo;
                }
            }
            if (isset($db->pdo) && $db->pdo instanceof PDO) {
                return $db->pdo;
            }

            $ref = new ReflectionObject($db);
            foreach ($ref->getProperties() as $prop) {
                $prop->setAccessible(true);
                $val = $prop->getValue($db);
                if ($val instanceof PDO) {
                    return $val;
                }
            }
        }

        throw new RuntimeException('ไม่สามารถดึง PDO instance จากฐานข้อมูลได้');
    }

    private function resolveTablePrefix(PDO $pdo): string
    {
        if ($this->tablePrefix !== null) {
            return $this->tablePrefix;
        }

        foreach (['DB_PREFIX', 'TABLE_PREFIX', 'SOT_DB_PREFIX'] as $constName) {
            if (defined($constName)) {
                $val = (string)constant($constName);
                if ($val !== '') {
                    return $this->tablePrefix = $val;
                }
            }
        }

        if (!empty($GLOBALS['db_prefix']) && is_string($GLOBALS['db_prefix'])) {
            return $this->tablePrefix = $GLOBALS['db_prefix'];
        }
        if (!empty($GLOBALS['config']['db']['prefix']) && is_string($GLOBALS['config']['db']['prefix'])) {
            return $this->tablePrefix = $GLOBALS['config']['db']['prefix'];
        }
        if (!empty($GLOBALS['config']['prefix']) && is_string($GLOBALS['config']['prefix'])) {
            return $this->tablePrefix = $GLOBALS['config']['prefix'];
        }

        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '%\\_orders'");
            if ($stmt) {
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
                foreach ($tables as $tbl) {
                    $tblName = (string)$tbl;
                    if (preg_match('/^(.*_)orders$/', $tblName, $m)) {
                        return $this->tablePrefix = $m[1];
                    }
                }
            }
        } catch (Throwable $e) {
            // ข้ามหากไม่สามารถคิวรี SHOW TABLES ได้
        }

        return $this->tablePrefix = 'sot_';
    }

    private function table(PDO $pdo, string $baseName): string
    {
        $cleanBase = ltrim($baseName, '_');
        $prefix = $this->resolveTablePrefix($pdo);

        if ($prefix !== '' && str_starts_with($cleanBase, $prefix)) {
            return $cleanBase;
        }

        return $prefix . $cleanBase;
    }

    private function getTableColumns(PDO $pdo, string $baseOrFullTableName): array
    {
        static $cache = [];
        $tableName = $this->table($pdo, $baseOrFullTableName);

        if (isset($cache[$tableName])) {
            return $cache[$tableName];
        }

        $columns = [];
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM `{$tableName}`");
            if ($stmt) {
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    if (!empty($row['Field'])) {
                        $columns[(string)$row['Field']] = true;
                    }
                }
            }
        } catch (Throwable $e) {
            $columns = [];
        }

        return $cache[$tableName] = $columns;
    }

    public function index(): void
    {
        if (empty($_SESSION['user_id'])) {
            redirect(url('auth/login'));
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        $summary = [
            'total_orders'     => 0,
            'approved_courses' => 0,
            'pending_orders'   => 0,
            'recent_orders'    => [],
        ];

        try {
            $app = App::instance();
            $dbObj = method_exists($app, 'db') ? $app->db() : ($GLOBALS['db'] ?? null);
            if ($dbObj !== null) {
                $pdo = $this->resolvePdo($dbObj);
                $tOrders = $this->table($pdo, 'orders');
                $tEnrollments = $this->table($pdo, 'enrollments');
                $tParticipants = $this->table($pdo, 'participants');

                $stmtOrdCount = $pdo->prepare("
                    SELECT
                        COUNT(*) AS total_orders,
                        SUM(CASE WHEN `payment_status` = 'paid' THEN 1 ELSE 0 END) AS paid_orders,
                        SUM(CASE WHEN `payment_status` IN ('pending', 'awaiting_verification', 'pending_approval') THEN 1 ELSE 0 END) AS pending_orders
                    FROM `{$tOrders}`
                    WHERE `user_id` = :uid
                ");
                $stmtOrdCount->execute([':uid' => $userId]);
                $ordCounts = $stmtOrdCount->fetch(PDO::FETCH_ASSOC) ?: [];
                $summary['total_orders'] = (int)($ordCounts['total_orders'] ?? 0);
                $summary['pending_orders'] = (int)($ordCounts['pending_orders'] ?? 0);

                $enrCols = $this->getTableColumns($pdo, 'enrollments');
                $enrConditions = [];
                $enrParams = [];

                if (isset($enrCols['learner_user_id'])) {
                    $enrConditions[] = "e.`learner_user_id` = :uid_learner";
                    $enrParams[':uid_learner'] = $userId;
                }
                if (isset($enrCols['payer_user_id'])) {
                    $enrConditions[] = "e.`payer_user_id` = :uid_payer";
                    $enrParams[':uid_payer'] = $userId;
                }
                $enrConditions[] = "o.`user_id` = :uid_order";
                $enrParams[':uid_order'] = $userId;

                $payConds = ["o.`payment_status` = 'paid'"];
                if (isset($enrCols['payment_status'])) {
                    $payConds = ["(e.`payment_status` = 'paid' OR o.`payment_status` = 'paid')"];
                }

                $stmtEnrCount = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM `{$tEnrollments}` e
                    INNER JOIN `{$tOrders}` o ON o.`id` = e.`order_id`
                    LEFT JOIN `{$tParticipants}` p ON p.`id` = e.`participant_id`
                    WHERE " . implode(' AND ', $payConds) . "
                      AND (" . implode(' OR ', $enrConditions) . ")
                ");
                $stmtEnrCount->execute($enrParams);
                $summary['approved_courses'] = (int)$stmtEnrCount->fetchColumn();

                $stmtRecent = $pdo->prepare("
                    SELECT `id`, `order_no`, `grand_total`, `payment_status`, `created_at`
                    FROM `{$tOrders}`
                    WHERE `user_id` = :uid
                    ORDER BY `id` DESC
                    LIMIT 5
                ");
                $stmtRecent->execute([':uid' => $userId]);
                $summary['recent_orders'] = $stmtRecent->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }
        } catch (Throwable $e) {
            // คงค่าเริ่มต้นของ summary หากไม่สามารถดึงข้อมูลสรุปได้
        }

        echo App::instance()->view->render('Auth::dashboard', [
            'title'   => 'Dashboard - ภาพรวมระบบ',
            'summary' => $summary,
        ]);
    }
}