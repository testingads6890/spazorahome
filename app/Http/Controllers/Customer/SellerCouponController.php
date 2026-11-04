<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\VendorCoupon;
use App\Services\CouponService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * The cart's "apply coupon" box. Seller codes (vendor_coupons) are checked here; every other code is handed
 * to the store's own coupon controller exactly as before.
 */
class SellerCouponController extends Controller
{
    public function apply(Request $request)
    {
        $code = strtoupper(trim((string) $request->input('code')));
        $isSellerCode = $code !== '' && Schema::hasTable('vendor_coupons') && VendorCoupon::whereRaw('UPPER(code) = ?', [$code])->exists();

        if (! $isSellerCode) {
            return app()->call([app(CouponController::class), 'apply']);
        }

        $cart = Cart::current();
        $items = $cart ? $cart->items()->with(['product', 'variant'])->get() : collect();
        if ($items->isEmpty()) {
            return back()->with('coupon_error', __('Add something to your cart first.'))->withInput();
        }

        $service = app(CouponService::class);
        $result = $service->apply($code, $service->linesFor($items));
        if ($result['error']) {
            return back()->with('coupon_error', $result['error'])->withInput();
        }

        session([CouponService::SESSION_KEY => $code]);

        return back()->with('success', __('Coupon :code applied: you save ₹:amount.', ['code' => $code, 'amount' => number_format((float) $result['discount'], 2)]));
    }
}
