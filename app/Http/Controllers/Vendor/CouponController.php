<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorCoupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

/** Discount codes a seller creates for their own products. */
class CouponController extends Controller
{
    public function index(Request $request)
    {
        $coupons = $request->user()->vendorAccount()->coupons()->latest('id')->paginate(20);

        return view('vendor.coupons.index', compact('coupons'));
    }

    public function create()
    {
        return view('vendor.coupons.form', ['coupon' => new VendorCoupon(['type' => 'percent', 'is_active' => true, 'min_order' => 0])]);
    }

    public function store(Request $request)
    {
        $request->user()->vendorAccount()->coupons()->create($this->validated($request));

        return redirect()->route('vendor.coupons.index')->with('success', 'Coupon created. Share the code with your customers.');
    }

    public function edit(Request $request, VendorCoupon $coupon)
    {
        $this->own($request, $coupon);

        return view('vendor.coupons.form', compact('coupon'));
    }

    public function update(Request $request, VendorCoupon $coupon)
    {
        $this->own($request, $coupon);
        $coupon->update($this->validated($request, $coupon));

        return redirect()->route('vendor.coupons.index')->with('success', 'Coupon updated.');
    }

    public function destroy(Request $request, VendorCoupon $coupon)
    {
        $this->own($request, $coupon);
        $coupon->delete();

        return redirect()->route('vendor.coupons.index')->with('success', 'Coupon deleted.');
    }

    private function validated(Request $request, ?VendorCoupon $coupon = null): array
    {
        $request->merge(['code' => strtoupper(preg_replace('/\s+/', '', (string) $request->input('code')))]);
        $data = $request->validate([
            'code' => ['required', 'string', 'min:4', 'max:40', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('vendor_coupons', 'code')->ignore($coupon?->id)],
            'title' => 'nullable|string|max:255',
            'type' => 'required|in:percent,fixed',
            'value' => ['required', 'numeric', 'min:1', $request->input('type') === 'percent' ? 'max:90' : 'max:1000000'],
            'min_order' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:1',
            'usage_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'nullable|boolean',
        ], ['code.regex' => 'Use letters, numbers, - or _ only (no spaces).', 'code.unique' => 'This code is already taken. Try another.']);

        // A seller code must not clash with a store-wide code either.
        foreach (['coupons', 'discounts'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'code') && \DB::table($table)->whereRaw('UPPER(code) = ?', [$data['code']])->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['code' => 'This code is already used by a store-wide offer. Try another.']);
            }
        }

        $data['min_order'] = $data['min_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function own(Request $request, VendorCoupon $coupon): void
    {
        abort_unless((int) $coupon->vendor_id === (int) $request->user()->vendorAccount()->id, 404);
    }
}
