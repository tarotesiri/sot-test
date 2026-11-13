<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : CourseModel.php
 * Description : Course Model for Catalog Module
 * ====================================================================
**/
declare(strict_types=1);

namespace Modules\Catalog\Models;

use App\Core\Model;

final class CourseModel extends Model
{
    protected static string $table = 'courses';

    public function paginateBatches(array $filters, int $page = 1, int $perPage = 9): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = '(c.title LIKE ? OR c.summary LIKE ? OR c.code LIKE ? OR b.batch_no LIKE ?)';
            $q = '%' . trim($filters['q']) . '%';
            array_push($params, $q, $q, $q, $q);
        }
        if (!empty($filters['type'])) {
            $where[] = 'c.type = ?';
            $params[] = $filters['type'];
        }
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'open') {
                $where[] = "c.status = 'open' AND b.status = 'open'";
            } elseif ($filters['status'] === 'closed') {
                $where[] = "(c.status = 'closed' OR b.status = 'closed')";
            }
        }

        $sqlWhere = implode(' AND ', $where);

        $orderBy = "b.id DESC"; 
        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'nearest':
                    $orderBy = "b.id ASC";
                    break;
                case 'furthest':
                    $orderBy = "b.id DESC";
                    break;
                case 'title':
                    $orderBy = "c.title ASC";
                    break;
            }
        }
        
        // ใช้ LEAST และ GREATEST เฉพาะราคาที่มีค่า > 0 เพื่อหาช่วงราคาที่แท้จริง (รองรับ 1, 2 หรือ 4 ราคา)
        $sql = "SELECT b.id AS batch_id, b.course_id, b.batch_no, b.date_text, b.duration_text, 
                       b.location_text, b.description AS batch_description, b.price_type, 
                       b.payment_type, b.status, b.time_text, 
                       b.brochure_image, b.gallery_images, b.document_file, b.document_title, 
                       b.price_1, b.price_2, b.price_3, b.price_4, 
                       b.limit_enabled_1, b.capacity_1, b.status_1,
                       b.limit_enabled_2, b.capacity_2, b.status_2,
                       b.limit_enabled_3, b.capacity_3, b.status_3,
                       b.limit_enabled_4, b.capacity_4, b.status_4,
                       LEAST(
                           NULLIF(b.price_1, 0), 
                           COALESCE(NULLIF(b.price_2, 0), NULLIF(b.price_1, 0)), 
                           COALESCE(NULLIF(b.price_3, 0), NULLIF(b.price_1, 0)), 
                           COALESCE(NULLIF(b.price_4, 0), NULLIF(b.price_1, 0))
                       ) AS min_price,
                       GREATEST(
                           COALESCE(NULLIF(b.price_4, 0), 0), 
                           COALESCE(NULLIF(b.price_3, 0), 0), 
                           COALESCE(NULLIF(b.price_2, 0), 0), 
                           COALESCE(NULLIF(b.price_1, 0), 0)
                       ) AS max_price,
                       c.title, c.slug, c.code, c.type, c.summary, c.description AS course_description, 
                       c.cover_image, c.brochure_file, c.status AS course_status,
                       cat.name AS category_name, cat.slug AS category_slug
                FROM {prefix}course_batches b
                JOIN {$this->t()} c ON b.course_id = c.id
                LEFT JOIN {prefix}course_categories cat ON c.category_id = cat.id
                WHERE {$sqlWhere}
                ORDER BY {$orderBy}";

        return $this->db->paginate($sql, $params, $page, $perPage);
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->db->fetch(
            "SELECT c.*, cat.name AS category_name, cat.slug AS category_slug
             FROM {$this->t()} c
             LEFT JOIN {prefix}course_categories cat ON c.category_id = cat.id
             WHERE c.slug = ? LIMIT 1",
            [$slug]
        );
    }

    public function getBatches(int $courseId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM {prefix}course_batches WHERE course_id = ? ORDER BY id ASC",
            [$courseId]
        );
    }

    public function getDocuments(int $courseId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM {prefix}course_documents WHERE course_id = ? ORDER BY id DESC",
            [$courseId]
        );
    }

    public function getCategories(): array
    {
        return $this->db->fetchAll("SELECT * FROM {prefix}course_categories ORDER BY sort_order ASC, name ASC");
    }
}