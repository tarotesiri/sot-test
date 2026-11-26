<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : Database.php
 *    Description : PDO Wrapper รองรับ Table Prefix ({prefix}) ใช้ Prepared Statement ทุกคำสั่ง พร้อม insert/update/delete/paginate/transaction
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
 * วิธีใช้:
 *   $db->fetchAll('SELECT * FROM {prefix}courses WHERE status = ?', ['open']);
 *   $db->insert('courses', ['title' => 'ABC']);          // เติม prefix ให้อัตโนมัติ
 *   $db->update('courses', ['title' => 'X'], 'id = ?', [5]);
 *   $db->paginate('SELECT * FROM {prefix}courses ORDER BY id DESC', [], $page, 12);
 *
 * หมายเหตุ: ใช้ Native Prepare จึงห้ามใช้ชื่อ :param ซ้ำในคำสั่งเดียว
 *           ชื่อตาราง/คอลัมน์ห้ามรับจากผู้ใช้โดยตรง (ใช้ ident() ตรวจ)
 */
final class Database
{
    private \PDO $pdo;
    private readonly string $prefix;
    private int $queryCount = 0;

    public function __construct(
        string $host,
        int $port,
        string $name,
        string $user,
        string $pass,
        string $timezone = '+07:00',
        string $prefix = ''
    ) {
        if (!preg_match('/^[A-Za-z0-9_]*$/', $prefix)) {
            throw new \InvalidArgumentException('DB_PREFIX ไม่ถูกต้อง (ใช้ได้เฉพาะ A-Z a-z 0-9 _)');
        }
        $this->prefix = $prefix;

        $this->pdo = new \PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass, [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
            \PDO::ATTR_STRINGIFY_FETCHES  => false,
        ]);
        $this->pdo->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
        if (preg_match('/^[+-]\d{2}:\d{2}$/', $timezone)) {
            $this->pdo->exec("SET time_zone = '{$timezone}'");
        }
    }

    public static function fromEnv(): self
    {
        return new self(
            (string) Env::get('DB_HOST', '127.0.0.1'),
            (int) Env::get('DB_PORT', 3306),
            (string) Env::get('DB_DATABASE', ''),
            (string) Env::get('DB_USERNAME', 'root'),
            (string) (Env::get('DB_PASSWORD', '') ?? ''),
            (string) Env::get('DB_TIMEZONE', '+07:00'),
            (string) (Env::get('DB_PREFIX', '') ?? ''),
        );
    }

    /* ---------------- Prefix ---------------- */

    public function prefix(): string
    {
        return $this->prefix;
    }

    /** ชื่อตารางแบบมี prefix + backtick เช่น `sot_courses` */
    public function table(string $name): string
    {
        return self::ident($this->prefix . $name);
    }

    /** แทนค่า {prefix} ใน SQL */
    public function sql(string $sql): string
    {
        return str_replace('{prefix}', $this->prefix, $sql);
    }

    /* ---------------- Query ---------------- */

    public function pdo(): \PDO
    {
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $st = $this->pdo->prepare($this->sql($sql));
        $isList = array_is_list($params);
        foreach ($params as $key => $value) {
            $param = $isList ? $key + 1 : ':' . ltrim((string) $key, ':');
            [$bind, $type] = match (true) {
                is_bool($value)                     => [(int) $value, \PDO::PARAM_INT],
                is_int($value)                      => [$value, \PDO::PARAM_INT],
                $value === null                     => [null, \PDO::PARAM_NULL],
                $value instanceof \DateTimeInterface => [$value->format('Y-m-d H:i:s'), \PDO::PARAM_STR],
                default                             => [(string) $value, \PDO::PARAM_STR],
            };
            $st->bindValue($param, $bind, $type);
        }
        $st->execute();
        $this->queryCount++;
        return $st;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /** ค่าคอลัมน์แรกของแถวแรก */
    public function value(string $sql, array $params = []): mixed
    {
        $v = $this->query($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    /** ค่าคอลัมน์แรกของทุกแถว */
    public function column(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll(\PDO::FETCH_COLUMN);
    }

    /** [คอลัมน์1 => คอลัมน์2] เช่นใช้ทำ dropdown */
    public function pairs(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    public function exists(string $sql, array $params = []): bool
    {
        return $this->value($sql, $params) !== null;
    }

    /* ---------------- CRUD (เติม prefix อัตโนมัติ) ---------------- */

    public function insert(string $table, array $data): int
    {
        if ($data === []) {
            throw new \InvalidArgumentException('insert() ต้องมีข้อมูลอย่างน้อย 1 คอลัมน์');
        }
        $cols  = implode(', ', array_map([self::class, 'ident'], array_keys($data)));
        $marks = implode(', ', array_fill(0, count($data), '?'));
        $this->query('INSERT INTO ' . $this->table($table) . " ({$cols}) VALUES ({$marks})", array_values($data));
        return (int) $this->pdo->lastInsertId();
    }

    /** $where เขียนโดยนักพัฒนาเท่านั้น ค่าจากผู้ใช้ต้องส่งผ่าน $params */
    public function update(string $table, array $data, string $where, array $params = []): int
    {
        if ($data === []) {
            return 0;
        }
        $set = implode(', ', array_map(static fn (string $c): string => self::ident($c) . ' = ?', array_keys($data)));
        return $this->query(
            'UPDATE ' . $this->table($table) . " SET {$set} WHERE {$where}",
            [...array_values($data), ...array_values($params)]
        )->rowCount();
    }

    public function delete(string $table, string $where, array $params = []): int
    {
        return $this->query('DELETE FROM ' . $this->table($table) . " WHERE {$where}", array_values($params))->rowCount();
    }

    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * แบ่งหน้า: คืน ['items','total','page','pages','perPage','from','to']
     * $sql ห้ามมี LIMIT
     */
    public function paginate(string $sql, array $params = [], int $page = 1, int $perPage = 12): array
    {
        $perPage = max(1, min(200, $perPage));
        $total   = (int) $this->value("SELECT COUNT(*) FROM ({$sql}) AS _pg_count", $params);
        $pages   = max(1, (int) ceil($total / $perPage));
        $page    = max(1, min($page, $pages));
        $offset  = ($page - 1) * $perPage;
        $items   = $total > 0 ? $this->fetchAll("{$sql} LIMIT {$perPage} OFFSET {$offset}", $params) : [];
        return [
            'items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages, 'perPage' => $perPage,
            'from'  => $total > 0 ? $offset + 1 : 0, 'to' => $offset + count($items),
        ];
    }

    /** รองรับการเรียกซ้อน (ถ้าอยู่ใน transaction แล้วจะไม่เปิดใหม่) */
    public function transaction(callable $fn): mixed
    {
        if ($this->pdo->inTransaction()) {
            return $fn($this);
        }
        $this->pdo->beginTransaction();
        try {
            $result = $fn($this);
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /* ---------------- Info ---------------- */

    /** ตรวจว่ามีตารางหรือไม่ (ส่งชื่อแบบไม่มี prefix) */
    public function tableExists(string $name): bool
    {
        return (int) $this->value(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$this->prefix . $name]
        ) > 0;
    }

    public function serverVersion(): string
    {
        return (string) $this->pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }

    public function queryCount(): int
    {
        return $this->queryCount;
    }

    /** ตรวจชื่อตาราง/คอลัมน์ แล้วครอบด้วย backtick */
    public static function ident(string $name): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException("ชื่อ identifier ไม่ถูกต้อง: {$name}");
        }
        return "`{$name}`";
    }
}