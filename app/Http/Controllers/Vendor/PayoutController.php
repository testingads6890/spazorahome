<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorOrder;
use Illuminate\Http\Request;

/** The seller's earnings: what is due, what has been paid, and each order line behind it. */
class PayoutController extends Controller
{
    public const TABS = ['all' => 'All', 'pending' => 'Due', 'paid' => 'Paid', 'failed' => 'Cancelled / failed'];

    public function index(Request $request)
    {
        $vendor = $request->user()->vendorAccount();
        abort_unless($vendor, 403);

        $base = fn () => VendorOrder::where('vendor_id', $vendor->id);
        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'all';

        $summary = [
            'due' => (float) $base()->where('payout_status', 'pending')->sum('payout_amount'),
            'paid' => (float) $base()->where('payout_status', 'paid')->sum('payout_amount'),
            'sales' => (float) $base()->where('payout_status', '!=', 'failed')->sum('subtotal'),
            'commission' => (float) $base()->where('payout_status', '!=', 'failed')->sum('commission'),
            'tcs' => (float) $base()->where('payout_status', '!=', 'failed')->sum('tcs_amount'),
            'delivered_due' => (float) $base()->where('payout_status', 'pending')->where('status', 'delivered')->sum('payout_amount'),
        ];
        $counts = collect(self::TABS)->map(fn ($label, $key) => $key === 'all' ? $base()->count() : $base()->where('payout_status', $key)->count());

        $lines = $base()->when($tab !== 'all', fn ($q) => $q->where('payout_status', $tab))
            ->with(['order:id,order_number,created_at', 'orderItem:id,product_name,quantity'])
            ->latest('id')->paginate(20)->withQueryString();

        return view('vendor.payouts.index', compact('vendor', 'summary', 'counts', 'lines', 'tab') + ['tabs' => self::TABS]);
    }
}
