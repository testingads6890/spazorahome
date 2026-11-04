<?php

namespace App\Http\Controllers;

use App\Models\Newsletter;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $data = $request->validate(['email' => 'required|email:rfc|max:255']);

        // Same reply whether the address is new or already subscribed, so the form cannot be used to probe the list.
        $subscriber = Newsletter::firstOrCreate(
            ['email' => mb_strtolower($data['email'])],
            ['is_active' => true, 'subscribed_at' => now()]
        );
        if (! $subscriber->is_active) {
            $subscriber->update(['is_active' => true, 'subscribed_at' => now()]);
        }

        return redirect()->to(url()->previous() . '#newsletter')->with('newsletter', 'Thanks for subscribing!');
    }
}
