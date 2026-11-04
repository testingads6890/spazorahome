<?php

namespace App\Support;

/** Indian states / UTs with their GST state codes, used for the address dropdown and place-of-supply. */
class GstState
{
    /** code => [name, abbreviation] */
    private const STATES = [
        '01' => ['Jammu and Kashmir', 'JK'], '02' => ['Himachal Pradesh', 'HP'], '03' => ['Punjab', 'PB'],
        '04' => ['Chandigarh', 'CH'], '05' => ['Uttarakhand', 'UK'], '06' => ['Haryana', 'HR'],
        '07' => ['Delhi', 'DL'], '08' => ['Rajasthan', 'RJ'], '09' => ['Uttar Pradesh', 'UP'],
        '10' => ['Bihar', 'BR'], '11' => ['Sikkim', 'SK'], '12' => ['Arunachal Pradesh', 'AR'],
        '13' => ['Nagaland', 'NL'], '14' => ['Manipur', 'MN'], '15' => ['Mizoram', 'MZ'],
        '16' => ['Tripura', 'TR'], '17' => ['Meghalaya', 'ML'], '18' => ['Assam', 'AS'],
        '19' => ['West Bengal', 'WB'], '20' => ['Jharkhand', 'JH'], '21' => ['Odisha', 'OD'],
        '22' => ['Chhattisgarh', 'CG'], '23' => ['Madhya Pradesh', 'MP'], '24' => ['Gujarat', 'GJ'],
        '26' => ['Dadra and Nagar Haveli and Daman and Diu', 'DN'], '27' => ['Maharashtra', 'MH'],
        '29' => ['Karnataka', 'KA'], '30' => ['Goa', 'GA'], '31' => ['Lakshadweep', 'LD'],
        '32' => ['Kerala', 'KL'], '33' => ['Tamil Nadu', 'TN'], '34' => ['Puducherry', 'PY'],
        '35' => ['Andaman and Nicobar Islands', 'AN'], '36' => ['Telangana', 'TS'],
        '37' => ['Andhra Pradesh', 'AP'], '38' => ['Ladakh', 'LA'],
    ];

    /** @return array<string, string> code => name */
    public static function options(): array
    {
        return array_map(fn ($s) => $s[0], self::STATES);
    }

    public static function names(): array
    {
        return array_values(self::options());
    }

    /** GST code for a state given as name, abbreviation or code; null when unrecognised. */
    public static function code(?string $value): ?string
    {
        $v = mb_strtolower(trim((string) $value));
        if ($v === '') {
            return null;
        }

        foreach (self::STATES as $code => [$name, $abbr]) {
            if ($v === (string) $code || $v === mb_strtolower($name) || $v === mb_strtolower($abbr)) {
                return (string) $code;
            }
        }

        return null;
    }

    public static function name(?string $value): ?string
    {
        $code = self::code($value);

        return $code ? self::STATES[$code][0] : null;
    }

    /** Standard 15-character GSTIN layout. */
    public static function isValidGstin(?string $gstin): bool
    {
        return (bool) preg_match('/^\d{2}[A-Z]{5}\d{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', (string) $gstin);
    }

    /** State code taken from the first two digits of a GSTIN, if it is a known state code. */
    public static function codeFromGstin(?string $gstin): ?string
    {
        $prefix = substr((string) $gstin, 0, 2);

        return isset(self::STATES[$prefix]) ? $prefix : null;
    }
}
