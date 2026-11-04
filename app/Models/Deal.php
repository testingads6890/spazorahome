<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deal extends Model
{
    use \App\Models\Concerns\CleansRichText;

    /** Columns written in the CKEditor: cleaned on every save. */
    protected array $richText = ['description'];

    protected $fillable = [
        'name', 'description', 'product_id', 'original_price', 'deal_price',
        'starts_at', 'ends_at', 'is_active',
    ];

    protected $casts = [
        'original_price' => 'decimal:2',
        'deal_price' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}