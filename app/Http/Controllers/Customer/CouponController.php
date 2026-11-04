<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Services\CouponService;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function apply(Request $request, CouponService $coupons)
    {
        $code = CouponService::normalize($request->validate(['code' => 'required|string|max:50'])['code']);

        $cart = Cart::current();
        $items = $cart ? $cart->items()->with('product')->get() : collect();
        if ($items->isEmpty()) {
            return back()->with('error', __('shop.messages.coupon_add_first'));
        }

        $result = $coupons->apply($code, $coupons->linesFor($items));
        if ($result['error']) {
            return back()->withInput()->with('coupon_error', $result['error']);
        }

        $request->session()->put(CouponService::SESSION_KEY, $code);

        return back()->with('success', __('shop.messages.coupon_applied', ['code' => $result['coupon']->code, 'amount' => '₹' . number_format($result['discount'], 2)]));
    }

    public function remove(Request $request)
    {
        $request->session()->forget(CouponService::SESSION_KEY);

        return back()->with('success', __('shop.messages.coupon_removed'));
    }
}
