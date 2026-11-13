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

    // --- [เพิ่มใหม่] ฟังก์ชันค้นหาผู้ใช้ด้วย ID สำหรับดึงข้อมูลมาเติมในตะกร้า ---
    public function findById(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->t()} WHERE id = ? LIMIT 1",
            [$id]
        );
    }
}