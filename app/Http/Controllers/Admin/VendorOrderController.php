<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VendorOrder;
use Illuminate\Http\Request;

class VendorOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = VendorOrder::with(['vendor', 'order', 'orderItem']);
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        if ($request->filled('payout_status')) {
            $query->where('payout_status', $request->payout_status);
        }
        $items = $query->paginate(20);
        return view('admin.vendor-orders.index', ['vendorOrders' => $items]);
    }

    public function markPaid(VendorOrder $vendorOrder, \App\Services\WalletService $wallet)
    {
        // Paid outside a withdrawal: the same money leaves the seller's wallet (once).
        if ($vendorOrder->payout_status !== 'paid' && $vendorOrder->payout_status !== 'failed') {
            $wallet->recordPayout((int) $vendorOrder->vendor_id, (float) $vendorOrder->payout_amount, 'vendor_order', (int) $vendorOrder->id, 'Order line #' . $vendorOrder->order_id . ' marked as paid', auth()->id());
        }
        $vendorOrder->update([
            'payout_status' => 'paid',
            'paid_at' => now(),
        ]);
        return back()->with('success', 'Marked as paid.');
    }
}
