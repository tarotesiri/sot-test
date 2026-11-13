<?php
/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : Validator.php
 *    Description : ตรวจสอบข้อมูลฟอร์ม พร้อมข้อความ Error ภาษาไทย รองรับ required/email/min/max/numeric/date/in/regex/เลขผู้เสียภาษี 13 หลัก/เบอร์โทร ฯลฯ
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

/**
 * กฎที่รองรับ:
 *  required, nullable, email, url, phone, tax_id (เลข 13 หลัก + checksum)
 *  min:n / max:n (จำนวนตัวอักษร), min_num:n / max_num:n (ค่าตัวเลข), between:a,b
 *  numeric, integer, digits:n, boolean, array, alpha_dash
 *  in:a,b,c, date (Y-m-d), datetime (Y-m-d H:i), same:field, confirmed (field_confirmation)
 *  regex:/pattern/  (ถ้า pattern มี | ให้ส่งกฎเป็น array แทน string)
 *
 * ตัวอย่าง:
 *  Validator::make($data, ['email' => 'required|email', 'qty' => 'required|integer|min_num:1'], ['email' => 'อีเมล']);
 */
final class Validator
{
    private array $errors = [];
    private bool $ran = false;

    public function __construct(
        private readonly array $data,
        private readonly array $rules,
        private readonly array $labels = []
    ) {}

    public static function make(array $data, array $rules, array $labels = []): self
    {
        return new self($data, $rules, $labels);
    }

    public function fails(): bool
    {
        $this->run();
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return !$this->fails();
    }

    public function errors(): array
    {
        $this->run();
        return $this->errors;
    }

    public function first(): ?string
    {
        $this->run();
        foreach ($this->errors as $list) {
            return $list[0] ?? null;
        }
        return null;
    }

    /** คืนเฉพาะฟิลด์ที่มีกฎ (ตัดช่องว่างหัวท้ายแล้ว) */
    public function validated(): array
    {
        $out = [];
        foreach (array_keys($this->rules) as $field) {
            $v = $this->data[$field] ?? null;
            $out[$field] = is_string($v) ? trim($v) : $v;
        }
        return $out;
    }

    private function run(): void
    {
        if ($this->ran) {
            return;
        }
        $this->ran = true;
        foreach ($this->rules as $field => $set) {
            $rules = is_array($set) ? $set : explode('|', (string) $set);
            $value = $this->data[$field] ?? null;
            if (is_string($value)) {
                $value = trim($value);
            }
            $empty = $value === null || $value === '' || $value === [];
            if ($empty && !in_array('required', $rules, true)) {
                continue;
            }
            $label = $this->labels[$field] ?? $field;
            foreach ($rules as $rule) {
                [$name, $param] = array_pad(explode(':', (string) $rule, 2), 2, null);
                $error = $this->check($field, $name, $param, $value, $empty, $label);
                if ($error !== null) {
                    $this->errors[$field][] = $error;
                    break;
                }
            }
        }
    }

    private function check(string $field, string $rule, ?string $param, mixed $value, bool $empty, string $label): ?string
    {
        $str = is_scalar($value) ? (string) $value : '';
        $num = is_numeric($str) ? (float) $str : null;

        return match ($rule) {
            'nullable'   => null,
            'required'   => $empty ? "กรุณากรอก{$label}" : null,
            'email'      => filter_var($str, FILTER_VALIDATE_EMAIL) ? null : "รูปแบบ{$label}ไม่ถูกต้อง",
            'url'        => filter_var($str, FILTER_VALIDATE_URL) ? null : "รูปแบบ{$label}ไม่ถูกต้อง",
            'phone'      => preg_match('/^(\+66|0)\d{8,9}$/', (string) preg_replace('/[\s\-]/', '', $str)) ? null : "{$label}ต้องเป็นเบอร์โทรที่ถูกต้อง",
            'tax_id'     => self::thaiTaxId($str) ? null : "{$label}ไม่ถูกต้อง (ต้องเป็นตัวเลข 13 หลัก)",
            'min'        => mb_strlen($str) < (int) $param ? "{$label}ต้องมีอย่างน้อย {$param} ตัวอักษร" : null,
            'max'        => mb_strlen($str) > (int) $param ? "{$label}ต้องไม่เกิน {$param} ตัวอักษร" : null,
            'min_num'    => $num === null || $num < (float) $param ? "{$label}ต้องไม่น้อยกว่า {$param}" : null,
            'max_num'    => $num === null || $num > (float) $param ? "{$label}ต้องไม่เกิน {$param}" : null,
            'between'    => self::between($num, (string) $param) ? null : "{$label}ต้องอยู่ระหว่าง " . str_replace(',', ' ถึง ', (string) $param),
            'numeric'    => $num !== null ? null : "{$label}ต้องเป็นตัวเลข",
            'integer'    => filter_var($str, FILTER_VALIDATE_INT) !== false ? null : "{$label}ต้องเป็นจำนวนเต็ม",
            'digits'     => preg_match('/^\d{' . (int) $param . '}$/', $str) ? null : "{$label}ต้องเป็นตัวเลข {$param} หลัก",
            'boolean'    => in_array(strtolower($str), ['0', '1', 'true', 'false', 'on', 'off', 'yes', 'no'], true) ? null : "{$label}ไม่ถูกต้อง",
            'array'      => is_array($value) ? null : "{$label}ไม่ถูกต้อง",
            'alpha_dash' => preg_match('/^[A-Za-z0-9_\-]+$/', $str) ? null : "{$label}ใช้ได้เฉพาะ a-z, 0-9, - และ _",
            'in'         => in_array($str, explode(',', (string) $param), true) ? null : "{$label}ไม่อยู่ในตัวเลือกที่กำหนด",
            'date'       => self::isDate($str, 'Y-m-d') ? null : "{$label}ต้องเป็นวันที่ที่ถูกต้อง",
            'datetime'   => self::isDate($str, 'Y-m-d H:i') || self::isDate($str, 'Y-m-d H:i:s') ? null : "{$label}ต้องเป็นวันและเวลาที่ถูกต้อง",
            'same'       => $str === (string) ($this->data[(string) $param] ?? '') ? null : "{$label}ไม่ตรงกัน",
            'confirmed'  => $str === (string) ($this->data[$field . '_confirmation'] ?? '') ? null : "ยืนยัน{$label}ไม่ตรงกัน",
            'regex'      => @preg_match((string) $param, $str) === 1 ? null : "รูปแบบ{$label}ไม่ถูกต้อง",
            default      => throw new \InvalidArgumentException("ไม่รู้จักกฎตรวจสอบ: {$rule}"),
        };
    }

    private static function between(?float $num, string $param): bool
    {
        [$min, $max] = array_map('floatval', array_pad(explode(',', $param), 2, '0'));
        return $num !== null && $num >= $min && $num <= $max;
    }

    private static function isDate(string $v, string $format): bool
    {
        $d = \DateTimeImmutable::createFromFormat('!' . $format, $v);
        return $d !== false && $d->format($format) === $v;
    }

    /** เลขประจำตัวผู้เสียภาษี / บัตรประชาชน 13 หลัก (ตรวจ checksum) */
    public static function thaiTaxId(string $v): bool
    {
        $v = (string) preg_replace('/[\s\-]/', '', $v);
        if (!preg_match('/^\d{13}$/', $v)) {
            return false;
        }
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $v[$i] * (13 - $i);
        }
        return (11 - $sum % 11) % 10 === (int) $v[12];
    }
}