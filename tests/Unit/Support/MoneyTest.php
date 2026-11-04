<?php

namespace Tests\Unit\Support;

use App\Models\Currency;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Money::reset();
        Currency::create(['name' => 'Rupee', 'code' => 'INR', 'symbol' => '₹', 'exchange_rate' => 1, 'is_active' => true]);
        Currency::create(['name' => 'Dollar', 'code' => 'USD', 'symbol' => '$', 'exchange_rate' => 0.012, 'is_active' => true]);
        Currency::create(['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€', 'exchange_rate' => 0.011, 'position' => 'after', 'is_active' => true]);
        Currency::create(['name' => 'Yen', 'code' => 'JPY', 'symbol' => '¥', 'exchange_rate' => 1.8, 'is_active' => false]);
        Currency::create(['name' => 'Broken', 'code' => 'XXX', 'symbol' => 'X', 'exchange_rate' => 0, 'is_active' => true]);
    }

    protected function tearDown(): void
    {
        Money::reset();
        parent::tearDown();
    }

    private function browseIn(?string $code): void
    {
        $request = Request::create('/', 'GET', [], $code ? [Money::COOKIE => $code] : []);
        $this->app->instance('request', $request);
        Money::reset();
    }

    public function test_defaults_to_rupees(): void
    {
        $this->browseIn(null);
        $this->assertSame('INR', Money::code());
        $this->assertFalse(Money::isForeign());
        $this->assertSame('₹1,234', Money::browse(1234.4));
        $this->assertSame('₹1,234.40', Money::browse('1234.4', 2));
        $this->assertSame(['foreign' => false, 'symbol' => '₹', 'after' => false, 'rate' => 1.0], Money::jsConfig());
    }

    public function test_converts_to_a_chosen_currency_approximately(): void
    {
        $this->browseIn('usd');
        $this->assertSame('USD', Money::code());
        $this->assertSame('≈ $12.00', Money::browse(1000));
        $this->assertSame('≈ $120', Money::browse(10000)); // no decimals from 100 up
        $this->assertTrue(Money::jsConfig()['foreign']);
    }

    public function test_symbol_after_amount(): void
    {
        $this->browseIn('EUR');
        $this->assertSame('≈ 11.00 €', Money::browse(1000));
        $this->assertTrue(Money::jsConfig()['after']);
    }

    public function test_inactive_unknown_or_zero_rate_currencies_fall_back_to_rupees(): void
    {
        foreach (['JPY', 'ABC', 'XXX', 'INR'] as $code) {
            $this->browseIn($code);
            $this->assertSame('INR', Money::code(), $code);
        }
    }

    public function test_options_only_list_active_currencies(): void
    {
        $this->assertEqualsCanonicalizing(['EUR', 'INR', 'USD', 'XXX'], Money::options()->pluck('code')->all());
        $this->assertTrue(Money::switchable());
    }
}
