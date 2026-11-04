<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ProfileRequest;
use App\Models\Profile;

class ProfileController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();
        $stats = [
            'orders' => $user->orders()->count(),
            'wishlist' => $user->wishlists()->count(),
            'tickets' => $user->supportTickets()->whereIn('status', ['open', 'in_progress', 'escalated'])->count(),
            // Money actually spent: cancelled orders do not count.
            'spent' => (float) $user->orders()->where('status', '!=', 'cancelled')->sum('total'),
        ];
        $recent = $user->orders()->with('items')->latest()->limit(4)->get();
        $address = $user->addresses()->orderByDesc('is_default')->first();

        return view('customer.dashboard', compact('user', 'stats', 'recent', 'address'));
    }

    public function edit()
    {
        $profile = auth()->user()->profile()->firstOrNew();
        return view('customer.profile.edit', compact('profile'));
    }

    public function update(ProfileRequest $request)
    {
        $data = $request->validated();
        auth()->user()->update(['name' => $data['name'], 'email' => $data['email']]);
        auth()->user()->profile()->updateOrCreate(
            ['user_id' => auth()->id()],
            collect($data)->only(['phone', 'whatsapp', 'other_phone', 'date_of_birth', 'gender'])->all()
        );
        return redirect()->route('customer.profile.edit')->with('success', __('Profile updated successfully.'));
    }

    /**
     * Change the password. Someone who checked out as a guest has no password to type yet, so they only
     * choose one; everybody else must give their current password first.
     */
    public function password(\Illuminate\Http\Request $request)
    {
        $user = $request->user();
        $request->validate([
            'current_password' => $user->is_guest ? ['nullable'] : ['required', 'current_password'],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $user->forceFill(['password' => \Illuminate\Support\Facades\Hash::make($request->password), 'is_guest' => false])->save();

        return redirect()->route('customer.profile.edit')->with('success', __('Password saved.'));
    }
}
