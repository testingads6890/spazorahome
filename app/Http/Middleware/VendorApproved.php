<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VendorApproved
{
    public function handle(Request $request, Closure $next)
    {
        $vendor = $request->user()?->vendorAccount();

        if (! $vendor || ! $vendor->is_active) {
            // A friendly status page instead of a bare 403.
            return response()->view('vendor.pending', ['vendor' => $vendor], 403);
        }

        return $next($request);
    }
}
