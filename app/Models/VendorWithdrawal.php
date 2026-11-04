<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A seller's request to be paid what is due on delivered orders. */
class VendorWithdrawal extends Model
{
    public const STATUSES = ['pending' => 'Requested', 'approved' => 'Approved', 'paid' => 'Paid', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled'];

    /** Requests that still hold their order lines (those lines cannot be asked for again). */
    public const OPEN = ['pending', 'approved'];

    protected $fillable = [
        'vendor_id', 'amount', 'fee', 'status', 'method', 'payout_details', 'vendor_order_ids',
        'note', 'admin_note', 'transaction_ref', 'processed_at', 'processed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'payout_details' => 'array',
        'vendor_order_ids' => 'array',
        'processed_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function net(): float
    {
        return round((float) $this->amount - (float) $this->fee, 2);
    }

    /** Order lines that can go into a new request: delivered, not paid yet, and not held by an open request. */
    public static function availableLines(Vendor $vendor)
    {
        $held = static::where('vendor_id', $vendor->id)->whereIn('status', self::OPEN)->pluck('vendor_order_ids')->flatten()->filter()->map(fn ($id) => (int) $id)->all();

        return VendorOrder::where('vendor_id', $vendor->id)
            ->where('status', 'delivered')
            ->where('payout_status', 'pending')
            ->when($held, fn ($q) => $q->whereNotIn('id', $held))
            ->orderBy('id');
    }
}
