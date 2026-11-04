<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorWalletTransaction;
use App\Services\WalletService;
use Illuminate\Http\Request;

/** Seller wallets for the team: every seller's balance, their statement, and manual corrections (admin only). */
class VendorWalletController extends Controller
{
    public function index(Request $request, WalletService $wallet)
    {
        $vendors = Vendor::query()
            ->when($request->filled('q'), fn ($q) => $q->where('business_name', 'like', '%' . $request->query('q') . '%'))
            ->whereIn('status', ['approved', 'suspended'])
            ->orderBy('business_name')->paginate(25)->withQueryString();

        $money = [];
        foreach ($vendors as $vendor) {
            $money[$vendor->id] = $wallet->summary($vendor);
        }

        return view('admin.vendor-wallets.index', [
            'vendors' => $vendors,
            'money' => $money,
            'ready' => WalletService::ready(),
            'holdDays' => $wallet->holdDays(),
        ]);
    }

    public function show(Vendor $vendor, WalletService $wallet)
    {
        $transactions = VendorWalletTransaction::where('vendor_id', $vendor->id)->with('author:id,name')->latest('id')->paginate(30);

        return view('admin.vendor-wallets.show', [
            'vendor' => $vendor,
            'money' => $wallet->summary($vendor),
            'transactions' => $transactions,
        ]);
    }

    /** Add or take money with a written reason. Only the admin role may do this. */
    public function adjust(Request $request, Vendor $vendor, WalletService $wallet)
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        $data = $request->validate([
            'type' => 'required|in:credit,debit',
            'amount' => 'required|numeric|min:0.01|max:10000000',
            'reason' => 'required|string|min:5|max:250',
        ]);

        $wallet->adjust($vendor, $data['type'], (float) $data['amount'], $data['reason'], $request->user()->id);

        return back()->with('success', '₹' . number_format((float) $data['amount'], 2) . ' ' . ($data['type'] === 'credit' ? 'added to' : 'taken from') . ' the wallet of ' . $vendor->business_name . '.');
    }
}
