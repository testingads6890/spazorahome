<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'user_id', 'type', 'first_name', 'last_name', 'company',
        'address_line_1', 'address_line_2', 'city', 'state', 'postal_code', 'country', 'phone', 'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /** GST state code (e.g. 27) for this address, or null if the stored state is not recognised. */
    public function getStateCodeAttribute(): ?string
    {
        return \App\Support\GstState::code($this->state);
    }

    /** Delivered outside India, and the shop sells abroad. With exports switched off every address is treated as Indian, as before. */
    public function isForeign(): bool
    {
        return config('shop.export.enabled') && ! \App\Support\Countries::isIndia($this->country);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getAddressTypeAttribute(): ?string
    {
        return $this->type;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
