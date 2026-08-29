<?php

namespace App\Support;

class PhoneNormalizer
{
    public static function toE164(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '90') && strlen($digits) === 12) {
            return '+'.$digits;
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '5')) {
            return '+90'.$digits;
        }

        if (str_starts_with($phone, '+')) {
            return '+'.$digits;
        }

        return $digits !== '' ? '+'.$digits : trim($phone);
    }

    public static function digitsOnly(string $phone): string
    {
        return preg_replace('/\D+/', '', self::toE164($phone)) ?? '';
    }
}
