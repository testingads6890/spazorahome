<?php

namespace App\Services;

use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\VendorWalletTransaction;
use App\Models\VendorWithdrawal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The seller wallet. Every rupee that comes in or goes out is a row in vendor_wallet_transactions,
 * and the balance is the sum of those rows, so the numbers can always be explained line by line.
 *
 *   balance    everything in minus everything out
 *   held       earnings from delivered orders still inside the hold period (config shop.wallet.hold_days)
 *   available  balance minus held: what the seller may withdraw now
 */
class WalletService
{
    private static bool $known = false;

    /** False until the wallet migration has been run, so a server that is a step behind keeps taking orders. */
    public static function ready(): bool
    {
        if (self::$known) {
            return true;
        }

        return self::$known = Schema::hasTable('vendor_wallet_transactions');
    }

    public function holdDays(): int
    {
        return max(0, (int) config('shop.wallet.hold_days', 0));
    }

    /** @return array{balance: float, held: float, available: float, earned: float, withdrawn: float, next_release: ?Carbon, next_release_amount: float} */
    public function summary(Vendor|int $vendor): array
    {
        $id = $vendor instanceof Vendor ? $vendor->id : $vendor;
        $empty = ['balance' => 0.0, 'held' => 0.0, 'available' => 0.0, 'earned' => 0.0, 'withdrawn' => 0.0, 'next_release' => null, 'next_release_amount' => 0.0];
        if (! self::ready()) {
            return $empty;
        }

        $signed = "CASE WHEN type = 'credit' THEN amount ELSE -amount END";
        $earning = "kind IN ('order','order_adjustment')";
        $row = VendorWalletTransaction::where('vendor_id', $id)->selectRaw(
            "COALESCE(SUM($signed), 0) AS balance,
             COALESCE(SUM(CASE WHEN $earning AND available_at > ? THEN $signed ELSE 0 END), 0) AS held,
             COALESCE(SUM(CASE WHEN $earning THEN $signed ELSE 0 END), 0) AS earned,
             COALESCE(SUM(CASE WHEN kind IN ('withdrawal','payout') THEN amount WHEN kind = 'withdrawal_reversal' THEN -amount ELSE 0 END), 0) AS withdrawn",
            [now()]
        )->first();

        $balance = round((float) $row->balance, 2);
        $held = max(0.0, round((float) $row->held, 2));

        $next = null;
        $nextAmount = 0.0;
        if ($held > 0) {
            $upcoming = VendorWalletTransaction::where('vendor_id', $id)->whereIn('kind', VendorWalletTransaction::EARNING_KINDS)
                ->where('available_at', '>', now())->orderBy('available_at')->first();
            $next = $upcoming?->available_at;
            if ($next) {
                $nextAmount = round((float) VendorWalletTransaction::where('vendor_id', $id)->whereIn('kind', VendorWalletTransaction::EARNING_KINDS)
                    ->where('available_at', $next)->selectRaw("COALESCE(SUM($signed), 0) AS s")->value('s'), 2);
            }
        }

        return [
            'balance' => $balance,
            'held' => $held,
            'available' => round($balance - $held, 2),
            'earned' => round((float) $row->earned, 2),
            'withdrawn' => round((float) $row->withdrawn, 2),
            'next_release' => $next,
            'next_release_amount' => $nextAmount,
        ];
    }

    /** Adds one ledger row. Call inside a transaction when the row belongs to a larger change. */
    public function post(int $vendorId, string $type, string $kind, float $amount, ?string $refType = null, ?int $refId = null, ?string $note = null, ?Carbon $availableAt = null, ?int $createdBy = null): ?VendorWalletTransaction
    {
        $amount = round(abs($amount), 2);
        if ($amount < 0.005 || ! self::ready()) {
            return null;
        }

        return DB::transaction(function () use ($vendorId, $type, $kind, $amount, $refType, $refId, $note, $availableAt, $createdBy) {
            // One writer per seller at a time keeps the running balance on each row exact.
            Vendor::withTrashed()->whereKey($vendorId)->lockForUpdate()->value('id');
            $signed = "CASE WHEN type = 'credit' THEN amount ELSE -amount END";
            $before = (float) VendorWalletTransaction::where('vendor_id', $vendorId)->selectRaw("COALESCE(SUM($signed), 0) AS b")->value('b');

            return VendorWalletTransaction::create([
                'vendor_id' => $vendorId,
                'type' => $type,
                'kind' => $kind,
                'amount' => $amount,
                'balance_after' => round($before + ($type === 'credit' ? $amount : -$amount), 2),
                'ref_type' => $refType,
                'ref_id' => $refId,
                'available_at' => $availableAt,
                'note' => $note ? mb_substr($note, 0, 255) : null,
                'created_by' => $createdBy,
            ]);
        });
    }

    /**
     * Makes the wallet agree with one order line: a delivered line is worth its payout amount,
     * anything else (not delivered yet, cancelled, fully refunded) is worth nothing. Whatever the difference is
     * gets posted, so calling this twice, or after a partial refund, always ends at the right figure.
     */
    public function syncVendorOrder(VendorOrder $line): void
    {
        if (! self::ready() || ! $line->exists) {
            return;
        }

        DB::transaction(function () use ($line) {
            $desired = ($line->status === 'delivered' && $line->payout_status !== 'failed') ? round((float) $line->payout_amount, 2) : 0.0;
            $rows = VendorWalletTransaction::where('ref_type', 'vendor_order')->where('ref_id', $line->id)
                ->whereIn('kind', VendorWalletTransaction::EARNING_KINDS)->orderBy('id')->get();
            $current = round($rows->sum(fn (VendorWalletTransaction $r) => $r->signed()), 2);
            $delta = round($desired - $current, 2);
            if (abs($delta) < 0.005) {
                return;
            }

            $first = $rows->first();
            $number = $line->order?->order_number ?? $line->order_id;
            $this->post(
                (int) $line->vendor_id,
                $delta > 0 ? 'credit' : 'debit',
                $first ? 'order_adjustment' : 'order',
                abs($delta),
                'vendor_order',
                (int) $line->id,
                $first ? "Order {$number} changed (return, refund or cancellation)" : "Order {$number} delivered",
                $first ? $first->available_at : $this->releaseDate($line),
            );
        });
    }

    /** Syncs every line of an order (after the order was cancelled in bulk). */
    public function syncOrder(int $orderId): void
    {
        if (! self::ready()) {
            return;
        }
        foreach (VendorOrder::where('order_id', $orderId)->get() as $line) {
            $this->syncVendorOrder($line);
        }
    }

    private function releaseDate(VendorOrder $line): ?Carbon
    {
        $days = $this->holdDays();

        return $days > 0 ? ($line->delivered_at ?? now())->copy()->addDays($days) : null;
    }

    /** A withdrawal request takes its money out of the wallet right away. */
    public function holdForWithdrawal(VendorWithdrawal $withdrawal): void
    {
        $this->post((int) $withdrawal->vendor_id, 'debit', 'withdrawal', (float) $withdrawal->amount, 'withdrawal', (int) $withdrawal->id, 'Withdrawal W-' . $withdrawal->id);
    }

    /** A rejected or cancelled request gives the money back (only once). */
    public function releaseWithdrawal(VendorWithdrawal $withdrawal, string $why): void
    {
        if (! self::ready()) {
            return;
        }
        DB::transaction(function () use ($withdrawal, $why) {
            $held = VendorWalletTransaction::where('ref_type', 'withdrawal')->where('ref_id', $withdrawal->id)->where('kind', 'withdrawal')->exists();
            $given = VendorWalletTransaction::where('ref_type', 'withdrawal')->where('ref_id', $withdrawal->id)->where('kind', 'withdrawal_reversal')->exists();
            if ($held && ! $given) {
                $this->post((int) $withdrawal->vendor_id, 'credit', 'withdrawal_reversal', (float) $withdrawal->amount, 'withdrawal', (int) $withdrawal->id, "Withdrawal W-{$withdrawal->id} {$why}: returned to your wallet");
            }
        });
    }

    /** Money paid to the seller outside a withdrawal (a marked-paid order line or a manual payout). Posted once per reference. */
    public function recordPayout(int $vendorId, float $amount, string $refType, int $refId, string $note, ?int $by = null): void
    {
        if (! self::ready()) {
            return;
        }
        DB::transaction(function () use ($vendorId, $amount, $refType, $refId, $note, $by) {
            if (! VendorWalletTransaction::where('ref_type', $refType)->where('ref_id', $refId)->where('kind', 'payout')->exists()) {
                $this->post($vendorId, 'debit', 'payout', $amount, $refType, $refId, $note, null, $by);
            }
        });
    }

    /** A correction by the team, always with a reason the seller can read. */
    public function adjust(Vendor $vendor, string $type, float $amount, string $reason, int $by): ?VendorWalletTransaction
    {
        return $this->post($vendor->id, $type === 'debit' ? 'debit' : 'credit', 'adjustment', $amount, null, null, $reason, null, $by);
    }
}
