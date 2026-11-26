<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : CategoryAdminModel.php
 * Description : Category Model
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.22.0
 * ====================================================================
**/
declare(strict_types=1);

namespace Modules\Admin\Models;

final class CategoryAdminModel
{
    public function __construct(private readonly object $db) {}

    private function t(string $name): string
    {
        return (string) $this->db->table($name);
    }

    public function all(string $status = 'active'): array
    {
        try {
            return $this->db->fetchAll("SELECT * FROM {$this->t('course_categories')} WHERE status = ? ORDER BY sort_order ASC, name ASC", [$status]);
        } catch (\Throwable) {
            return $this->db->fetchAll("SELECT * FROM {$this->t('course_categories')} ORDER BY id ASC");
        }
    }

    public function options(): array
    {
        try {
            return $this->db->fetchAll("SELECT id, name FROM {$this->t('course_categories')} WHERE status = 'active' ORDER BY name ASC");
        } catch (\Throwable) {
            return $this->db->fetchAll("SELECT id, name FROM {$this->t('course_categories')} ORDER BY id ASC");
        }
    }

    public function find(int $id): ?array
    {
        $row = $this->db->fetch("SELECT * FROM {$this->t('course_categories')} WHERE id = ? LIMIT 1", [$id]);
        return is_array($row) ? $row : null;
    }

    public function slugExists(string $slug, int $exceptId = 0): bool
    {
        return (int) $this->db->value("SELECT COUNT(*) FROM {$this->t('course_categories')} WHERE slug = ? AND id <> ?", [$slug, $exceptId]) > 0;
    }

    public function uniqueSlug(string $base, int $exceptId = 0): string
    {
        $base = substr($base, 0, 140);
        $slug = $base;
        $i    = 2;
        while ($this->slugExists($slug, $exceptId)) { $slug = $base . '-' . $i++; }
        return $slug;
    }

    public function create(array $data): int { return (int) $this->db->insert('course_categories', $data); }
    public function update(int $id, array $data): void { $this->db->update('course_categories', $data, '`id` = ?', [$id]); }
    public function trash(int $id): void { 
        try { $this->db->query("UPDATE {$this->t('course_categories')} SET status = 'trashed' WHERE id = ?", [$id]); } 
        catch (\Throwable) { $this->db->delete('course_categories', 'id = ?', [$id]); } 
    }
    public function restore(int $id): void { 
        try { $this->db->query("UPDATE {$this->t('course_categories')} SET status = 'active' WHERE id = ?", [$id]); } catch (\Throwable) {} 
    }
    public function forceDelete(int $id): bool { return (int) $this->db->delete('course_categories', '`id` = ?', [$id]) > 0; }
}