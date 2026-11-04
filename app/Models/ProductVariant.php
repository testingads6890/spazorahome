<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = ['product_id', 'sku', 'price', 'sale_price', 'stock_quantity', 'attributes'];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'attributes' => 'array',
    ];

    /** Human label such as "Red / L", built from the variant attributes; falls back to the SKU. */
    public function getLabelAttribute(): string
    {
        $values = collect($this->getAttribute('attributes') ?? [])->filter()->map(fn ($v) => is_array($v) ? implode(', ', $v) : (string) $v);

        return $values->isNotEmpty() ? $values->implode(' / ') : (string) $this->sku;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
