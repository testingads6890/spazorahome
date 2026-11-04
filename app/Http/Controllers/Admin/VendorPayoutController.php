<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VendorPayout;
use Illuminate\Http\Request;

class VendorPayoutController extends Controller
{
    public function index(Request $request)
    {
        $query = VendorPayout::with('vendor');
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $items = $query->paginate(20);
        return view('admin.vendor-payouts.index', ['payouts' => $items, 'vendors' => \App\Models\Vendor::where('status', 'approved')->orderBy('business_name')->get()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'amount' => 'required|numeric|min:0',
            'method' => 'required|string',
            'transaction_id' => 'nullable|string',
        ]);

        $payout = VendorPayout::create([
            'vendor_id' => $request->vendor_id,
            'amount' => $request->amount,
            'method' => $request->method,
            'transaction_id' => $request->transaction_id,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Payout initiated successfully.');
    }

    public function complete(VendorPayout $vendorPayout, \App\Services\WalletService $wallet)
    {
        // A completed manual payout leaves the seller's wallet (once).
        if ($vendorPayout->status !== 'completed') {
            $wallet->recordPayout((int) $vendorPayout->vendor_id, (float) $vendorPayout->amount, 'vendor_payout', (int) $vendorPayout->id, 'Payout' . ($vendorPayout->transaction_id ? ' ' . $vendorPayout->transaction_id : ' #' . $vendorPayout->id), auth()->id());
        }
        $vendorPayout->update([
            'status' => 'completed',
            'paid_at' => now(),
        ]);
        return back()->with('success', 'Payout completed.');
    }
}
