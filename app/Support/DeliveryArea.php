<?php

namespace App\Support;

use App\Models\Pincode;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The delivery PIN a shopper gives (kept in the session). A product that has a dispatch location is only
 * deliverable to shoppers whose PIN falls inside it; a product without one is delivered across India.
 */
class DeliveryArea
{
    private const SESSION_KEY = 'delivery_pincode_id';

    public static function current(): ?Pincode
    {
        $id = session(self::SESSION_KEY);

        return $id ? Pincode::with('city.district.state')->find($id) : null;
    }

    /** Remember a 6-digit PIN. Returns null when the PIN is not in our list. */
    public static function set(string $code): ?Pincode
    {
        $pin = Pincode::where('code', $code)->where('is_active', true)->with('city.district.state')->first();
        if ($pin) {
            session([self::SESSION_KEY => $pin->id]);
        }

        return $pin;
    }

    /** The PIN a saved address has, when that PIN is in our list (used at checkout). */
    public static function forPin(?string $code): ?Pincode
    {
        if (! filled($code)) {
            return null;
        }

        return Pincode::where('code', trim($code))->where('is_active', true)->with('city.district.state')->first();
    }

    public static function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /** Several PIN codes can be set per product (product_pincode) plus the single one kept on the product. */
    public static function pincodeIds(Product $product): array
    {
        $ids = DB::table('product_pincode')->where('product_id', $product->id)->pluck('pincode_id')->map(fn ($id) => (int) $id)->all();
        if ($product->pincode_id) {
            $ids[] = (int) $product->pincode_id;
        }

        return $ids;
    }

    /** Does the product have any dispatch location? (A country alone is only the default and does not limit it.) */
    public static function hasLocation(Product $product): bool
    {
        return (bool) (self::pincodeIds($product) || $product->city_id || $product->district_id || $product->state_id);
    }

    /** Save the PIN codes ticked on the product form (the form also sends a marker, so an empty choice clears them). */
    public static function syncPincodes(Product $product, Request $request): void
    {
        if (! $request->has('pincode_ids_sent')) {
            return;
        }
        $ids = collect((array) $request->input('pincode_ids', []))->map(fn ($id) => (int) $id)->filter()->unique()->values();
        $valid = Pincode::whereIn('id', $ids)->pluck('id')->all();

        DB::table('product_pincode')->where('product_id', $product->id)->delete();
        foreach ($valid as $id) {
            DB::table('product_pincode')->insert(['product_id' => $product->id, 'pincode_id' => $id]);
        }
    }

    /**
     * Can this product be delivered to the given PIN? A null PIN means nothing is known yet.
     * The narrowest level the vendor chose decides: PIN codes first, then city, district and state.
     */
    public static function canDeliver(Product $product, ?Pincode $area): bool
    {
        if (! self::hasLocation($product)) {
            return true;
        }
        if (! $area) {
            return false;
        }

        $pincodeIds = self::pincodeIds($product);
        if ($pincodeIds) {
            return in_array($area->id, $pincodeIds, true);
        }

        $city = $area->city;
        if (! $city) {
            return false;
        }
        if ($product->city_id) {
            return (int) $product->city_id === (int) $city->id;
        }
        if ($product->district_id) {
            return (int) $product->district_id === (int) $city->district_id;
        }

        return (int) $product->state_id === (int) $city->district?->state_id;
    }
}
