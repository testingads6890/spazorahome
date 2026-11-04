<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\User;
use App\Support\Countries;
use App\Support\GstState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

/**
 * Checkout without registering first. The visitor gives contact and delivery details; that creates a
 * customer account (with a password only if they chose one), saves the address, signs them in and hands
 * over to the normal checkout page, so totals, payment and the order itself use the one tested path.
 */
class GuestCheckoutController extends Controller
{
    public function show()
    {
        $cart = Cart::current();
        $cartItems = $cart ? $cart->items()->with(['product.images', 'variant'])->get() : collect();
        if ($cartItems->isEmpty()) {
            return redirect()->route('customer.cart.index')->with('error', __('shop.messages.cart_empty'));
        }

        return view('checkout.guest', ['cartItems' => $cartItems, 'states' => GstState::options(), 'countries' => config('shop.export.enabled') ? Countries::all() : []]);
    }

    public function store(Request $request)
    {
        $cart = Cart::current();
        if (! $cart || ! $cart->items()->exists()) {
            return redirect()->route('customer.cart.index')->with('error', __('shop.messages.cart_empty'));
        }

        // With international selling on, the visitor may be abroad: the country comes from a list and the state is typed.
        $export = (bool) config('shop.export.enabled');
        $country = $export && ! Countries::isIndia($request->input('country')) ? (string) $request->input('country') : Countries::INDIA;
        $abroad = $country !== Countries::INDIA;

        $data = $request->validate([
            'country' => $export ? ['nullable', Rule::in(Countries::all())] : ['nullable'],
            'first_name' => 'required|string|max:120',
            'last_name' => 'required|string|max:120',
            'email' => 'required|string|email|max:255',
            'phone' => 'required|string|max:20',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => $abroad ? 'required|string|max:100' : ['required', Rule::in(GstState::names())],
            'postal_code' => 'required|string|max:20',
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);
        $email = mb_strtolower($data['email']);

        // An existing account is never touched or opened from here: its owner signs in (the cart is kept).
        if (User::whereRaw('lower(email) = ?', [$email])->exists()) {
            return back()->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['email' => __('An account with this email already exists. Sign in to continue; your cart will be waiting.')])
                ->with('existing_account', true);
        }

        $user = DB::transaction(function () use ($data, $email, $country) {
            $user = User::create([
                'name' => trim($data['first_name'] . ' ' . $data['last_name']),
                'email' => $email,
                // No password chosen: a random one nobody knows, replaced later from the profile page or by a reset email.
                'password' => Hash::make($data['password'] ?? Str::random(40)),
                'is_guest' => empty($data['password']),
                'account_type' => 'customer',
            ]);
            $user->assignRole(Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']));
            $user->profile()->create(['phone' => $data['phone']]);
            $user->addresses()->create([
                'type' => 'shipping', 'first_name' => $data['first_name'], 'last_name' => $data['last_name'],
                'address_line_1' => $data['address_line_1'], 'address_line_2' => $data['address_line_2'] ?? null,
                'city' => $data['city'], 'state' => $data['state'], 'postal_code' => $data['postal_code'],
                'country' => $country, 'phone' => $data['phone'], 'is_default' => true,
            ]);

            return $user;
        });

        // Signing in moves the guest cart into the new account (see AppServiceProvider).
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('customer.checkout.index')->with('success', __('Details saved. Choose how to pay to place your order.'));
    }
}
