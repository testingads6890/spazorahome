<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * The customer account menu, in one place, so the account sidebar, the header "account" dropdown
 * and the mobile account sheet always show the same links in the same order.
 */
class AccountMenu
{
    /** @return array<int, array{route:string,label:string,icon:string,pattern:string|array}> */
    public static function items(): array
    {
        $user = auth()->user();
        if (! $user) {
            return [];
        }
        // Admins and sellers without a shopper account only get their own dashboard + logout.
        if (! $user->hasRole('customer')) {
            return [['route' => 'dashboard', 'label' => __('Dashboard'), 'icon' => 'bi-speedometer2', 'pattern' => 'dashboard']];
        }

        $items = [
            ['customer.dashboard', __('Overview'), 'bi-house', 'customer.dashboard'],
            ['customer.orders.index', __('Orders'), 'bi-cart3', ['customer.orders.*', 'customer.checkout.*']],
            ['customer.invoices.index', __('Invoices'), 'bi-file-earmark-text', 'customer.invoices.*'],
            ['customer.reviews.index', __('Reviews'), 'bi-star', 'customer.reviews.*'],
            ['customer.returns.index', __('Order Return Requests'), 'bi-cart-x', 'customer.returns.*'],
            ['customer.order-tracking.index', __('Track order'), 'bi-truck', 'customer.order-tracking.*'],
            ['customer.wishlists.index', __('Wishlist'), 'bi-heart', 'customer.wishlists.*'],
            ['customer.addresses.index', __('Addresses'), 'bi-book', 'customer.addresses.*'],
            ['customer.support-tickets.index', __('Support tickets'), 'bi-life-preserver', 'customer.support-tickets.*'],
            ['customer.profile.edit', __('Account Settings'), 'bi-gear', ['customer.profile.*', 'customer.professional-profile.*']],
        ];
        // Shoppers who do not sell yet can apply with this same account.
        if (! $user->vendorAccount()) {
            $items[] = ['customer.become-seller', __('Become Vendor'), 'bi-shop', 'customer.become-seller*'];
        }

        return collect($items)
            ->filter(fn ($i) => Route::has($i[0]))
            ->map(fn ($i) => ['route' => $i[0], 'label' => $i[1], 'icon' => $i[2], 'pattern' => $i[3]])
            ->values()->all();
    }

    public static function current(): ?array
    {
        foreach (self::items() as $item) {
            if (request()->routeIs(...(array) $item['pattern'])) {
                return $item;
            }
        }

        return null;
    }
}
