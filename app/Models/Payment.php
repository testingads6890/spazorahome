<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id', 'method', 'amount', 'charge_amount', 'refunded_amount', 'refund_due', 'currency', 'status', 'transaction_id', 'payment_details', 'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'refund_due' => 'decimal:2',
        'payment_details' => 'array',
        'paid_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Money was actually received for this payment (it may since have been refunded). */
    public function wasCollected(): bool
    {
        return $this->paid_at !== null;
    }

    /** What can still be given back: received, minus what was already sent or is already queued. */
    public function refundable(): float
    {
        if (! $this->wasCollected()) {
            return 0.0;
        }

        return max(0.0, round((float) $this->amount - (float) $this->refunded_amount - (float) $this->refund_due, 2));
    }

    /** null: nothing to refund. pending: owed to the customer but not sent yet. processed: sent. */
    public function refundState(): ?string
    {
        if ((float) $this->refund_due > 0) {
            return 'pending';
        }

        return (float) $this->refunded_amount > 0 ? 'processed' : null;
    }
}
