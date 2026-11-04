<?php

namespace Tests\Unit\Support;

use App\Support\BulkPricing;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BulkPricingTest extends TestCase
{
    private function read(array $input): ?array
    {
        return BulkPricing::fromRequest(Request::create('/', 'POST', $input));
    }

    public function test_default_mode_returns_null(): void
    {
        $this->assertNull($this->read([]));
        $this->assertNull($this->read(['bulk_mode' => 'default']));
    }

    public function test_off_mode_returns_empty_array(): void
    {
        $this->assertSame([], $this->read(['bulk_mode' => 'off']));
    }

    public function test_custom_mode_sorts_tiers_and_skips_blank_rows(): void
    {
        $tiers = $this->read(['bulk_mode' => 'custom', 'bulk_min' => ['10', '', '2'], 'bulk_off' => ['12.345', '5', '5']]);

        $this->assertSame([['min' => 2, 'off' => 5.0], ['min' => 10, 'off' => 12.35]], $tiers);
    }

    public function test_custom_mode_needs_at_least_one_tier(): void
    {
        $this->expectException(ValidationException::class);
        $this->read(['bulk_mode' => 'custom', 'bulk_min' => [''], 'bulk_off' => ['']]);
    }

    public function test_bigger_quantity_must_give_bigger_discount(): void
    {
        try {
            $this->read(['bulk_mode' => 'custom', 'bulk_min' => ['2', '5'], 'bulk_off' => ['10', '10']]);
            $this->fail('Expected a validation error');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('bulk_off', $e->errors());
        }
    }

    public function test_rules_limit_rows(): void
    {
        $this->assertSame('nullable|array|max:' . BulkPricing::ROWS, BulkPricing::rules()['bulk_min']);
    }
}
