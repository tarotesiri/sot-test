<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : helpers.php
 *    Description : ฟังก์ชันช่วยทั่วระบบ: e() กัน XSS, url/asset/route/redirect, CSRF, flash/old, setting/config, icon, เงิน, วันที่ไทย พ.ศ., ช่วงวันอบรม, จำนวนเงินเป็นตัวอักษรไทย
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 1.0.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
declare(strict_types=1);

use App\Core\{App, Csrf, Database, Env, ErrorHandler, HttpException, Icon, Request, Session};

/* ============================================================================
 *  App / Env / Request
 * ========================================================================== */
function app(): App
{
    return App::instance() ?? throw new RuntimeException('App ยังไม่ถูก boot');
}

function db(): Database
{
    return app()->db();
}

function request(): Request
{
    return app()->request;
}

function env(string $key, mixed $default = null): mixed
{
    return Env::get($key, $default);
}

function config(string $name): array
{
    static $cache = [];
    $name = basename($name);
    if (!isset($cache[$name])) {
        $file = BASE_PATH . '/config/' . $name . '.php';
        $cache[$name] = is_file($file) ? (array) require $file : [];
    }
    return $cache[$name];
}

/** ค่าตั้งค่าจากฐานข้อมูล (ถ้า DB มีปัญหาจะคืนค่า default แทน ไม่ทำให้หน้าเว็บพัง) */
function setting(string $key, mixed $default = null): mixed
{
    static $broken = false;
    if ($broken) {
        return $default;
    }
    try {
        return app()->settings()->get($key, $default);
    } catch (Throwable $e) {
        $broken = true;
        ErrorHandler::log($e);
        return $default;
    }
}

function view(string $view, array $data = [], ?string $layout = 'layout'): string
{
    return app()->view->render($view, $data, $layout);
}

/* ============================================================================
 *  Security
 * ========================================================================== */
/** Escape HTML ป้องกัน XSS — ใช้กับทุกค่าที่แสดงผล */
function e(mixed $value): string
{
    if ($value instanceof Stringable) {
        $value = (string) $value;
    }
    return htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

/* ============================================================================
 *  URL / Redirect
 * ========================================================================== */
function url_base(): string
{
    static $base = null;
    if ($base === null) {
        $path = parse_url((string) Env::get('APP_URL', ''), PHP_URL_PATH);
        $base = rtrim(is_string($path) ? $path : '', '/');
    }
    return $base;
}

function url(string $path = '/'): string
{
    if (preg_match('#^((https?:)?//|mailto:|tel:|\#|data:)#i', $path)) {
        return $path;
    }
    $base = url_base();
    if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
        return $path; // มี base อยู่แล้ว (เช่นได้มาจาก route())
    }
    return $base . '/' . ltrim($path, '/');
}

function full_url(string $path = '/'): string
{
    $root = rtrim((string) Env::get('APP_URL', ''), '/');
    $base = url_base();
    $origin = $base !== '' && str_ends_with($root, $base) ? substr($root, 0, -strlen($base)) : $root;
    return $origin . url($path);
}

function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = BASE_PATH . '/public/assets/' . $path;
    return url('assets/' . $path) . (is_file($file) ? '?v=' . filemtime($file) : '');
}

function upload_url(?string $path): string
{
    return $path ? url('uploads/' . ltrim($path, '/')) : '';
}

function upload_path(string $path = ''): string
{
    return BASE_PATH . '/public/uploads' . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function route(string $name, array $params = []): string
{
    return app()->router->url($name, $params);
}

function current_path(): string
{
    return app()->request->path();
}

function is_active(string $link): bool
{
    $p = parse_url($link, PHP_URL_PATH);
    $path = '/' . trim(is_string($p) ? $p : '', '/');
    $cur = current_path();
    return $path === '/' ? $cur === '/' : ($cur === $path || str_starts_with($cur, $path . '/'));
}

function redirect(string $to, int $status = 302): never
{
    header('Location: ' . url($to), true, $status);
    exit;
}

function back(string $fallback = '/'): never
{
    $ref = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    $ok = $ref !== '' && parse_url($ref, PHP_URL_HOST) === parse_url('//' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
    header('Location: ' . ($ok ? $ref : url($fallback)), true, 302);
    exit;
}

function abort(int $status, string $message = ''): never
{
    throw new HttpException($status, $message);
}

function abort_unless(bool $condition, int $status, string $message = ''): void
{
    if (!$condition) {
        abort($status, $message);
    }
}

/* ============================================================================
 *  Session / Form
 * ========================================================================== */
function flash(string $type, string $message): void
{
    Session::flash($type, $message);
}

function old(string $key, mixed $default = ''): mixed
{
    return Session::old($key, $default);
}

function error_for(string $field): ?string
{
    return Session::errors()[$field][0] ?? null;
}

function has_errors(): bool
{
    return Session::errors() !== [];
}

function selected(mixed $a, mixed $b): string
{
    return (string) $a === (string) $b ? ' selected' : '';
}

function checked(mixed $condition): string
{
    return $condition ? ' checked' : '';
}

/* ============================================================================
 *  UI / Text
 * ========================================================================== */
function icon(string $name, string $class = ''): string
{
    return Icon::svg($name, $class);
}

function str_limit(string $s, int $limit = 100): string
{
    return mb_strlen($s) > $limit ? rtrim(mb_substr($s, 0, $limit)) . '…' : $s;
}

/** slug รองรับภาษาไทย เช่น "หลักสูตร Excel 2026" -> "หลักสูตร-excel-2026" */
function str_slug(string $s): string
{
    $s = mb_strtolower(trim($s));
    $s = (string) preg_replace('/[^\p{L}\p{M}\p{N}]+/u', '-', $s);
    return trim($s, '-');
}

/** โค้ดสุ่ม (ตัดตัวที่สับสนง่าย 0/O, 1/I/L) */
function random_code(int $length = 8): string
{
    $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $out = '';
    for ($i = 0; $i < $length; $i++) {
        $out .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $out;
}

/* ============================================================================
 *  Money
 * ========================================================================== */
function money(float|int|string|null $n, int $decimals = 2): string
{
    return number_format((float) $n, $decimals);
}

/** 1500 -> "1,500 บาท" , 0 -> "ฟรี" (ถ้า $freeLabel = true) */
function baht(float|int|string|null $n, bool $freeLabel = false, int $decimals = 0): string
{
    if ($freeLabel && (float) $n <= 0) {
        return 'ฟรี';
    }
    return number_format((float) $n, $decimals) . ' บาท';
}

/** อ่านตัวเลขเป็นข้อความไทย (รับเป็นสตริงตัวเลขล้วน) */
function thai_number_text(string $digits): string
{
    $digits = ltrim($digits, '0');
    if ($digits === '') {
        return 'ศูนย์';
    }
    if (strlen($digits) > 6) {
        $high = substr($digits, 0, -6);
        $low = substr($digits, -6);
        return thai_number_text($high) . 'ล้าน' . (ltrim($low, '0') === '' ? '' : thai_number_text($low));
    }
    $nums = ['', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];
    $units = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน'];
    $len = strlen($digits);
    $out = '';
    for ($i = 0; $i < $len; $i++) {
        $d = (int) $digits[$i];
        $pos = $len - $i - 1;
        if ($d === 0) {
            continue;
        }
        if ($pos === 1 && $d === 1) {
            $out .= 'สิบ';
        } elseif ($pos === 1 && $d === 2) {
            $out .= 'ยี่สิบ';
        } elseif ($pos === 0 && $d === 1 && $len > 1) {
            $out .= 'เอ็ด';
        } else {
            $out .= $nums[$d] . $units[$pos];
        }
    }
    return $out;
}

/** จำนวนเงินเป็นตัวอักษร เช่น 1250.50 -> "หนึ่งพันสองร้อยห้าสิบบาทห้าสิบสตางค์" */
function thai_baht_text(float|int|string $amount): string
{
    $amount = round((float) $amount, 2);
    if ($amount < 0) {
        return 'ลบ' . thai_baht_text(-$amount);
    }
    [$baht, $satang] = explode('.', number_format($amount, 2, '.', ''));
    $text = ltrim($baht, '0') === '' ? '' : thai_number_text($baht) . 'บาท';
    if ((int) $satang === 0) {
        return ($text === '' ? 'ศูนย์บาท' : $text) . 'ถ้วน';
    }
    return $text . thai_number_text($satang) . 'สตางค์';
}

/* ============================================================================
 *  Date (ภาษาไทย พ.ศ.)
 * ========================================================================== */
function to_date(DateTimeInterface|string|null $date): ?DateTimeImmutable
{
    if ($date === null || $date === '' || str_starts_with((string) ($date instanceof DateTimeInterface ? 'x' : $date), '0000-00-00')) {
        return null;
    }
    if ($date instanceof DateTimeInterface) {
        return DateTimeImmutable::createFromInterface($date);
    }
    try {
        return new DateTimeImmutable($date);
    } catch (Exception) {
        return null;
    }
}

function thai_month(int $m, bool $short = false): string
{
    $full = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
    $abbr = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    return ($short ? $abbr : $full)[$m] ?? '';
}

/** 5 มีนาคม 2569 / 5 มี.ค. 2569 / 5 มี.ค. 2569 เวลา 09:00 น. */
function thai_date(DateTimeInterface|string|null $date, bool $short = false, bool $withTime = false): string
{
    $d = to_date($date);
    if ($d === null) {
        return '-';
    }
    $out = $d->format('j') . ' ' . thai_month((int) $d->format('n'), $short) . ' ' . ((int) $d->format('Y') + 543);
    return $withTime ? $out . ' เวลา ' . $d->format('H:i') . ' น.' : $out;
}

/** ช่วงวันอบรม: "5–7 มี.ค. 2569" / "28 ก.พ. – 2 มี.ค. 2569" / "30 ธ.ค. 2569 – 2 ม.ค. 2570" */
function thai_date_range(DateTimeInterface|string|null $start, DateTimeInterface|string|null $end, bool $short = true): string
{
    $s = to_date($start);
    $e = to_date($end);
    if ($s === null) {
        return '-';
    }
    if ($e === null || $s->format('Y-m-d') === $e->format('Y-m-d')) {
        return thai_date($s, $short);
    }
    if ($s->format('Y-m') === $e->format('Y-m')) {
        return $s->format('j') . '–' . thai_date($e, $short);
    }
    if ($s->format('Y') === $e->format('Y')) {
        return $s->format('j') . ' ' . thai_month((int) $s->format('n'), $short) . ' – ' . thai_date($e, $short);
    }
    return thai_date($s, $short) . ' – ' . thai_date($e, $short);
}

/** จำนวนวันจาก $from (ค่าเริ่มต้น = วันนี้) ถึง $date (ติดลบ = เลยมาแล้ว) */
function days_until(DateTimeInterface|string $date, DateTimeInterface|string|null $from = null): int
{
    $to = to_date($date) ?? new DateTimeImmutable('today');
    $f = ($from === null ? new DateTimeImmutable('today') : (to_date($from) ?? new DateTimeImmutable('today')));
    return (int) $f->setTime(0, 0)->diff($to->setTime(0, 0))->format('%r%a');
}