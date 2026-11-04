<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * The cart is open to visitors who are not signed in (guest cart) and to customers.
 * Signed-in admins and sellers are not shoppers and are kept out, as before.
 */
class Shopper
{
    public function handle(Request $request, Closure $next)
    {
        abort_if($request->user() && ! $request->user()->hasRole('customer'), 403, 'Unauthorized');

        return $next($request);
    }
}
