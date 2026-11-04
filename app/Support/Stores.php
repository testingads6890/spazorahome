<?php

namespace App\Support;

/**
 * Seller store pages (/stores) are switched off until sellers have their own store addresses.
 * Turn them on with SHOP_STORES_ENABLED=true in .env.
 */
class Stores
{
    public static function enabled(): bool
    {
        return (bool) config('shop.stores_enabled', false);
    }
}
