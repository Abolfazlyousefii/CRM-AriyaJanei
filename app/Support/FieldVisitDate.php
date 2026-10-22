<?php

namespace App\Support;

use Closure;
use Hekmatinasser\Verta\Verta;

class FieldVisitDate
{
    public static function persian(string $value): string
    {
        return strtr($value, array_combine(str_split('0123456789'), ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹']));
    }

    public static function format($date, string $format = 'Y/m/d'): string
    {
        return $date ? self::persian(Verta::instance($date)->format($format)) : '';
    }

    public static function gregorian(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_string($value)) {
            throw new \InvalidArgumentException('Invalid date');
        }
        $value = strtr(trim($value), ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
        if (! preg_match('/^(1[2-5]\d{2})\/(\d{1,2})\/(\d{1,2})$/', $value, $parts) || ! Verta::isValidDate((int) $parts[1], (int) $parts[2], (int) $parts[3])) {
            throw new \InvalidArgumentException('Invalid Jalali date');
        }

        return Verta::createJalaliDate((int) $parts[1], (int) $parts[2], (int) $parts[3])->datetime()->format('Y-m-d');
    }

    public static function rule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            try {
                self::gregorian($value);
            } catch (\InvalidArgumentException $e) {
                $fail('تاریخ شمسی معتبر با قالب ۱۴۰۵/۰۷/۱۸ وارد کنید.');
            }
        };
    }
}
