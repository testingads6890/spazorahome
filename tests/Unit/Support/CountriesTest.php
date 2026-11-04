<?php

namespace Tests\Unit\Support;

use App\Support\Countries;
use PHPUnit\Framework\TestCase;

class CountriesTest extends TestCase
{
    public function test_india_spellings_count_as_india(): void
    {
        foreach (['India', 'india', ' IN ', 'IND', 'Bharat', '', null] as $value) {
            $this->assertTrue(Countries::isIndia($value), var_export($value, true));
        }
    }

    public function test_other_countries_are_not_india(): void
    {
        foreach (['United States', 'Indonesia', 'US', 'INDIA2'] as $value) {
            $this->assertFalse(Countries::isIndia($value), $value);
        }
    }

    public function test_list_contains_india_and_has_no_duplicates(): void
    {
        $all = Countries::all();
        $this->assertContains(Countries::INDIA, $all);
        $this->assertSame($all, array_values(array_unique($all)));
    }
}
