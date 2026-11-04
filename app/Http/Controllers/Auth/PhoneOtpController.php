<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\FirebaseIdToken;
use App\Support\InvalidFirebaseToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * Sign in and sign up with a mobile number. The browser proves the number with Firebase (SMS code), then sends the
 * Firebase ID token here. The server verifies that token itself; a number only ever signs in when it was verified.
 */
class PhoneOtpController extends Controller
{
    public function customerLogin(Request $request)
    {
        return $this->signIn($request, 'customer', '/customer/dashboard');
    }

    public function vendorLogin(Request $request)
    {
        return $this->signIn($request, 'vendor', '/vendor/dashboard');
    }

    public function customerRegister(Request $request)
    {
        $data = $request->validate([
            'id_token' => ['required', 'string', 'max:4096'],
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => __('Enter your name.'),
        ]);

        $phone = $this->verifiedPhone($data['id_token']);

        if (User::where('phone', $phone)->exists()) {
            throw ValidationException::withMessages(['id_token' => __('This mobile number already has an account. Sign in with it instead.')]);
        }

        $user = DB::transaction(function () use ($data, $phone) {
            $user = User::create([
                'name' => $data['name'],
                'email' => null, // OTP accounts start without an email; they can add one from their profile later
                'password' => Hash::make(Str::random(48)), // unusable: this account signs in with the mobile OTP
                'account_type' => 'customer',
                'phone' => $phone,
                'phone_verified_at' => now(),
            ]);
            $user->assignRole(Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']));
            $user->profile()->create(['phone' => $phone]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('customer.dashboard')->with('success', __('Welcome! Your mobile number is verified.'));
    }

    private function signIn(Request $request, string $role, string $fallback)
    {
        $data = $request->validate(['id_token' => ['required', 'string', 'max:4096']]);
        $phone = $this->verifiedPhone($data['id_token']);

        $user = User::where('phone', $phone)->whereNotNull('phone_verified_at')->first();
        if (! $user) {
            throw ValidationException::withMessages(['id_token' => __('No account is linked to this mobile number. Create an account, or sign in with your email and password.')]);
        }
        if (! $user->hasRole($role)) {
            throw ValidationException::withMessages(['id_token' => __('This sign-in is for :role accounts only.', ['role' => __($role)])]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended($fallback);
    }

    /** @return string E.164 mobile number the token proves. */
    private function verifiedPhone(string $idToken): string
    {
        try {
            return app(FirebaseIdToken::class)->verify($idToken)['phone'];
        } catch (InvalidFirebaseToken $e) {
            throw ValidationException::withMessages(['id_token' => $e->getMessage()]);
        }
    }
}
