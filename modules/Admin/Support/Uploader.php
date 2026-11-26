<?php
/**
 * ====================================================================
 * Project Name : SIAM Online Training
 * File Name : Uploader.php
 * Description : Uploader
 * ====================================================================
 * Author : TaRote SiRi
 * Version : 5.22.0
 * ====================================================================
**/
declare(strict_types=1);

namespace Modules\Admin\Support;

use RuntimeException;

final class Uploader
{
    public const DOCUMENT_EXT = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', 'txt', 'csv', 'jpg', 'png', 'webp'];

    public static function upload(array $file, string $dir, int $maxBytes, array $allowedExt = self::DOCUMENT_EXT): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('อัปโหลดไฟล์ไม่สำเร็จ');
        }
        if (($file['size'] ?? 0) > $maxBytes) {
            throw new RuntimeException('ขนาดไฟล์ใหญ่เกินกำหนด');
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            throw new RuntimeException('ชนิดไฟล์ไม่รองรับ');
        }
        $name   = date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
        $absDir = AdminHelper::uploadPath($dir);
        if (!is_dir($absDir) && !mkdir($absDir, 0775, true) && !is_dir($absDir)) {
            throw new RuntimeException('สร้างโฟลเดอร์ไม่ได้');
        }
        if (!move_uploaded_file((string) $file['tmp_name'], $absDir . '/' . $name)) {
            throw new RuntimeException('บันทึกไฟล์ไม่สำเร็จ');
        }
        return trim($dir, '/') . '/' . $name;
    }

    public static function delete(?string $path): void
    {
        if (!$path) return;
        $abs = AdminHelper::uploadPath($path);
        if (is_file($abs)) {
            @unlink($abs);
        }
    }
}