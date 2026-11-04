<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditNote extends Model
{
    protected $fillable = ['order_id', 'supplier_invoice_id', 'is_full', 'number', 'issued_at', 'reason', 'shipping', 'shipping_tax', 'tax', 'total'];

    protected $casts = [
        'issued_at' => 'datetime',
        'is_full' => 'boolean',
        'shipping' => 'decimal:2',
        'shipping_tax' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Set when the order is invoiced per supplier: the one invoice this note reverses. */
    public function supplierInvoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class);
    }

    /** The returned lines. Empty for credit notes issued before partial returns existed (those reverse the whole invoice). */
    public function items(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class);
    }
}
