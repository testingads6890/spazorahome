<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ShippingMethod extends Model
{
    public const TYPES = ['flat_rate' => 'Flat Rate', 'free_shipping' => 'Free Shipping', 'local_pickup' => 'Local Pickup'];

    protected $fillable = [
        'name', 'type', 'rate', 'min_order', 'delivery_estimate', 'sort_order', 'description', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rate' => 'decimal:2',
        'min_order' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    /**
     * Delivery options a customer may choose for an order of this value (after coupon, before GST).
     * An empty list means the shop has not set any up: checkout then charges the address's normal fee.
     *
     * @return Collection<int, self>
     */
    public static function availableFor(float $orderValue): Collection
    {
        return static::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()
            ->filter(fn (self $m) => $m->type !== 'free_shipping' || $m->min_order === null || $orderValue >= (float) $m->min_order)
            ->values();
    }

    /**
     * What this option costs for an address:
     * - flat rate: the address's normal fee (shipping zone or store default, with its free-shipping threshold) plus this option's extra charge
     * - free shipping and local pickup: nothing
     */
    public function chargeFor(?Address $address, float $orderValue, float $weightKg = 0.0): float
    {
        if (in_array($this->type, ['free_shipping', 'local_pickup'], true)) {
            return 0.0;
        }

        return round(ShippingZone::shippingFor($address, $orderValue, $weightKg) + (float) $this->rate, 2);
    }
}
