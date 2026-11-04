<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm(Request $request)
    {
        // The same form serves customers and vendors; only the heading and the role check differ.
        $role = $request->routeIs('vendor.login') ? 'vendor' : ($request->routeIs('customer.login') ? 'customer' : null);
        $action = match ($role) {
            'vendor' => route('vendor.login.post'),
            'customer' => route('customer.login.post'),
            default => route('login.post'),
        };

        return view('auth.login', compact('role', 'action'));
    }

    public function showAdminLoginForm()
    {
        return view('auth.admin-login');
    }

    /** Any role; lands on the dashboard that matches the account. */
    public function login(Request $request)
    {
        return $this->attempt($request, null, '/dashboard');
    }

    public function adminLogin(Request $request)
    {
        // The admin form labels the field "username" but accounts are identified by email.
        $request->merge(['email' => $request->input('email', $request->input('username'))]);

        // Owners and every staff role (managers, support executives) sign in here.
        return $this->attempt($request, \App\Support\AdminArea::panelRoles(), '/admin/dashboard');
    }

    public function customerLogin(Request $request)
    {
        return $this->attempt($request, 'customer', '/customer/dashboard');
    }

    public function vendorLogin(Request $request)
    {
        return $this->attempt($request, 'vendor', '/vendor/dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function attempt(Request $request, string|array|null $role, string $fallback)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => __('The email or password is incorrect.')])->onlyInput('email');
        }

        if ($role !== null && ! $request->user()->hasAnyRole((array) $role)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => __('This sign-in is for :role accounts only.', ['role' => __(is_array($role) ? 'staff' : ucfirst($role))])])->onlyInput('email');
        }

        $request->session()->regenerate();

        // Staff without the full admin role land on the area their role covers.
        if (is_array($role) && ! $request->user()->hasRole('admin')) {
            return redirect()->intended(\App\Support\AdminArea::home($request->user()));
        }

        return redirect()->intended($fallback);
    }
}
