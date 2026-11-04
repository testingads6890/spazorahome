<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VendorOrder;
use App\Models\VendorWithdrawal;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Seller withdrawal requests: approve, record the transfer, or reject. */
class VendorWithdrawalController extends Controller
{
    public function index(Request $request)
    {
        $tab = array_key_exists($request->query('status'), VendorWithdrawal::STATUSES) ? $request->query('status') : 'pending';
        $requests = VendorWithdrawal::with('vendor:id,business_name,slug', 'processor:id,name')
            ->where('status', $tab)->latest('id')->paginate(20)->withQueryString();
        $counts = VendorWithdrawal::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        return view('admin.vendor-withdrawals.index', compact('requests', 'tab', 'counts') + ['statuses' => VendorWithdrawal::STATUSES]);
    }

    public function approve(Request $request, VendorWithdrawal $withdrawal)
    {
        abort_unless($withdrawal->status === 'pending', 422);
        $withdrawal->update(['status' => 'approved', 'processed_by' => $request->user()->id, 'processed_at' => now()]);

        return back()->with('success', 'Approved. Transfer the money, then mark it paid with the reference.');
    }

    /**
     * The money was sent. It already left the wallet when the seller asked, so nothing is posted here;
     * the oldest delivered order lines that fit inside the amount are marked paid so the Payouts page stays right.
     */
    public function paid(Request $request, VendorWithdrawal $withdrawal)
    {
        abort_unless(in_array($withdrawal->status, VendorWithdrawal::OPEN, true), 422);
        $data = $request->validate(['transaction_ref' => 'required|string|max:100', 'admin_note' => 'nullable|string|max:1000']);

        DB::transaction(function () use ($withdrawal, $data, $request) {
            $left = round((float) $withdrawal->amount, 2);
            $paid = [];
            $lines = VendorOrder::where('vendor_id', $withdrawal->vendor_id)->where('status', 'delivered')->where('payout_status', 'pending')
                ->orderBy('id')->lockForUpdate()->get(['id', 'payout_amount']);
            foreach ($lines as $line) {
                if ((float) $line->payout_amount > $left + 0.001) {
                    continue;
                }
                $paid[] = $line->id;
                $left = round($left - (float) $line->payout_amount, 2);
            }
            if ($paid) {
                VendorOrder::whereIn('id', $paid)->update(['payout_status' => 'paid', 'paid_at' => now()]);
            }
            $withdrawal->update([
                'status' => 'paid',
                'vendor_order_ids' => $paid,
                'transaction_ref' => $data['transaction_ref'],
                'admin_note' => $data['admin_note'] ?? $withdrawal->admin_note,
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
            ]);
        });

        return back()->with('success', 'Marked as paid. The seller sees the transfer reference.');
    }

    public function reject(Request $request, VendorWithdrawal $withdrawal, WalletService $wallet)
    {
        abort_unless(in_array($withdrawal->status, VendorWithdrawal::OPEN, true), 422);
        $data = $request->validate(['admin_note' => 'required|string|max:1000']);
        DB::transaction(function () use ($withdrawal, $data, $request, $wallet) {
            $withdrawal->update(['status' => 'rejected', 'admin_note' => $data['admin_note'], 'processed_by' => $request->user()->id, 'processed_at' => now()]);
            $wallet->releaseWithdrawal($withdrawal, 'rejected');
        });

        return back()->with('success', 'Request rejected. The money is back in the seller\'s wallet.');
    }
}
