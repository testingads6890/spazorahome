<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorOrder extends Model
{
    protected $fillable = [
        'vendor_id', 'order_id', 'order_item_id', 'status', 'subtotal', 'commission', 'tcs_amount', 'payout_amount', 'payout_status', 'paid_at', 'delivered_at',
    ];

    /** Always written explicitly, so a server whose column defaults are wrong (quoted) still stores clean values. */
    protected $attributes = [
        'status' => 'pending',
        'payout_status' => 'pending',
    ];

    protected static function booted(): void
    {
        // The moment a line is delivered is when the money is earned (and when any hold period starts).
        static::saving(function (VendorOrder $line) {
            // delivered_at is added by the wallet migration, so wait until it has been run.
            if ($line->status === 'delivered' && ! $line->delivered_at && \App\Services\WalletService::ready()) {
                $line->delivered_at = now();
            }
        });

        // Keep the seller wallet in step with the line. A wallet problem must never stop an order from being saved.
        static::saved(function (VendorOrder $line) {
            if (! \App\Services\WalletService::ready()) {
                return;
            }
            if (! $line->wasRecentlyCreated && ! $line->wasChanged(['status', 'payout_amount', 'payout_status'])) {
                return;
            }
            try {
                app(\App\Services\WalletService::class)->syncVendorOrder($line);
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }

    protected $casts = [
        'subtotal' => 'decimal:2',
        'commission' => 'decimal:2',
        'tcs_amount' => 'decimal:2',
        'payout_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
