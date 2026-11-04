<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A customer's request to send items back. Approving it issues the credit note and the refund. */
class ReturnRequest extends Model
{
    public const REASONS = [
        'damaged' => 'Arrived damaged',
        'defective' => 'Defective or not working',
        'wrong_item' => 'Wrong item delivered',
        'not_as_described' => 'Not as described',
        'size_fit' => 'Size or fit is not right',
        'changed_mind' => 'Changed my mind',
        'other' => 'Other reason',
    ];

    public const STATUSES = ['pending' => 'Requested', 'approved' => 'Approved', 'rejected' => 'Declined', 'cancelled' => 'Cancelled'];

    protected $fillable = ['order_id', 'user_id', 'status', 'items', 'reason', 'customer_note', 'admin_note', 'credit_notes', 'resolved_at', 'resolved_by'];

    protected $casts = [
        'items' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? ucfirst(str_replace('_', ' ', $this->reason));
    }

    /** Can this order still be returned by its customer? Delivered (or shipped) and inside the return window. */
    public static function windowOpen(Order $order): bool
    {
        if (! in_array($order->status, ['shipped', 'delivered'], true) || ! $order->hasInvoice()) {
            return false;
        }
        $from = $order->delivered_at ?? $order->updated_at;

        return $from === null || $from->copy()->addDays((int) config('shop.policies.return_days', config('shop.return_days', 7)))->endOfDay()->isFuture();
    }
}
