<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seller wallet: one ledger row for every rupee that comes in or goes out. The balance is always the sum of the rows.
 *
 *   credit  order                 money earned on a delivered order line
 *   credit/debit order_adjustment a return, partial refund or cancellation changed what was earned
 *   debit   withdrawal            held when a withdrawal is requested
 *   credit  withdrawal_reversal   given back when the request is rejected or cancelled
 *   debit   payout                paid to the seller outside a withdrawal (manual payout / marked paid)
 *   credit/debit adjustment       manual correction by the team (always with a reason)
 *
 * Safe to run on a live database: it adds one table (and vendor_orders.delivered_at if that is missing) and fills the table from the orders that already exist (once).
 */
return new class extends Migration
{
    public function up(): void
    {
        // The moment a line was delivered. Some servers already have it, some do not: add it only where it is missing.
        if (Schema::hasTable('vendor_orders') && ! Schema::hasColumn('vendor_orders', 'delivered_at')) {
            Schema::table('vendor_orders', function (Blueprint $table) {
                $table->timestamp('delivered_at')->nullable();
            });
        }

        if (Schema::hasTable('vendor_wallet_transactions')) {
            return;
        }

        Schema::create('vendor_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id')->index();
            $table->string('type', 10);                       // credit or debit
            $table->string('kind', 30);                       // see list above
            $table->decimal('amount', 12, 2);                 // always positive; "type" gives the direction
            $table->decimal('balance_after', 12, 2)->default(0);
            $table->string('ref_type', 30)->nullable();       // vendor_order, withdrawal, vendor_payout
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->timestamp('available_at')->nullable();    // earnings are held until this moment (null = no hold)
            $table->string('note', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['ref_type', 'ref_id']);
            $table->index(['vendor_id', 'kind']);
        });

        $this->backfill();
    }

    /** Bring the orders and payouts that already exist into the ledger, so balances are right from day one. */
    private function backfill(): void
    {
        if (! Schema::hasTable('vendor_orders')) {
            return;
        }
        $now = now();
        $balances = [];
        $push = function (int $vendorId, string $type, string $kind, float $amount, string $refType, int $refId, string $note, $at) use (&$balances, $now) {
            $amount = round($amount, 2);
            if ($amount < 0.005) {
                return;
            }
            $balances[$vendorId] = round(($balances[$vendorId] ?? 0) + ($type === 'credit' ? $amount : -$amount), 2);
            DB::table('vendor_wallet_transactions')->insert([
                'vendor_id' => $vendorId, 'type' => $type, 'kind' => $kind, 'amount' => $amount,
                'balance_after' => $balances[$vendorId], 'ref_type' => $refType, 'ref_id' => $refId,
                'note' => $note, 'created_at' => $at ?: $now, 'updated_at' => $at ?: $now,
            ]);
        };

        // Delivered order lines are earned money; the ones already paid out are paid again in the ledger (a debit).
        DB::table('vendor_orders')->where('status', 'delivered')->where('payout_status', '!=', 'failed')->orderBy('id')
            ->chunkById(500, function ($lines) use ($push) {
                foreach ($lines as $line) {
                    $at = $line->delivered_at ?? $line->updated_at ?? $line->created_at;
                    $push((int) $line->vendor_id, 'credit', 'order', (float) $line->payout_amount, 'vendor_order', (int) $line->id, 'Order line #' . $line->order_id . ' (before the wallet)', $at);
                    if ($line->payout_status === 'paid') {
                        $push((int) $line->vendor_id, 'debit', 'payout', (float) $line->payout_amount, 'vendor_order', (int) $line->id, 'Paid out before the wallet', $line->paid_at ?? $at);
                    }
                }
            });

        // Withdrawal requests that are still waiting already hold their money.
        if (Schema::hasTable('vendor_withdrawals')) {
            foreach (DB::table('vendor_withdrawals')->whereIn('status', ['pending', 'approved'])->orderBy('id')->get() as $w) {
                $push((int) $w->vendor_id, 'debit', 'withdrawal', (float) $w->amount, 'withdrawal', (int) $w->id, 'Withdrawal W-' . $w->id, $w->created_at);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_wallet_transactions');
    }
};
