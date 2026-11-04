<?php

namespace App\Services;

use App\Models\Product;
use App\Models\VendorCoupon;
use Illuminate\Support\Facades\Schema;

/**
 * The store's coupon service, plus seller coupons: a code from the vendor_coupons table only discounts that
 * seller's products in the cart. Store-wide codes work exactly as before (handled by the parent class).
 * The result has the same shape as before: error, discount, allocations (one per cart line) and coupon.
 */
class MarketplaceCouponService extends CouponService
{
    public function apply($code, $lines, $lock = false, ...$rest): array
    {
        $code = is_string($code) ? strtoupper(trim($code)) : $code;
        $vendorCoupon = $code && Schema::hasTable('vendor_coupons')
            ? VendorCoupon::with('vendor')->when($lock, fn ($q) => $q->lockForUpdate())->whereRaw('UPPER(code) = ?', [$code])->first()
            : null;

        if (! $vendorCoupon) {
            return parent::apply($code, $lines, $lock, ...$rest);
        }

        // Start from the "no coupon" result so every key the rest of the shop expects is present.
        $lines = (array) $lines;
        $result = parent::apply(null, $lines, $lock, ...$rest);
        $result['allocations'] = array_fill(0, count($lines), 0.0);
        $result['discount'] = 0.0;
        $result['coupon'] = null;

        if ($problem = $vendorCoupon->problem()) {
            $result['error'] = $problem;

            return $result;
        }

        $sellerProducts = Product::whereIn('id', array_column($lines, 'product_id'))->where('vendor_id', $vendorCoupon->vendor_id)->pluck('id')->all();
        $eligible = [];
        foreach (array_values($lines) as $n => $line) {
            if (in_array((int) $line['product_id'], array_map('intval', $sellerProducts), true)) {
                $eligible[$n] = (float) $line['total'];
            }
        }
        $base = array_sum($eligible);

        if ($base <= 0) {
            $result['error'] = 'This coupon is for products from ' . ($vendorCoupon->vendor->business_name ?? 'another seller') . ' only.';

            return $result;
        }
        if ($base < (float) $vendorCoupon->min_order) {
            $result['error'] = 'Add ₹' . number_format((float) $vendorCoupon->min_order - $base) . ' more from ' . $vendorCoupon->vendor->business_name . ' to use this coupon.';

            return $result;
        }

        $discount = $vendorCoupon->type === 'percent' ? $base * (float) $vendorCoupon->value / 100 : (float) $vendorCoupon->value;
        if ($vendorCoupon->max_discount !== null) {
            $discount = min($discount, (float) $vendorCoupon->max_discount);
        }
        $discount = round(min($discount, $base), 2);

        // Spread the discount over the seller's lines in proportion to their value; the last line takes the rounding.
        $left = $discount;
        $keys = array_keys($eligible);
        foreach ($keys as $i => $n) {
            $share = $i === count($keys) - 1 ? $left : round($discount * $eligible[$n] / $base, 2);
            $result['allocations'][$n] = $share;
            $left = round($left - $share, 2);
        }

        $result['discount'] = $discount;
        $result['coupon'] = $vendorCoupon;
        $result['error'] = null;

        return $result;
    }
}
