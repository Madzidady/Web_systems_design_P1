<?php

namespace App\Support;

class Base62
{
    private const ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public static function encode(int $number): string
    {
        if ($number === 0) {
            return self::ALPHABET[0];
        }

        $base = strlen(self::ALPHABET);
        $result = '';

        while ($number > 0) {
            $remainder = $number % $base;
            $result = self::ALPHABET[$remainder] . $result;
            $number = intdiv($number, $base);
        }

        return $result;
    }
}