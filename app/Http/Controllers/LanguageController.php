<?php

namespace App\Http\Controllers;

use App\Support\Locales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class LanguageController extends Controller
{
    public function switch(Request $request)
    {
        $code = $request->validate(['language' => 'required|string|max:10'])['language'];
        abort_unless(Locales::isAvailable($code), 422, 'That language is not available.');

        Cookie::queue(Cookie::make(Locales::COOKIE, $code, 60 * 24 * 365));

        return back();
    }
}
