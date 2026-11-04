<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Setting;

class RTLSupport
{
    public function handle(Request $request, Closure $next)
    {
        $direction = Setting::get('text_direction', 'ltr');
        
        if ($direction === 'rtl') {
            config(['app.rtl' => true]);
        }

        return $next($request);
    }
}