<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

/** "Forgot password": email a one-time link, then let its holder choose a new password. */
class PasswordResetController extends Controller
{
    public function request()
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request)
    {
        $request->validate(['email' => 'required|email|max:255']);

        try {
            Password::sendResetLink(['email' => mb_strtolower($request->email)]);
        } catch (\Throwable $e) {
            // A mail server problem must not reveal anything or show an error page.
            report($e);
        }

        // The same answer whether or not the address has an account, so the form cannot be used to find out.
        return back()->with('status', __('If that email has an account, a link to choose a new password is on its way.'));
    }

    public function reset(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email|max:255',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            ['email' => mb_strtolower($request->email), 'password' => $request->password, 'password_confirmation' => $request->password_confirmation, 'token' => $request->token],
            function (User $user, string $password) {
                // A guest-checkout account becomes a normal one the moment it has a password of its own.
                $user->forceFill(['password' => Hash::make($password), 'is_guest' => false, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors(['email' => __('This link is no longer valid. Ask for a new one.')]);
        }

        return redirect()->route('login')->with('status', __('Password changed. You can sign in now.'));
    }
}
