<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Limits a seller-panel area to people allowed to use it: `vendor.can:products`, `vendor.can:orders`,
 * `vendor.can:payouts`, or `vendor.can:owner` for the things only the shop's owner may touch.
 */
class VendorCan
{
    public function handle(Request $request, Closure $next, string $area)
    {
        abort_unless($request->user()?->vendorCan($area), 403, 'Your staff account does not have access to this area.');

        return $next($request);
    }
}
