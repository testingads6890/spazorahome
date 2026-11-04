<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Which parts of the admin panel each staff role may open. The "admin" role sees everything;
 * every other staff role is limited to the route-name patterns listed here.
 */
class AdminArea
{
    /** role => [label, description, allowed admin route-name patterns] */
    public const ROLES = [
        'vendor_manager' => ['Vendor manager', 'Seller applications, documents, vendor listings, vendor orders and payouts.', [
            'admin.vendors.*', 'admin.vendor-products.*', 'admin.vendor-orders.*', 'admin.vendor-payouts.*', 'admin.vendor-withdrawals.*', 'admin.vendor-wallets.*', 'admin.marketplace.*', 'admin.reports.vendors',
        ]],
        'product_manager' => ['Product manager', 'Catalogue: products, variants, categories, attributes, collections and reviews.', [
            'admin.products.*', 'admin.categories.*', 'admin.product-attributes.*', 'admin.swatches.*', 'admin.collections.*',
            'admin.frequently-bought.*', 'admin.room-ideas.*', 'admin.reviews.*', 'admin.vendor-products.*', 'admin.reports.products',
        ]],
        'order_manager' => ['Order manager', 'Orders, shipments, returns, invoices, customers and professionals.', [
            'admin.orders.*', 'admin.return-requests.*', 'admin.customers.*', 'admin.international.*', 'admin.shipping.*', 'admin.professionals.*', 'admin.reports.sales',
        ]],
        'support_manager' => ['Support manager', 'All support tickets: assigning, escalations, call records and support reports.', [
            'admin.support-tickets.*', 'admin.customers.*', 'admin.reports.support',
        ]],
        'support_executive' => ['Support executive', 'Their own and unassigned support tickets, replies, call logs and follow-ups.', [
            'admin.support-tickets.*',
        ]],
    ];

    /** Every role that may sign in to the admin panel. */
    public static function panelRoles(): array
    {
        return array_merge(['admin'], array_keys(self::ROLES));
    }

    public static function isStaff(?User $user): bool
    {
        return $user !== null && $user->hasAnyRole(self::panelRoles());
    }

    public static function allows(?User $user, string $routeName): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->hasRole('admin')) {
            return true;
        }
        if ($routeName === 'admin.dashboard') {
            return self::isStaff($user);
        }
        foreach (self::ROLES as $role => [, , $patterns]) {
            if ($user->hasRole($role) && Str::is($patterns, $routeName)) {
                return true;
            }
        }

        return false;
    }

    /** Where a staff member lands after signing in: the first area their role opens. */
    public static function home(User $user): string
    {
        if ($user->hasRole('admin')) {
            return route('admin.dashboard');
        }
        $landing = [
            'support_executive' => 'admin.support-tickets.index',
            'support_manager' => 'admin.support-tickets.index',
            'order_manager' => 'admin.orders.index',
            'vendor_manager' => 'admin.vendors.index',
            'product_manager' => 'admin.products.index',
        ];
        foreach ($landing as $role => $route) {
            if ($user->hasRole($role)) {
                return route($route);
            }
        }

        return route('home');
    }

    /** Can this person see every ticket (not just their own and the unassigned queue)? */
    public static function seesAllTickets(?User $user): bool
    {
        return $user !== null && $user->hasAnyRole(['admin', 'support_manager']);
    }
}
