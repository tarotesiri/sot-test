<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : console.php
 *    Description : เครื่องมือ Command Line: migrate, migrate:status, seed [Module], route:list, about
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 1.0.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);

/*
 * วิธีใช้ (รันที่โฟลเดอร์โปรเจกต์):
 *   php bin/console.php migrate          รัน migration ที่ค้าง
 *   php bin/console.php migrate:status   ดูสถานะ migration
 *   php bin/console.php seed [Module]    ใส่ข้อมูลทดสอบ (ทั้งหมด หรือเฉพาะ Module)
 *   php bin/console.php route:list       ดู Route ทั้งหมด
 *   php bin/console.php about            ข้อมูลระบบ
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

$app = require dirname(__DIR__) . '/core/bootstrap.php';
$migrator = new App\Core\Migrator($app->db(), $app->moduleNames());

switch ($argv[1] ?? 'help') {
    case 'migrate':
        $ran = $migrator->migrate();
        echo $ran ? "รันแล้ว:\n  - " . implode("\n  - ", $ran) . "\n" : "ไม่มี migration ค้าง\n";
        break;

    case 'migrate:status':
        foreach ($migrator->status() as $m) {
            printf("[%s] %-10s %-40s %s\n", $m['ran'] ? 'x' : ' ', $m['module'], $m['migration'], $m['ran_at'] ?? 'ยังไม่รัน');
        }
        break;

    case 'seed':
        $ran = $migrator->seed($argv[2] ?? null);
        echo $ran ? "ใส่ข้อมูลแล้ว:\n  - " . implode("\n  - ", $ran) . "\n" : "ไม่พบไฟล์ seed\n";
        break;

    case 'route:list':
        foreach ($app->router->list() as $r) {
            printf("%-10s %s\n", implode('|', $r['methods']), $r['path']);
        }
        break;

    case 'about':
        $db = $app->db();
        echo 'SIAM Online Training v' . App\Core\App::VERSION . "\n";
        echo 'PHP      : ' . PHP_VERSION . "\n";
        echo 'MySQL    : ' . $db->serverVersion() . "\n";
        echo 'Prefix   : "' . $db->prefix() . "\"\n";
        echo 'Modules  : ' . implode(', ', $app->moduleNames()) . "\n";
        break;

    default:
        echo "คำสั่ง: migrate | migrate:status | seed [Module] | route:list | about\n";
}