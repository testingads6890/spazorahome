<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingZone extends Model
{
    protected $fillable = [
        'name', 'countries', 'states', 'cities', 'rate', 'rate_per_kg', 'free_shipping_threshold', 'is_active',
    ];

    protected $casts = [
        'countries' => 'array',
        'states' => 'array',
        'cities' => 'array',
        'rate' => 'decimal:2',
        'rate_per_kg' => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /** Does this zone cover the address? An empty list means "any"; otherwise the address must match one entry. */
    public function matches(Address $address): bool
    {
        $checks = ['countries' => $address->country, 'states' => $address->state, 'cities' => $address->city];

        foreach ($checks as $field => $value) {
            $list = array_filter(array_map(fn ($v) => mb_strtolower(trim((string) $v)), $this->{$field} ?? []));
            if ($list && ! in_array(mb_strtolower(trim((string) $value)), $list, true)) {
                return false;
            }
        }

        return true;
    }

    /** More specific zones (city > state > country > everywhere) win. */
    public function specificity(): int
    {
        return (! empty($this->cities) ? 4 : 0) + (! empty($this->states) ? 2 : 0) + (! empty($this->countries) ? 1 : 0);
    }

    /** Best matching active zone for an address, or null. */
    public static function forAddress(?Address $address): ?self
    {
        if (! $address) {
            return null;
        }

        return static::where('is_active', true)->orderBy('id')->get()
            ->filter(fn (self $z) => $z->matches($address))
            ->sortByDesc(fn (self $z) => $z->specificity())
            ->first();
    }

    /**
     * Shipping charge for a subtotal: the matching zone's rate, else the store-wide default.
     * A zone with a per-kg rate adds that for the packed weight of the order (how international freight is priced).
     */
    public static function shippingFor(?Address $address, float $subtotal, float $weightKg = 0.0): float
    {
        $zone = static::forAddress($address);

        if ($zone) {
            $free = $zone->free_shipping_threshold;

            return $free !== null && $subtotal > (float) $free ? 0.0 : round((float) $zone->rate + (float) $zone->rate_per_kg * $weightKg, 2);
        }

        return $subtotal > (float) config('shop.free_shipping_over') ? 0.0 : (float) config('shop.shipping_fee');
    }
}
