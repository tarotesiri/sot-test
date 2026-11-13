<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : CourseChildModel.php
 * Description : Course Child Model
 * ====================================================================
 * Author : TaRote SiRi
 * Created Date : 2026-11-01
 * Version : 1.1.0
 * ====================================================================
**/
declare(strict_types=1);

namespace Modules\Admin\Models;

use InvalidArgumentException;

final class CourseChildModel
{
    private const TABLES = [
        'session'  => 'course_sessions',
        'price'    => 'course_prices',
        'document' => 'course_documents',
        'image'    => 'course_images',
    ];

    private const ORDER = [
        'session'  => 'session_no ASC, start_date ASC, id ASC',
        'price'    => "FIELD(price_type, 'standard', 'member', 'early_bird', 'member_early_bird'), id ASC",
        'document' => 'id ASC',
        'image'    => 'sort_order ASC, id ASC',
    ];

    public function __construct(private readonly object $db) {}

    private function name(string $kind): string
    {
        if (!isset(self::TABLES[$kind])) {
            throw new InvalidArgumentException('ไม่รู้จักประเภทข้อมูล: ' . $kind);
        }
        return self::TABLES[$kind];
    }

    private function t(string $kind): string
    {
        return (string) $this->db->table($this->name($kind));
    }

    public function items(string $kind, int $courseId): array
    {
        return $this->db->fetchAll("SELECT * FROM {$this->t($kind)} WHERE course_id = ? ORDER BY " . self::ORDER[$kind], [$courseId]);
    }

    public function find(string $kind, int $id, int $courseId): ?array
    {
        $row = $this->db->fetch("SELECT * FROM {$this->t($kind)} WHERE id = ? AND course_id = ? LIMIT 1", [$id, $courseId]);
        return is_array($row) ? $row : null;
    }

    public function create(string $kind, array $data): int
    {
        return (int) $this->db->insert($this->name($kind), $data);
    }

    public function update(string $kind, int $id, array $data): void
    {
        $this->db->update($this->name($kind), $data, '`id` = ?', [$id]);
    }

    public function delete(string $kind, int $id): void
    {
        $this->db->delete($this->name($kind), '`id` = ?', [$id]);
    }

    public function count(string $kind, int $courseId): int
    {
        return (int) $this->db->value("SELECT COUNT(*) FROM {$this->t($kind)} WHERE course_id = ?", [$courseId]);
    }

    public function nextNumber(string $kind, int $courseId): int
    {
        $col = $kind === 'session' ? 'session_no' : 'sort_order';
        return (int) $this->db->value("SELECT COALESCE(MAX({$col}), 0) FROM {$this->t($kind)} WHERE course_id = ?", [$courseId]) + 1;
    }

    public function isSessionInUse(int $sessionId): bool
    {
        return false;
    }
}