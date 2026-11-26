<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : Migrator.php
 *    Description : รันไฟล์ SQL Migration/Seed ของ Core และทุก Module ตามลำดับ แทนค่า {prefix} อัตโนมัติ และบันทึกประวัติในตาราง {prefix}migrations
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
 * ลำดับการรัน: core/migrations -> modules/<ตามลำดับใน config/modules.php>/migrations
 * ไฟล์ SQL ใช้ {prefix} แทนคำนำหน้าตาราง เช่น CREATE TABLE {prefix}courses (...)
 * กฎ: ห้ามแก้ไฟล์ migration ที่รันไปแล้ว ถ้าต้องเปลี่ยนโครงสร้างให้สร้างไฟล์ใหม่ (ALTER)
 * Seed (ข้อมูลทดสอบ) อยู่ใน <source>/seeds/*.sql ต้องเขียนให้รันซ้ำได้ (INSERT IGNORE)
 */
final class Migrator
{
    public function __construct(
        private readonly Database $db,
        private readonly array $modules
    ) {}

    /** @return array<string, string> [ชื่อ => โฟลเดอร์] */
    public function sources(): array
    {
        $src = ['Core' => BASE_PATH . '/core'];
        foreach ($this->modules as $m) {
            if (is_string($m) && preg_match('/^[A-Z][A-Za-z0-9]*$/', $m)) {
                $src[$m] = BASE_PATH . "/modules/{$m}";
            }
        }
        return $src;
    }

    public function status(): array
    {
        $this->ensureTable();
        $ran = [];
        foreach ($this->db->fetchAll('SELECT module, migration, batch, ran_at FROM {prefix}migrations') as $r) {
            $ran[$r['module'] . '/' . $r['migration']] = $r;
        }
        $list = [];
        foreach ($this->sources() as $module => $dir) {
            $files = glob($dir . '/migrations/*.sql') ?: [];
            sort($files);
            foreach ($files as $file) {
                $r = $ran[$module . '/' . basename($file)] ?? null;
                $list[] = [
                    'module'    => $module,
                    'migration' => basename($file),
                    'file'      => $file,
                    'ran'       => $r !== null,
                    'batch'     => $r['batch'] ?? null,
                    'ran_at'    => $r['ran_at'] ?? null,
                ];
            }
        }
        return $list;
    }

    public function pending(): array
    {
        return array_values(array_filter($this->status(), static fn (array $m): bool => !$m['ran']));
    }

    /** @return string[] รายการที่รันในรอบนี้ */
    public function migrate(): array
    {
        $pending = $this->pending();
        if ($pending === []) {
            return [];
        }
        $batch = (int) $this->db->value('SELECT COALESCE(MAX(batch), 0) FROM {prefix}migrations') + 1;
        $done = [];
        foreach ($pending as $m) {
            $this->runSqlFile($m['file']);
            $this->db->insert('migrations', ['module' => $m['module'], 'migration' => $m['migration'], 'batch' => $batch]);
            $done[] = $m['module'] . '/' . $m['migration'];
        }
        return $done;
    }

    /** @return string[] */
    public function seed(?string $only = null): array
    {
        $done = [];
        foreach ($this->sources() as $module => $dir) {
            if ($only !== null && strcasecmp($only, $module) !== 0) {
                continue;
            }
            $files = glob($dir . '/seeds/*.sql') ?: [];
            sort($files);
            foreach ($files as $file) {
                $this->runSqlFile($file);
                $done[] = $module . '/' . basename($file);
            }
        }
        return $done;
    }

    /** รันไฟล์ SQL (แทน {prefix} ให้) คืนจำนวนคำสั่งที่รัน */
    public function runSqlFile(string $file): int
    {
        $sql = (string) file_get_contents($file);
        $count = 0;
        foreach (self::split($this->db->sql($sql)) as $i => $stmt) {
            try {
                $this->db->pdo()->exec($stmt);
                $count++;
            } catch (\PDOException $e) {
                throw new \RuntimeException(
                    'SQL ผิดพลาดในไฟล์ ' . basename($file) . ' (คำสั่งที่ ' . ($i + 1) . '): ' . $e->getMessage(),
                    0,
                    $e
                );
            }
        }
        return $count;
    }

    /** ตัด comment แล้วแยกคำสั่งด้วย ; ท้ายบรรทัด */
    public static function split(string $sql): array
    {
        $sql = str_replace("\r\n", "\n", $sql);
        $sql = (string) preg_replace('#/\*(?!!).*?\*/#s', '', $sql);   // /* ... */ (ยกเว้น /*! ... */)
        $sql = (string) preg_replace('/^\s*--.*$/m', '', $sql);        // -- comment ทั้งบรรทัด
        $parts = preg_split('/;\s*(?:\n|$)/', $sql) ?: [];
        return array_values(array_filter(array_map('trim', $parts), static fn (string $s): bool => $s !== ''));
    }

    private function ensureTable(): void
    {
        $this->db->pdo()->exec($this->db->sql(
            "CREATE TABLE IF NOT EXISTS {prefix}migrations (
                id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                module     VARCHAR(50)  NOT NULL,
                migration  VARCHAR(190) NOT NULL,
                batch      INT UNSIGNED NOT NULL,
                ran_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_migration (module, migration)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ));
    }
}