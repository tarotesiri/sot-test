<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : CourseAdminModel.php
 * Description : Course Model
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.22.0
 * ====================================================================
**/
declare(strict_types=1);

namespace Modules\Admin\Models;

final class CourseAdminModel
{
    public function __construct(private readonly object $db) {}

    private function t(string $name): string
    {
        return (string) $this->db->table($name);
    }

    public function find(int $id): ?array
    {
        $course = $this->db->fetch("SELECT * FROM {$this->t('courses')} WHERE id = ? LIMIT 1", [$id]);
        return is_array($course) ? $course : null;
    }

    public function paginate(array $filters, int $page, int $perPage): array
    {
        $c      = $this->t('courses');
        $b      = $this->t('course_batches');
        $where  = [];
        $params = [];
        
        $status = (string) ($filters['status'] ?? '');
        if ($status === 'trashed') {
            $where[] = "c.status = 'trashed'";
        } else {
            $where[] = "c.status <> 'trashed'";
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(c.title LIKE ? OR c.slug LIKE ?)';
            array_push($params, '%' . $q . '%', '%' . $q . '%');
        }

        $sqlWhere = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';
        $total    = (int) $this->db->value("SELECT COUNT(*) FROM {$c} c {$sqlWhere}", $params);
        $perPage  = max(1, $perPage);
        $pages    = max(1, (int) ceil($total / $perPage));
        $page     = min(max(1, $page), $pages);
        $offset   = ($page - 1) * $perPage;

        // ดึงข้อมูลภาพรวมหลักสูตร พร้อมคำนวณจำนวนรุ่นเปิด/ทั้งหมด และวันที่รุ่นยังเปิดที่ใกล้อบรมที่สุด
        $items = $this->db->fetchAll(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM {$b} b WHERE b.course_id = c.id) AS total_batches,
                    (SELECT COUNT(*) FROM {$b} b WHERE b.course_id = c.id AND b.status = 'open') AS open_batches,
                    (SELECT COALESCE(
                        MIN(CASE WHEN b.status = 'open' THEN b.date_start END),
                        MIN(CASE WHEN b.status = 'open' THEN b.date_text END)
                    ) FROM {$b} b WHERE b.course_id = c.id) AS nearest_open_date
               FROM {$c} c
               {$sqlWhere}
           ORDER BY (CASE WHEN (SELECT COUNT(*) FROM {$b} b WHERE b.course_id = c.id AND b.status = 'open') > 0 THEN 0 ELSE 1 END) ASC,
                    c.published_at ASC,
                    c.id DESC
              LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['items' => is_array($items) ? $items : [], 'total' => $total, 'page' => $page, 'pages' => $pages, 'perPage' => $perPage];
    }

    public function statusCounts(): array
    {
        $out = ['all' => 0, 'draft' => 0, 'open' => 0, 'closed' => 0, 'cancelled' => 0, 'trashed' => 0];
        try {
            foreach ($this->db->fetchAll("SELECT status, COUNT(*) AS n FROM {$this->t('courses')} GROUP BY status") as $row) {
                $s       = (string) $row['status'];
                $n       = (int) $row['n'];
                $out[$s] = $n;
                if ($s !== 'trashed') { $out['all'] += $n; }
            }
        } catch (\Throwable) {
            $total = (int) $this->db->value("SELECT COUNT(*) FROM {$this->t('courses')}");
            $out['all'] = $total;
            $out['open'] = $total;
        }
        return $out;
    }

    public function slugExists(string $slug, int $exceptId = 0): bool
    {
        return (int) $this->db->value("SELECT COUNT(*) FROM {$this->t('courses')} WHERE slug = ? AND id <> ?", [$slug, $exceptId]) > 0;
    }

    public function uniqueSlug(string $base, int $exceptId = 0): string
    {
        $base = substr($base, 0, 235);
        $slug = $base;
        $i    = 2;
        while ($this->slugExists($slug, $exceptId)) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    public function create(array $data, array $categoryIds): int
    {
        return (int) $this->db->insert('courses', $data);
    }

    public function update(int $id, array $data, array $categoryIds): void
    {
        $this->db->update('courses', $data, '`id` = ?', [$id]);
    }

    public function trash(int $id): void
    {
        try {
            $this->db->query("UPDATE {$this->t('courses')} SET status = 'trashed' WHERE id = ?", [$id]);
        } catch (\Throwable) {
            $this->db->delete('courses', 'id = ?', [$id]);
        }
    }

    public function restore(int $id): void
    {
        try {
            $this->db->query("UPDATE {$this->t('courses')} SET status = 'draft' WHERE id = ?", [$id]);
        } catch (\Throwable) {}
    }

    public function forceDelete(int $id): bool
    {
        return (int) $this->db->delete('courses', '`id` = ?', [$id]) > 0;
    }
}