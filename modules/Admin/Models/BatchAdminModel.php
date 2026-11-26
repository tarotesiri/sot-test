<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : BatchAdminModel.php
 * Description : Batch Model
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.27.0
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
        return $this->db->fetchAll(
            "SELECT * FROM {$this->t('course_batches')} 
              WHERE course_id = ? 
           ORDER BY (CASE WHEN status IN ('closed', 'cancelled') THEN 1 ELSE 0 END) ASC,
                    (CASE WHEN status NOT IN ('closed', 'cancelled') THEN (date_start IS NULL) ELSE 0 END) ASC,
                    (CASE WHEN status NOT IN ('closed', 'cancelled') THEN date_start END) ASC,
                    (CASE WHEN status IN ('closed', 'cancelled') THEN (date_start IS NULL) ELSE 0 END) ASC,
                    (CASE WHEN status IN ('closed', 'cancelled') THEN date_start END) DESC,
                    id DESC",
            [$courseId]
        );
    }

    public function allItems(string $q = '', string $status = ''): array
    {
        $b   = $this->t('course_batches');
        $c   = $this->t('courses');
        $e   = $this->t('enrollments');
        $oi  = $this->t('order_items');
        $o   = $this->t('orders');
        $oai = $this->t('order_attendees');

        $where  = ["c.status <> 'trashed'"];
        $params = [];

        if ($status !== '') {
            $where[]  = 'b.status = ?';
            $params[] = $status;
        }

        $qTrim = trim($q);
        if ($qTrim !== '') {
            $where[] = '(c.title LIKE ? OR b.batch_no LIKE ?)';
            $like = '%' . $qTrim . '%';
            array_push($params, $like, $like);
        }

        $sqlWhere = 'WHERE ' . implode(' AND ', $where);

        $getCols = function (string $tableName): array {
            $cols = [];
            try {
                foreach ($this->db->fetchAll("SHOW COLUMNS FROM {$tableName}") as $colRow) {
                    if (!empty($colRow['Field'])) {
                        $cols[(string)$colRow['Field']] = true;
                    }
                }
            } catch (\Throwable) {
                $cols = [];
            }
            return $cols;
        };

        $enrCols = $getCols($e);
        $oiCols  = $getCols($oi);
        $oCols   = $getCols($o);
        $oaiCols = $getCols($oai);

        // กลุ่ม "จำนวนผู้เข้าอบรม": อนุมัติ / ผ่าน / ปริ้น
        $notCancelledCond = isset($enrCols['status'])
            ? "COALESCE(sub_e.status, '') NOT IN ('cancelled', 'refunded')"
            : "1=1";

        $approvedExpr = "0";
        if (isset($enrCols['payment_status'])) {
            $approvedExpr = "SUM(CASE WHEN sub_e.payment_status = 'paid' AND ({$notCancelledCond}) THEN 1 ELSE 0 END)";
        } elseif (isset($enrCols['status'])) {
            $approvedExpr = "SUM(CASE WHEN sub_e.status IN ('approved', 'active', 'paid') THEN 1 ELSE 0 END)";
        }

        $passedExpr = "0";
        $passConds  = [];
        if (isset($enrCols['attendance_status'])) {
            $passConds[] = "sub_e.attendance_status = 'passed'";
        }
        if (isset($enrCols['exam_passed'])) {
            $passConds[] = "sub_e.exam_passed = 1";
        }
        if (isset($enrCols['pass_status'])) {
            $passConds[] = "sub_e.pass_status IN ('passed', 'pass')";
        }
        if (isset($enrCols['exam_status'])) {
            $passConds[] = "sub_e.exam_status IN ('passed', 'pass')";
        }
        if ($passConds !== []) {
            $passedExpr = "SUM(CASE WHEN ({$notCancelledCond}) AND (" . implode(' OR ', $passConds) . ") THEN 1 ELSE 0 END)";
        }

        $printedExpr = "0";
        $printConds  = [];
        if (isset($enrCols['certificate_printed_at'])) {
            $printConds[] = "sub_e.certificate_printed_at IS NOT NULL";
        }
        if (isset($enrCols['cert_printed_at'])) {
            $printConds[] = "sub_e.cert_printed_at IS NOT NULL";
        }
        if (isset($enrCols['certificate_code'])) {
            $printConds[] = "(sub_e.certificate_code IS NOT NULL AND TRIM(sub_e.certificate_code) <> '')";
        }
        if ($printConds !== []) {
            $printedExpr = "SUM(CASE WHEN ({$notCancelledCond}) AND (" . implode(' OR ', $printConds) . ") THEN 1 ELSE 0 END)";
        }

        $statsJoin   = '';
        $statsSelect = "0 AS approved_count, 0 AS passed_count, 0 AS printed_count";
        if (!empty($enrCols) && isset($enrCols['course_id'])) {
            $hasBatchCol   = isset($enrCols['batch_id']);
            $batchGroupCol = $hasBatchCol ? "sub_e.batch_id" : "NULL AS batch_id";
            $batchGroupBy  = $hasBatchCol ? ", sub_e.batch_id" : "";
            $batchJoinCond = $hasBatchCol
                ? " AND (es.batch_id = b.id OR (es.batch_id IS NULL AND b.id IS NULL))"
                : "";

            $statsJoin = "
                LEFT JOIN (
                    SELECT
                        sub_e.course_id,
                        {$batchGroupCol},
                        {$approvedExpr} AS approved_count,
                        {$passedExpr} AS passed_count,
                        {$printedExpr} AS printed_count
                    FROM {$e} sub_e
                    GROUP BY sub_e.course_id{$batchGroupBy}
                ) es ON es.course_id = c.id{$batchJoinCond}
            ";
            $statsSelect = "
                COALESCE(es.approved_count, 0) AS approved_count,
                COALESCE(es.passed_count, 0) AS passed_count,
                COALESCE(es.printed_count, 0) AS printed_count
            ";
        }

        // คอลัมน์ "Oreder รออนุมัติ": นับจำนวน Order และจำนวนผู้เข้าอบรมใน Order ที่รุ่นยังไม่อนุมัติและคำสั่งซื้อไม่ถูกยกเลิก/คืนเงิน
        $pendingJoin   = '';
        $pendingSelect = "0 AS pending_approval_count, 0 AS order_pending_orders_count, 0 AS order_pending_attendees_count";
        if (!empty($oiCols) && isset($oiCols['course_id'])) {
            $attCountExpr = "0";
            $attJoinSql   = "";
            if (!empty($oaiCols) && isset($oaiCols['order_item_id'])) {
                $attJoinSql = " LEFT JOIN (
                    SELECT order_item_id, COUNT(*) AS att_cnt
                    FROM {$oai}
                    GROUP BY order_item_id
                ) att_sub ON att_sub.order_item_id = sub_oi.id";
                $attCountExpr = "COALESCE(att_sub.att_cnt, 0)";
            }

            $qtyFallback = "1";
            foreach (['quantity', 'qty', 'attendee_count', 'attendees_count'] as $qCol) {
                if (isset($oiCols[$qCol])) {
                    $qtyFallback = "COALESCE(NULLIF(sub_oi.{$qCol}, 0), 1)";
                    break;
                }
            }

            $headcountExpr = ($attCountExpr !== "0")
                ? "CASE WHEN {$attCountExpr} > 0 THEN {$attCountExpr} ELSE {$qtyFallback} END"
                : $qtyFallback;

            $orderCountExpr = "COUNT(sub_oi.id)";
            $joinOrderSql   = '';
            $pendingConds   = [];

            if (isset($oiCols['batch_approval_status'])) {
                $pendingConds[] = "COALESCE(sub_oi.batch_approval_status, 'pending') <> 'approved'";
            }

            if (isset($oiCols['order_id']) && !empty($oCols) && isset($oCols['id'])) {
                $joinOrderSql = " INNER JOIN {$o} sub_o ON sub_o.id = sub_oi.order_id";
                $orderCountExpr = "COUNT(DISTINCT sub_oi.order_id)";
                if (isset($oCols['payment_status'])) {
                    $pendingConds[] = "COALESCE(sub_o.payment_status, 'pending') NOT IN ('cancelled', 'refunded')";
                }
            }

            $pendingWhereSql = $pendingConds !== []
                ? "WHERE " . implode(' AND ', $pendingConds)
                : "";

            $hasOiBatchCol   = isset($oiCols['batch_id']);
            $oiBatchGroupCol = $hasOiBatchCol ? "sub_oi.batch_id" : "NULL AS batch_id";
            $oiBatchGroupBy  = $hasOiBatchCol ? ", sub_oi.batch_id" : "";
            $oiBatchJoinCond = $hasOiBatchCol
                ? " AND (po.batch_id = b.id OR (po.batch_id IS NULL AND b.id IS NULL))"
                : "";

            $pendingJoin = "
                LEFT JOIN (
                    SELECT
                        sub_oi.course_id,
                        {$oiBatchGroupCol},
                        {$orderCountExpr} AS pending_order_cnt,
                        SUM({$headcountExpr}) AS pending_count
                    FROM {$oi} sub_oi
                    {$joinOrderSql}
                    {$attJoinSql}
                    {$pendingWhereSql}
                    GROUP BY sub_oi.course_id{$oiBatchGroupBy}
                ) po ON po.course_id = c.id{$oiBatchJoinCond}
            ";
            $pendingSelect = "
                COALESCE(po.pending_count, 0) AS pending_approval_count,
                COALESCE(po.pending_order_cnt, 0) AS order_pending_orders_count,
                COALESCE(po.pending_count, 0) AS order_pending_attendees_count
            ";
        }

        return $this->db->fetchAll(
            "SELECT b.*, c.title AS course_title, c.slug AS course_slug, c.type AS course_type,
                    {$statsSelect},
                    {$pendingSelect}
               FROM {$b} b
               INNER JOIN {$c} c ON c.id = b.course_id
               {$statsJoin}
               {$pendingJoin}
               {$sqlWhere}
           ORDER BY (CASE WHEN b.status IN ('closed', 'cancelled') THEN 1 ELSE 0 END) ASC,
                    (CASE WHEN b.status NOT IN ('closed', 'cancelled') THEN (b.date_start IS NULL) ELSE 0 END) ASC,
                    (CASE WHEN b.status NOT IN ('closed', 'cancelled') THEN b.date_start END) ASC,
                    (CASE WHEN b.status IN ('closed', 'cancelled') THEN (b.date_start IS NULL) ELSE 0 END) ASC,
                    (CASE WHEN b.status IN ('closed', 'cancelled') THEN b.date_start END) DESC,
                    b.id DESC",
            $params
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