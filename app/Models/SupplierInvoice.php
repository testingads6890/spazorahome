<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One supplier's tax invoice for an order, used when SHOP_INVOICE_MODE=per_supplier.
 * vendor_id null means the marketplace itself (its own goods and the shipping charge).
 */
class SupplierInvoice extends Model
{
    protected $fillable = ['order_id', 'vendor_id', 'number', 'issued_at'];

    protected $casts = ['issued_at' => 'datetime'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class)->withTrashed();
    }

    /** Who issued it, for buttons and lists. */
    public function supplierName(): string
    {
        return $this->vendor_id ? (string) ($this->vendor?->business_name ?? 'Seller') : (string) config('shop.seller.name');
    }
}
