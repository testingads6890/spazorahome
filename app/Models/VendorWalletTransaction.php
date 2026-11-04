<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One line in a seller's wallet ledger. The balance is the sum of these lines; nothing else stores it. */
class VendorWalletTransaction extends Model
{
    /** kind => label shown to sellers and the team */
    public const KINDS = [
        'order' => 'Order earnings',
        'order_adjustment' => 'Return / cancellation',
        'withdrawal' => 'Withdrawal',
        'withdrawal_reversal' => 'Withdrawal returned',
        'payout' => 'Payout',
        'adjustment' => 'Adjustment by our team',
    ];

    /** Kinds that make up earnings from sales (the ones held back until the hold period ends). */
    public const EARNING_KINDS = ['order', 'order_adjustment'];

    protected $fillable = [
        'vendor_id', 'type', 'kind', 'amount', 'balance_after', 'ref_type', 'ref_id', 'available_at', 'note', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'available_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Plus for money in, minus for money out. */
    public function signed(): float
    {
        return $this->type === 'credit' ? (float) $this->amount : -(float) $this->amount;
    }

    public function label(): string
    {
        return self::KINDS[$this->kind] ?? ucfirst(str_replace('_', ' ', $this->kind));
    }

    /** Still inside the hold period? */
    public function isHeld(): bool
    {
        return $this->available_at !== null && $this->available_at->isFuture() && in_array($this->kind, self::EARNING_KINDS, true);
    }
}
