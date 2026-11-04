<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = ['cart_id', 'product_id', 'variant_id', 'quantity', 'unit_price'];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /** Current price from the catalog; never trust a client-supplied price. */
    public function baseUnitPrice(): float
    {
        $source = $this->variant ?? $this->product;

        return (float) ($source->sale_price ?? $source->price);
    }

    public function availableStock(): int
    {
        return (int) (($this->variant ?? $this->product)->stock_quantity ?? 0);
    }

    /** Price of one unit in this line, after the bulk discount for the line's quantity. */
    public function currentUnitPrice(): float
    {
        $base = (float) $this->baseUnitPrice();

        return $this->product ? $this->product->bulkUnitPrice($base, (int) $this->quantity) : round($base, 2);
    }

    /** Percent off each unit thanks to the quantity in this line (0 when none applies). */
    public function bulkDiscount(): float
    {
        return $this->product ? $this->product->bulkDiscountFor((int) $this->quantity) : 0.0;
    }
}
