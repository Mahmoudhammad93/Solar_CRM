<?php

namespace App\Support;

use InvalidArgumentException;

final class PhoneNumber
{
    /**
     * Normalize to E.164-ish digits with leading +.
     *
     * @throws InvalidArgumentException
     */
    public static function normalize(string $raw, ?string $defaultCallingCode = null): string
    {
        $trimmed = trim($raw);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Phone number is required.');
        }

        $defaultCallingCode = $defaultCallingCode ?: '+91';
        if (! str_starts_with($defaultCallingCode, '+')) {
            $defaultCallingCode = '+'.$defaultCallingCode;
        }

        $digits = preg_replace('/[^\d+]/', '', $trimmed) ?? '';
        if (str_starts_with($digits, '00')) {
            $digits = '+'.substr($digits, 2);
        }

        if (! str_starts_with($digits, '+')) {
            $local = ltrim($digits, '0');
            $ccDigits = ltrim($defaultCallingCode, '+');
            $digits = '+'.$ccDigits.$local;
        }

        $only = preg_replace('/\D/', '', $digits) ?? '';
        if (strlen($only) < 10 || strlen($only) > 15) {
            throw new InvalidArgumentException('Phone number must include a country code and 10–15 digits total.');
        }

        return '+'.$only;
    }

    public static function isValid(string $raw, ?string $defaultCallingCode = null): bool
    {
        try {
            self::normalize($raw, $defaultCallingCode);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    public static function waMeUrl(string $e164, ?string $prefill = null): string
    {
        $digits = ltrim(self::normalize($e164), '+');
        $url = 'https://wa.me/'.$digits;
        if (filled($prefill)) {
            $url .= '?text='.rawurlencode($prefill);
        }

        return $url;
    }
}
