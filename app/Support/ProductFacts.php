<?php

namespace App\Support;

use App\Models\Deal;
use App\Models\Product;
use App\Models\Vendor;

/**
 * The short lines under the price on a product card: saving, offer end date, stock, delivery, seller and so on.
 *
 * Each line is worked out from the product's own data and is left out when there is nothing to say, or when the
 * card already says it elsewhere (the discount percent, the old price, the stars, "Sold out" and "New" have their
 * own places on the card, so they are never repeated here).
 */
class ProductFacts
{
    /**
     * @return array<int, array{key: string, icon: string, tone: string, text: string, url: ?string}>
     */
    public static function for(Product $product): array
    {
        $regular = (float) $product->price;
        $sale = $product->sale_price !== null && (float) $product->sale_price < $regular ? (float) $product->sale_price : null;
        $now = $sale ?? $regular;
        $stock = (int) $product->stock_quantity;
        $out = $stock <= 0;
        $hasVariants = (int) ($product->variants_count ?? 0) > 0;
        $vendor = $product->vendor_id ? self::vendors()->get($product->vendor_id) : null;
        $deal = self::deals()->get($product->id);
        $lines = [];

        $add = function (string $key, string $icon, string $text, string $tone = '', ?string $url = null) use (&$lines) {
            $lines[] = compact('key', 'icon', 'tone', 'text', 'url');
        };

        // 1. Money saved (the percent is already on the picture, the old price already beside the price).
        if ($sale !== null && $regular - $sale >= 1) {
            $add('save', 'bi-tag-fill', __('shop.card.save', ['amount' => Money::browse($regular - $sale)]), 'deal');
        }

        // 2. How long the offer runs, when the product is in a timed deal (Admin > Deals).
        if ($deal && ! $out) {
            $days = (int) ceil(now()->diffInHours($deal->ends_at, false) / 24);
            $add('ends', 'bi-clock', $days <= 3
                ? trans_choice('shop.card.ends_in_days', max($days, 1), ['count' => max($days, 1)])
                : __('shop.card.price_valid_until', ['date' => $deal->ends_at->translatedFormat('j M Y')]), 'deal');
        }

        // 3. Stock, with where it ships from when the seller's city is known.
        if (! $out) {
            $low = max(1, (int) ($product->low_stock_threshold ?: 5));
            if (! $hasVariants && $stock <= $low) {
                $add('stock', 'bi-hourglass-split', trans_choice('shop.card.only_left', $stock, ['count' => $stock]), 'warn');
            // } elseif ($vendor && filled($vendor->city)) {
            //     $add('stock', 'bi-check-circle-fill', __('shop.card.in_stock_from', ['city' => $vendor->city]), 'ok');
            } else {
                $add('stock', 'bi-check-circle-fill', __('shop.card.in_stock'), 'ok');
            }
        }

        // 4. Delivery: free above the store threshold, otherwise the standard charge.
        $freeOver = (float) config('shop.free_shipping_over');
        $fee = (float) config('shop.shipping_fee');
        if ($fee <= 0 || ($freeOver > 0 && $now >= $freeOver)) {
            $add('delivery', 'bi-truck', __('shop.card.free_delivery'), 'ok');
        } else {
            $add('delivery', 'bi-truck', __('shop.card.delivery_from', ['amount' => Money::browse($fee)]));
        }

        // 5. How many have been bought.
        if ((int) $product->purchase_count >= 5) {
            $add('sold', 'bi-fire', trans_choice('shop.card.bought', (int) $product->purchase_count, ['count' => number_format((int) $product->purchase_count)]), 'warn');
        }

        // 6. Who sells it.
        if ($vendor) {
            $add('seller', 'bi-shop', __('shop.card.sold_by', ['seller' => $vendor->business_name]), '', filled($vendor->slug) && Stores::enabled() ? route('stores.show', $vendor->slug) : null);
        }

        // 7. Warranty, as written on the product.
        if (filled($product->warranty)) {
            $add('warranty', 'bi-shield-check', __('shop.card.warranty', ['text' => \Illuminate\Support\Str::limit(trim(strip_tags((string) $product->warranty)), 34)]));
        }

        // 8. Delivered outside India.
        if (config('shop.export.enabled') && $product->export_available) {
            $add('export', 'bi-globe2', __('shop.card.ships_worldwide'));
        }

        // 9. Tax: listed prices are before GST.
        $gst = rtrim(rtrim(number_format($product->taxRate(), 2), '0'), '.');
        $add('gst', 'bi-receipt', __('shop.card.plus_gst', ['rate' => $gst]));

        return $lines;
    }

    /** Approved sellers, read once per request. */
    private static function vendors()
    {
        return self::once('card_vendors', fn () => Vendor::where('status', 'approved')->get(['id', 'business_name', 'slug', 'city'])->keyBy('id'));
    }

    /** Deals running right now, by product, read once per request. */
    private static function deals()
    {
        return self::once('card_deals', fn () => Deal::where('is_active', true)->where('starts_at', '<=', now())->where('ends_at', '>', now())
            ->orderBy('ends_at')->get(['product_id', 'ends_at'])->unique('product_id')->keyBy('product_id'));
    }

    /** Kept on the request itself, like the header data, so nothing carries over between requests. */
    private static function once(string $key, \Closure $load)
    {
        $bag = request()->attributes;
        if (! $bag->has($key)) {
            $bag->set($key, $load());
        }

        return $bag->get($key);
    }
}
