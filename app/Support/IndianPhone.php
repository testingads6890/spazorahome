<?php

namespace App\Support;

/** Turns what people type into a mobile number in E.164 form (+91XXXXXXXXXX), the format Firebase uses. */
class IndianPhone
{
    public static function normalize(?string $input): ?string
    {
        $raw = trim((string) $input);
        if ($raw === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw);

        if (str_starts_with($raw, '+')) {
            return preg_match('/^\+[1-9]\d{7,14}$/', '+' . $digits) ? '+' . $digits : null;
        }
        if (strlen($digits) === 10 && preg_match('/^[6-9]\d{9}$/', $digits)) {
            return '+91' . $digits;
        }
        if (strlen($digits) === 12 && str_starts_with($digits, '91') && preg_match('/^91[6-9]\d{9}$/', $digits)) {
            return '+' . $digits;
        }
        if (strlen($digits) === 11 && str_starts_with($digits, '0') && preg_match('/^0[6-9]\d{9}$/', $digits)) {
            return '+91' . substr($digits, 1);
        }

        return null;
    }

    /** Digits of an Indian mobile for display in the OTP box (last 10 digits). */
    public static function localDigits(string $e164): string
    {
        return substr(preg_replace('/\D+/', '', $e164), -10);
    }
}
