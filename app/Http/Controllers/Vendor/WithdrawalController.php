<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorWithdrawal;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Sellers ask to be paid from their wallet; the team approves and transfers it. The money leaves the wallet when the request is made. */
class WithdrawalController extends Controller
{
    public function index(Request $request, WalletService $wallet)
    {
        $vendor = $request->user()->vendorAccount();
        $requests = $vendor->withdrawals()->latest('id')->paginate(15);
        $bank = (array) ($vendor->bank_details ?? []);
        $money = $wallet->summary($vendor);
        $summary = [
            'available' => max(0.0, $money['available']),
            'lines' => (int) $vendor->orders()->where('payout_status', 'pending')->where('status', 'delivered')->count(),
            'held' => $money['held'],
            'next_release' => $money['next_release'],
            'requested' => (float) $vendor->withdrawals()->whereIn('status', VendorWithdrawal::OPEN)->sum('amount'),
            'withdrawn' => (float) $vendor->withdrawals()->where('status', 'paid')->sum('amount'),
            'on_the_way' => (float) $vendor->orders()->where('payout_status', 'pending')->whereNotIn('status', ['delivered'])->sum('payout_amount'),
        ];

        return view('vendor.withdrawals.index', compact('vendor', 'summary', 'requests', 'bank') + [
            'fee' => (float) config('shop.withdrawals.fee', 0),
            'minimum' => (float) config('shop.withdrawals.minimum', 500),
        ]);
    }

    public function store(Request $request, WalletService $wallet)
    {
        $vendor = $request->user()->vendorAccount();
        $minimum = (float) config('shop.withdrawals.minimum', 500);
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'method' => 'required|in:bank_transfer,upi',
            'note' => 'nullable|string|max:500',
        ]);
        $bank = (array) ($vendor->bank_details ?? []);
        if ($data['method'] === 'bank_transfer' && empty($bank['account_number'])) {
            return back()->withErrors(['method' => 'Add your bank account in Business profile first.'])->withInput();
        }
        if ($data['method'] === 'upi' && empty($bank['upi'])) {
            return back()->withErrors(['method' => 'Add your UPI ID in Business profile first.'])->withInput();
        }

        $amount = round((float) $data['amount'], 2);
        $result = DB::transaction(function () use ($vendor, $data, $bank, $minimum, $amount, $wallet) {
            // One request at a time per seller, so two quick submits can never spend the same money twice.
            Vendor::whereKey($vendor->id)->lockForUpdate()->first();
            $available = max(0.0, $wallet->summary($vendor)['available']);
            if ($amount < $minimum) {
                return 'The smallest withdrawal is ₹' . number_format($minimum) . '.';
            }
            if ($amount > $available + 0.001) {
                return 'You can withdraw up to ₹' . number_format($available, 2) . ' right now.';
            }

            $withdrawal = $vendor->withdrawals()->create([
                'amount' => $amount,
                'fee' => min((float) config('shop.withdrawals.fee', 0), $amount),
                'status' => 'pending',
                'method' => $data['method'],
                'payout_details' => $data['method'] === 'upi' ? ['upi' => $bank['upi']] : array_intersect_key($bank, array_flip(['account_holder', 'bank_name', 'account_number', 'ifsc'])),
                'vendor_order_ids' => [],
                'note' => $data['note'] ?? null,
            ]);
            $wallet->holdForWithdrawal($withdrawal);

            return $amount;
        });

        if (is_string($result)) {
            return back()->withErrors(['amount' => $result])->withInput();
        }

        return back()->with('success', 'Withdrawal of ₹' . number_format($result, 2) . ' requested. It has left your wallet and will be transferred after checking.');
    }

    public function cancel(Request $request, VendorWithdrawal $withdrawal, WalletService $wallet)
    {
        abort_unless((int) $withdrawal->vendor_id === (int) $request->user()->vendorAccount()->id, 404);
        abort_unless($withdrawal->status === 'pending', 422, 'Only a request that has not been approved yet can be cancelled.');
        DB::transaction(function () use ($withdrawal, $wallet) {
            $withdrawal->update(['status' => 'cancelled']);
            $wallet->releaseWithdrawal($withdrawal, 'cancelled');
        });

        return back()->with('success', 'Withdrawal request cancelled. The money is back in your wallet.');
    }
}
