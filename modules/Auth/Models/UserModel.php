<?php
declare(strict_types=1);

namespace Modules\Auth\Models;

use App\Core\Model;

final class UserModel extends Model
{
    // เปลี่ยนจาก 'sot_users' เป็น 'users' ให้ตรงกับระบบ Prefix ของ Framework
    protected static string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->t()} WHERE email = ? LIMIT 1",
            [$email]
        );
    }

    public function findByEmailExcludingId(string $email, int $excludeId): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->t()} WHERE email = ? AND id <> ? LIMIT 1",
            [$email, $excludeId]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->t()} WHERE id = ? LIMIT 1",
            [$id]
        );
    }

    public function updateProfile(int $id, array $data): bool
    {
        if ($id <= 0 || empty($data)) {
            return false;
        }

        $allowedCols = [
            'prefix',
            'firstname',
            'lastname',
            'email',
            'phone',
            'id_card',
            'position',
            'other_requirements',
            'password',
            'updated_at',
        ];

        $sets = [];
        $params = [];
        foreach ($allowedCols as $col) {
            if (array_key_exists($col, $data)) {
                $sets[] = "`{$col}` = ?";
                $params[] = $data[$col];
            }
        }

        if (empty($sets)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE {$this->t()} SET " . implode(', ', $sets) . " WHERE id = ? LIMIT 1";
        $this->db->query($sql, $params);
        return true;
    }
}