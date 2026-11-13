<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : BatchAdminModel.php
 * Description : Batch Model
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.22.0
 * ====================================================================
**/
declare(strict_types=1);

namespace Modules\Admin\Models;

final class BatchAdminModel
{
    public function __construct(private readonly object $db) {}

    private function t(string $name): string
    {
        return (string) $this->db->table($name);
    }

    public function items(int $courseId): array
    {
        // จัดเรียงเริ่มต้นตามสถานะเปิด และวันที่ใกล้ถึง
        return $this->db->fetchAll(
            "SELECT * FROM {$this->t('course_batches')} 
              WHERE course_id = ? 
           ORDER BY (CASE status WHEN 'open' THEN 1 WHEN 'draft' THEN 2 ELSE 3 END) ASC, 
                    id ASC", 
            [$courseId]
        );
    }

    public function find(int $id, int $courseId): ?array
    {
        $row = $this->db->fetch("SELECT * FROM {$this->t('course_batches')} WHERE id = ? AND course_id = ? LIMIT 1", [$id, $courseId]);
        return is_array($row) ? $row : null;
    }

    public function create(array $data): int
    {
        return (int) $this->db->insert('course_batches', $data);
    }

    public function update(int $id, array $data): void
    {
        $this->db->update('course_batches', $data, '`id` = ?', [$id]);
    }

    public function delete(int $id): void
    {
        $this->db->delete('course_batches', '`id` = ?', [$id]);
    }
}