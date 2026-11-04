<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;

class InvoiceSequence extends Model
{
    protected $fillable = ['series', 'financial_year', 'last_number'];

    /** Indian financial year label (April to March), e.g. 26-27. */
    public static function financialYear(CarbonInterface $date): string
    {
        $start = $date->month >= 4 ? $date->year : $date->year - 1;

        return sprintf('%02d-%02d', $start % 100, ($start + 1) % 100);
    }

    /**
     * Next number in a series (INV or CN), e.g. INV/26-27/000001 (16 characters, the GST maximum).
     * Must run inside a transaction: the row lock serialises concurrent callers, and if the
     * caller's transaction rolls back the increment rolls back too, so no number is ever skipped.
     */
    public static function next(CarbonInterface $date, string $series = 'INV'): string
    {
        $fy = static::financialYear($date);

        $row = static::where('series', $series)->where('financial_year', $fy)->lockForUpdate()->first();
        if (! $row) {
            try {
                $row = static::create(['series' => $series, 'financial_year' => $fy, 'last_number' => 0]);
            } catch (UniqueConstraintViolationException) {
                $row = static::where('series', $series)->where('financial_year', $fy)->lockForUpdate()->first();
            }
        }

        $row->increment('last_number');

        // Vendor series (V12, C12) are longer than INV / CN, so the counter gets fewer digits to stay within 16 characters.
        $width = max(4, min(6, 16 - strlen($series) - strlen($fy) - 2));

        return sprintf('%s/%s/%0' . $width . 'd', $series, $fy, $row->last_number);
    }
}
