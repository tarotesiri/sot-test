<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : Model.php
 *    Description : Base Model สำหรับทุก Module: กำหนด $table (ไม่ต้องใส่ prefix) แล้วใช้ find/findBy/create/updateById/deleteById/count ได้ทันที
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
 * ตัวอย่าง:
 *   final class CourseModel extends Model { protected static string $table = 'courses'; }
 *   $m = new CourseModel(app()->db());
 *   $m->find(1);  $m->findBy('slug', 'abc');
 */
abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    public function __construct(protected readonly Database $db) {}

    public static function tableName(): string
    {
        return static::$table;
    }

    /** ชื่อตารางเต็ม (มี prefix + backtick) สำหรับเขียน SQL เอง */
    protected function t(): string
    {
        return $this->db->table(static::$table);
    }

    protected function pk(): string
    {
        return Database::ident(static::$primaryKey);
    }

    public function find(int $id): ?array
    {
        return $this->db->fetch("SELECT * FROM {$this->t()} WHERE {$this->pk()} = ? LIMIT 1", [$id]);
    }

    public function findBy(string $column, mixed $value): ?array
    {
        $col = Database::ident($column);
        return $this->db->fetch("SELECT * FROM {$this->t()} WHERE {$col} = ? LIMIT 1", [$value]);
    }

    public function create(array $data): int
    {
        return $this->db->insert(static::$table, $data);
    }

    public function updateById(int $id, array $data): int
    {
        return $this->db->update(static::$table, $data, $this->pk() . ' = ?', [$id]);
    }

    public function deleteById(int $id): int
    {
        return $this->db->delete(static::$table, $this->pk() . ' = ?', [$id]);
    }

    public function count(string $where = '1 = 1', array $params = []): int
    {
        return (int) $this->db->value("SELECT COUNT(*) FROM {$this->t()} WHERE {$where}", $params);
    }
}