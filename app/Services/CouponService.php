<?php

namespace App\Services;

use App\Models\Discount;

/**
 * Works out a coupon's effect on a cart. Pure calculation plus validation: it never writes.
 *
 * A "line" is ['product_id' => int, 'category_id' => ?int, 'total' => float] (price x quantity, before discount).
 * The discount reduces the taxable value, so GST is charged on the amount after the discount.
 */
class CouponService
{
    public const SESSION_KEY = 'coupon_code';

    /** Cart items -> the line shape the calculation expects. */
    public function linesFor($items): array
    {
        return $items->values()->map(fn ($i) => [
            'product_id' => $i->product_id,
            'category_id' => $i->product->category_id,
            'total' => round($i->currentUnitPrice() * $i->quantity, 2),
        ])->all();
    }

    public static function normalize(?string $code): string
    {
        return strtoupper(trim((string) $code));
    }

    public function find(?string $code, bool $lock = false): ?Discount
    {
        $code = self::normalize($code);
        if ($code === '') {
            return null;
        }
        $query = Discount::whereRaw('upper(code) = ?', [$code]);

        return ($lock ? $query->lockForUpdate() : $query)->first();
    }

    /** @return string|null why the coupon cannot be used, or null when it is fine */
    public function problem(?Discount $coupon, float $eligibleSubtotal, float $cartSubtotal): ?string
    {
        if (! $coupon || ! $coupon->is_active) {
            return 'That coupon code is not valid.';
        }
        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return 'This coupon is not active yet.';
        }
        if ($coupon->ends_at && $coupon->ends_at->isPast()) {
            return 'This coupon has expired.';
        }
        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            return 'This coupon has reached its usage limit.';
        }
        if ($coupon->min_order_amount !== null && $cartSubtotal < (float) $coupon->min_order_amount) {
            return 'Add items worth ₹' . number_format((float) $coupon->min_order_amount - $cartSubtotal) . ' more to use this coupon.';
        }
        if ($eligibleSubtotal <= 0) {
            return 'This coupon does not apply to the items in your cart.';
        }

        return null;
    }

    /**
     * @param  array<int, array{product_id: int, category_id: ?int, total: float}>  $lines
     * @return array{coupon: ?Discount, discount: float, allocations: array<int, float>, error: ?string}
     */
    public function apply(?string $code, array $lines, bool $lock = false): array
    {
        $none = ['coupon' => null, 'discount' => 0.0, 'allocations' => array_fill_keys(array_keys($lines), 0.0), 'error' => null];
        if (self::normalize($code) === '') {
            return $none;
        }

        $coupon = $this->find($code, $lock);
        $cartSubtotal = round(array_sum(array_column($lines, 'total')), 2);

        if (! $coupon) {
            return array_merge($none, ['error' => $this->problem(null, 0, $cartSubtotal)]);
        }

        $eligible = $this->eligibleIndexes($coupon, $lines);
        $eligibleSubtotal = round(array_sum(array_map(fn ($i) => $lines[$i]['total'], $eligible)), 2);

        if ($error = $this->problem($coupon, $eligibleSubtotal, $cartSubtotal)) {
            return array_merge($none, ['error' => $error]);
        }

        $raw = $coupon->type === 'percentage' ? $eligibleSubtotal * (float) $coupon->value / 100 : (float) $coupon->value;
        if ($coupon->max_discount_amount !== null) {
            $raw = min($raw, (float) $coupon->max_discount_amount);
        }
        $discount = round(min($raw, $eligibleSubtotal), 2);

        return ['coupon' => $coupon, 'discount' => $discount, 'allocations' => $this->allocate($discount, $lines, $eligible, $eligibleSubtotal), 'error' => null];
    }

    /** Lines the coupon covers: everything, unless it is limited to certain products or categories. */
    private function eligibleIndexes(Discount $coupon, array $lines): array
    {
        $products = $coupon->products()->pluck('products.id')->all();
        $categories = $coupon->categories()->pluck('categories.id')->all();

        if (! $products && ! $categories) {
            return array_keys($lines);
        }

        return array_keys(array_filter($lines, fn ($l) => in_array($l['product_id'], $products, true) || ($l['category_id'] !== null && in_array($l['category_id'], $categories, true))));
    }

    /** Spread the discount across the eligible lines in proportion to their value; the last line absorbs rounding. */
    private function allocate(float $discount, array $lines, array $eligible, float $eligibleSubtotal): array
    {
        $out = array_fill_keys(array_keys($lines), 0.0);
        $left = $discount;
        foreach ($eligible as $n => $i) {
            $share = $n === array_key_last($eligible) ? $left : round($discount * $lines[$i]['total'] / $eligibleSubtotal, 2);
            $share = min($share, $lines[$i]['total']);
            $out[$i] = round($share, 2);
            $left = round($left - $out[$i], 2);
        }

        return $out;
    }
}
