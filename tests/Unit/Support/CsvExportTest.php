<?php

namespace Tests\Unit\Support;

use App\Support\CsvExport;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class CsvExportTest extends TestCase
{
    public function test_cell_formats_scalars(): void
    {
        $this->assertSame('', CsvExport::cell(null));
        $this->assertSame('yes', CsvExport::cell(true));
        $this->assertSame('no', CsvExport::cell(false));
        $this->assertSame('42', CsvExport::cell(42));
        $this->assertSame('1.5', CsvExport::cell(1.5));
        $this->assertSame('2026-01-02 03:04', CsvExport::cell(new DateTimeImmutable('2026-01-02 03:04:05')));
        $this->assertSame('Sofa', CsvExport::cell('Sofa'));
    }

    public function test_cell_neutralises_formula_injection(): void
    {
        foreach (['=SUM(A1)', '+cmd', '-x', '@foo', "\tbad", "\rbad"] as $value) {
            $this->assertSame("'" . $value, CsvExport::cell($value));
        }
    }

    public function test_cell_keeps_plain_negative_numbers(): void
    {
        $this->assertSame('-12.50', CsvExport::cell('-12.50'));
        $this->assertSame('+5', CsvExport::cell('+5'));
    }

    public function test_money_has_two_decimals_without_grouping(): void
    {
        $this->assertSame('1234.50', CsvExport::money('1234.5'));
        $this->assertSame('0.00', CsvExport::money(null));
        $this->assertSame('1000000.00', CsvExport::money(1000000));
    }
}
