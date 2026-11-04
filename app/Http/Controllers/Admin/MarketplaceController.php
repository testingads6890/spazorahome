<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    public function index()
    {
        $vendors = Vendor::withCount('products')->paginate(20);
        $multivendorEnabled = \App\Models\Setting::get('multivendor_enabled', false);
        return view('admin.marketplace.index', compact('vendors', 'multivendorEnabled'));
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'multivendor_enabled' => 'required|boolean',
        ]);

        \App\Models\Setting::set('multivendor_enabled', $request->multivendor_enabled, 'boolean');

        return back()->with('success', 'Multivendor setting updated successfully.');
    }

    public function vendors()
    {
        $vendors = Vendor::withCount('products')->paginate(20);
        return view('admin.marketplace.vendors', compact('vendors'));
    }

    public function products()
    {
        $products = \App\Models\VendorProduct::with(['product', 'vendor'])->paginate(20);
        return view('admin.marketplace.products', compact('products'));
    }

    public function commissions()
    {
        $commissions = \App\Models\VendorProduct::with(['vendor', 'product'])->get();
        return view('admin.marketplace.commissions', compact('commissions'));
    }

    public function payouts()
    {
        $payouts = \App\Models\VendorPayout::with('vendor')->paginate(20);
        return view('admin.marketplace.payouts', compact('payouts'));
    }

    /** Site-wide default commission for sellers who have no rate of their own. */
    public function updateDefaultCommission(Request $request)
    {
        $data = $request->validate([
            'default_commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [
            'default_commission_rate.max' => __('Commission cannot be more than 100%.'),
            'default_commission_rate.min' => __('Commission cannot be negative.'),
        ]);

        \App\Models\Setting::set('default_commission_rate', (string) round((float) $data['default_commission_rate'], 2), 'string');

        return back()->with('success', __('Default commission updated. Sellers without their own rate now pay this.'));
    }
}
