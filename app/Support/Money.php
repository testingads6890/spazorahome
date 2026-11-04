<?php

namespace App\Support;

use App\Models\Currency;

/**
 * Display-only currency conversion for browsing pages.
 *
 * Everything is stored, charged, taxed and invoiced in INR. A visitor may pick another active currency to get a
 * rough idea of prices; those amounts are always prefixed with "≈" and the cart, checkout, orders and invoices
 * stay in rupees. Exchange rates are per 1 INR and are maintained by the admin.
 */
class Money
{
    public const COOKIE = 'currency';

    private static ?Currency $current = null;
    private static $options = null;
    private static bool $resolved = false;

    /** Active currencies a visitor can choose from (INR is the base and is always available). */
    public static function options()
    {
        return self::$options ??= Currency::where('is_active', true)->orderBy('sort_order')->orderBy('code')->get();
    }

    public static function switchable(): bool
    {
        return self::options()->count() > 1;
    }

    public static function code(): string
    {
        return self::current()?->code ?? 'INR';
    }

    /** The selected non-INR currency, or null when browsing in rupees. */
    public static function current(): ?Currency
    {
        if (! self::$resolved) {
            self::$resolved = true;
            $code = strtoupper((string) request()->cookie(self::COOKIE));
            $currency = ($code !== '' && $code !== 'INR') ? self::options()->firstWhere('code', $code) : null;
            self::$current = ($currency && (float) $currency->exchange_rate > 0) ? $currency : null;
        }

        return self::$current;
    }

    public static function isForeign(): bool
    {
        return self::current() !== null;
    }

    /** For a price on a browsing page: rupees, or an approximate amount in the chosen currency. */
    public static function browse(float|int|string|null $inr, int $decimals = 0): string
    {
        $inr = (float) $inr;
        $c = self::current();
        if (! $c) {
            return '₹' . number_format($inr, $decimals);
        }

        $amount = number_format($inr * (float) $c->exchange_rate, $inr * (float) $c->exchange_rate >= 100 ? 0 : 2);

        return '≈ ' . self::withSymbol($c, $amount);
    }

    /** Settings the page scripts need to format converted prices the same way. */
    public static function jsConfig(): array
    {
        $c = self::current();

        return $c
            ? ['foreign' => true, 'symbol' => $c->symbol, 'after' => $c->position === 'after', 'rate' => (float) $c->exchange_rate]
            : ['foreign' => false, 'symbol' => '₹', 'after' => false, 'rate' => 1.0];
    }

    private static function withSymbol(Currency $c, string $amount): string
    {
        return $c->position === 'after' ? "{$amount} {$c->symbol}" : "{$c->symbol}{$amount}";
    }

    /** Test helper: forget what was resolved for the previous request. */
    public static function reset(): void
    {
        self::$current = null;
        self::$options = null;
        self::$resolved = false;
    }
}
