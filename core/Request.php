<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : Request.php
 *    Description : คลาสห่อข้อมูลคำขอ HTTP ($_GET/$_POST/JSON/$_FILES/$_SERVER) พร้อมฟังก์ชันอ่านค่าแบบปลอดภัยตามชนิดข้อมูล
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

final class Request
{
    private function __construct(
        private readonly array $get,
        private readonly array $post,
        private readonly array $server,
        private readonly array $files,
    ) {}

    public static function capture(): self
    {
        $post = $_POST;
        if (str_contains((string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) {
            $json = json_decode((string) file_get_contents('php://input'), true);
            if (is_array($json)) {
                $post = $json;
            }
        }
        return new self($_GET, $post, $_SERVER, $_FILES);
    }

    public function method(): string
    {
        return strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /** path ที่ตัดโฟลเดอร์ฐานออกแล้ว เช่น /courses/abc */
    public function path(): string
    {
        $uri = parse_url((string) ($this->server['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $uri = rawurldecode(is_string($uri) ? $uri : '/');
        $base = url_base();
        if ($base !== '' && ($uri === $base || str_starts_with($uri, $base . '/'))) {
            $uri = substr($uri, strlen($base));
        }
        if (str_starts_with($uri, '/public/')) {
            $uri = substr($uri, 7); // กรณีเรียกผ่าน root .htaccess
        }
        if ($uri === '/index.php' || str_starts_with($uri, '/index.php/')) {
            $uri = substr($uri, 10);
        }
        return '/' . trim($uri, '/');
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->get[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->get[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->post) || array_key_exists($key, $this->get);
    }

    public function str(string $key, string $default = ''): string
    {
        $v = $this->input($key);
        return is_string($v) ? trim($v) : (is_int($v) || is_float($v) ? (string) $v : $default);
    }

    public function int(string $key, int $default = 0): int
    {
        $v = $this->input($key);
        return is_numeric($v) ? (int) $v : $default;
    }

    public function float(string $key, float $default = 0.0): float
    {
        $v = $this->input($key);
        return is_numeric($v) ? (float) $v : $default;
    }

    public function bool(string $key): bool
    {
        return in_array(strtolower((string) (is_scalar($this->input($key)) ? $this->input($key) : '')), ['1', 'true', 'on', 'yes'], true);
    }

    public function array(string $key): array
    {
        $v = $this->input($key);
        return is_array($v) ? $v : [];
    }

    public function all(): array
    {
        return array_merge($this->get, $this->post);
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    /** ไฟล์อัปโหลด (คืน null ถ้าไม่ได้เลือกไฟล์) */
    public function file(string $key): ?array
    {
        $f = $this->files[$key] ?? null;
        return is_array($f) && ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE ? $f : null;
    }

    public function header(string $name): ?string
    {
        $key = strtoupper(str_replace('-', '_', $name));
        $v = $this->server['HTTP_' . $key] ?? ($key === 'CONTENT_TYPE' ? ($this->server['CONTENT_TYPE'] ?? null) : null);
        return is_string($v) ? $v : null;
    }

    public function ip(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public function userAgent(): string
    {
        return mb_substr((string) ($this->server['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    public function isAjax(): bool
    {
        return strtolower((string) $this->header('X-Requested-With')) === 'xmlhttprequest';
    }

    public function wantsJson(): bool
    {
        return $this->isAjax() || str_contains((string) $this->header('Accept'), 'application/json');
    }
}