<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorUser extends Model
{
    /** Seller-panel areas a staff member can be given. */
    public const AREAS = ['products', 'orders', 'payouts'];

    public const AREA_LABELS = ['products' => 'Products', 'orders' => 'Orders', 'payouts' => 'Payouts'];

    protected $fillable = [
        'vendor_id', 'user_id', 'role', 'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Areas this person can actually use, for display. */
    public function areas(): array
    {
        return $this->role === 'manager' ? self::AREAS : array_values(array_intersect(self::AREAS, $this->permissions ?? []));
    }
}
