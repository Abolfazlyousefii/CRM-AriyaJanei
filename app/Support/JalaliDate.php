<?php

namespace App\Support;

use Hekmatinasser\Verta\Verta;

class JalaliDate
{
    private const DIGITS = [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    public static function normalize(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return str_replace('-', '/', strtr(trim($value), self::DIGITS));
    }

    public static function isValid(?string $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (! preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $value)) {
            return false;
        }

        try {
            $date = Verta::parse($value)->datetime();

            return Verta::instance($date)->format('Y/m/d') === $value;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function toGregorian(?string $value): ?string
    {
        return $value === null ? null : Verta::parse($value)->datetime()->format('Y-m-d');
    }
}
