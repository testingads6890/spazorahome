<?php

namespace App\Http\Middleware;

use App\Support\AdminArea as Areas;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Admin panel gate: full admins pass; staff roles only reach the areas their role covers. */
class AdminArea
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! Areas::isStaff($user)) {
            abort(403, 'Unauthorized');
        }

        $route = (string) $request->route()?->getName();
        if (! $user->hasRole('admin') && $route === 'admin.dashboard') {
            // Staff start on the first area their role opens rather than the owner's sales dashboard.
            return redirect()->to(Areas::home($user));
        }
        if (! Areas::allows($user, $route)) {
            abort(403, 'Your role does not include this area of the admin panel.');
        }

        return $next($request);
    }
}
