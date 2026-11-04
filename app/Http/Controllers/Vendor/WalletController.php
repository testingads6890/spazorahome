<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorWalletTransaction;
use App\Services\WalletService;
use Illuminate\Http\Request;

/** The seller's wallet: what is available, what is on hold, and every credit and debit behind it. */
class WalletController extends Controller
{
    public function index(Request $request, WalletService $wallet)
    {
        $vendor = $request->user()->vendorAccount();
        abort_unless($vendor, 403);

        $filter = $request->query('type');
        $filter = in_array($filter, ['credit', 'debit'], true) ? $filter : null;
        $transactions = VendorWalletTransaction::where('vendor_id', $vendor->id)
            ->when($filter, fn ($q) => $q->where('type', $filter))
            ->latest('id')->paginate(25)->withQueryString();

        return view('vendor.wallet.index', [
            'vendor' => $vendor,
            'money' => $wallet->summary($vendor),
            'transactions' => $transactions,
            'filter' => $filter,
            'holdDays' => $wallet->holdDays(),
            'minimum' => (float) config('shop.withdrawals.minimum', 500),
        ]);
    }

    /** The whole statement as a CSV file. */
    public function export(Request $request)
    {
        $vendor = $request->user()->vendorAccount();
        abort_unless($vendor, 403);

        return response()->streamDownload(function () use ($vendor) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Type', 'Description', 'Detail', 'Credit', 'Debit', 'Balance']);
            VendorWalletTransaction::where('vendor_id', $vendor->id)->orderBy('id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $t) {
                    fputcsv($out, [
                        $t->created_at->format('Y-m-d H:i'), ucfirst($t->type), $t->label(), $t->note,
                        $t->type === 'credit' ? $t->amount : '', $t->type === 'debit' ? $t->amount : '', $t->balance_after,
                    ]);
                }
            });
            fclose($out);
        }, 'wallet-statement-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }
}
