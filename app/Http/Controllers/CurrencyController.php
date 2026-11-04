<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class CurrencyController extends Controller
{
    public function switch(Request $request)
    {
        $code = strtoupper($request->validate(['currency' => 'required|string|max:10'])['currency']);

        $valid = $code === 'INR' || Currency::where('is_active', true)->where('code', $code)->where('exchange_rate', '>', 0)->exists();
        abort_unless($valid, 422, 'That currency is not available.');

        // A year-long cookie; INR simply clears the choice.
        Cookie::queue($code === 'INR' ? Cookie::forget(Money::COOKIE) : Cookie::make(Money::COOKIE, $code, 60 * 24 * 365));

        return back();
    }
}
