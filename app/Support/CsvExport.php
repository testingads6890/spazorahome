<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams rows as a CSV download that opens cleanly in Excel: UTF-8 with a byte-order mark (so the rupee sign
 * and Hindi text survive), written row by row so large exports do not use much memory, and with any cell
 * that starts like a formula (= + - @) neutralised so a customer-typed value cannot run as one.
 */
class CsvExport
{
    /** @param  iterable<array<int, mixed>>  $rows */
    public static function download(string $name, array $headings, iterable $rows): StreamedResponse
    {
        $filename = $name . '-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($headings, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headings);
            foreach ($rows as $row) {
                fputcsv($out, array_map([self::class, 'cell'], $row));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public static function cell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i');
        }

        $text = (string) $value;
        // Plain numbers (including negative amounts) are safe; any other text starting with = + - @ tab or CR is not.
        if ($text !== '' && ! is_numeric($text) && preg_match('/^[=+\-@\t\r]/', $text)) {
            return "'" . $text;
        }

        return $text;
    }

    /** Money as a plain number with two decimals, so spreadsheets can add it up. */
    public static function money(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
