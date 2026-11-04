<?php

namespace App\Support;

use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\VendorProduct;

/** Sidebar menus for the admin and vendor panels, with live "needs attention" counts. */
class PanelNav
{
    /** @return array<string, array<int, array{0: string, 1: string, 2: string, 3: string, 4: int}>> group => [route, label, icon, active pattern, badge] */
    public static function admin(): array
    {
        $pendingVendors = Vendor::whereIn('status', ['pending', 'under_review'])->count();
        $pendingListings = VendorProduct::where('status', 'pending')->count();
        $user = auth()->user();
        $openTickets = SupportTicket::whereIn('status', ['open', 'escalated'])
            ->when(! AdminArea::seesAllTickets($user), fn ($q) => $q->where(fn ($w) => $w->where('assigned_to', $user?->id)->orWhereNull('assigned_to')))
            ->count();
        $newOrders = Order::where('status', 'pending')->count();

        $groups = [
            'Overview' => [
                ['admin.dashboard', 'Dashboard', 'bi-speedometer2', 'admin.dashboard', 0],
                ['admin.analytics.index', 'Analytics', 'bi-bar-chart', 'admin.analytics.*', 0],
                ['admin.reports.sales', 'Reports', 'bi-file-earmark-bar-graph', 'admin.reports.*', 0],
                ['admin.exports.index', 'Exports', 'bi-download', 'admin.exports.*', 0],
            ],
            'Catalogue' => [
                ['admin.products.index', 'Products', 'bi-box-seam', 'admin.products.*', 0],
                ['admin.categories.index', 'Categories', 'bi-grid', 'admin.categories.*', 0],
                ['admin.product-attributes.index', 'Attributes', 'bi-list-check', 'admin.product-attributes.*', 0],
                ['admin.collections.index', 'Collections', 'bi-collection', 'admin.collections.*', 0],
                ['admin.frequently-bought.index', 'Bought together', 'bi-bag-plus', 'admin.frequently-bought.*', 0],
                ['admin.room-ideas.index', 'Room ideas', 'bi-lamp', 'admin.room-ideas.*', 0],
            ],
            'Sales' => [
                ['admin.orders.index', 'Orders', 'bi-cart3', 'admin.orders.*', $newOrders],
                ['admin.return-requests.index', 'Return requests', 'bi-arrow-return-left', 'admin.return-requests.*', \Illuminate\Support\Facades\Schema::hasTable('return_requests') ? \App\Models\ReturnRequest::where('status', 'pending')->count() : 0],
                ['admin.international.index', 'International', 'bi-globe2', 'admin.international.*', 0],
                ['admin.customers.index', 'Customers', 'bi-people', 'admin.customers.*', 0],
                ['admin.bulk-enquiries.index', 'Bulk enquiries', 'bi-boxes', 'admin.bulk-enquiries.*', \Illuminate\Support\Facades\Schema::hasTable('bulk_enquiries') ? \App\Models\BulkEnquiry::where('status', 'new')->count() : 0],
                ['admin.professionals.index', 'Professionals', 'bi-person-badge', 'admin.professionals.*', \App\Models\ProfessionalProfile::where('is_verified', false)->count()],
                ['admin.reviews.index', 'Reviews', 'bi-star', 'admin.reviews.*', 0],
                ['admin.discounts.index', 'Discounts', 'bi-percent', 'admin.discounts.*', 0],
                ['admin.deals.index', 'Deals', 'bi-lightning-charge', 'admin.deals.*', 0],
                ['admin.shipping.zones.index', 'Shipping zones', 'bi-truck', 'admin.shipping.zones.*', 0],
                ['admin.shipping.methods.index', 'Delivery options', 'bi-box-arrow-right', 'admin.shipping.methods.*', 0],
                ['admin.payments.index', 'Payments', 'bi-credit-card', 'admin.payments.*', 0],
            ],
            'Marketplace' => [
                ['admin.vendors.index', 'Vendors', 'bi-shop', 'admin.vendors.*', $pendingVendors],
                ['admin.vendor-products.index', 'Vendor products', 'bi-box2', 'admin.vendor-products.*', $pendingListings],
                ['admin.vendor-orders.index', 'Vendor orders', 'bi-receipt', 'admin.vendor-orders.*', 0],
                ['admin.vendor-payouts.index', 'Payouts', 'bi-cash-coin', 'admin.vendor-payouts.*', 0],
                ['admin.vendor-withdrawals.index', 'Withdrawals', 'bi-bank', 'admin.vendor-withdrawals.*', \Illuminate\Support\Facades\Schema::hasTable('vendor_withdrawals') ? \App\Models\VendorWithdrawal::whereIn('status', ['pending', 'approved'])->count() : 0],
                ['admin.vendor-wallets.index', 'Seller wallets', 'bi-wallet2', 'admin.vendor-wallets.*', 0],
                ['admin.marketplace.index', 'Marketplace', 'bi-diagram-3', 'admin.marketplace.*', 0],
            ],
            'Support' => [
                ['admin.support-tickets.index', 'Support tickets', 'bi-life-preserver', 'admin.support-tickets.*', $openTickets],
            ],
            'Content' => [
                ['admin.banners.index', 'Banners', 'bi-image', 'admin.banners.*', 0],
                ['admin.pages.index', 'Pages', 'bi-file-text', 'admin.pages.*', 0],
                ['admin.static-pages.index', 'Static pages', 'bi-file-earmark-text', 'admin.static-pages.*', 0],
                ['admin.blogs.index', 'Blog', 'bi-journal-text', 'admin.blogs.*', 0],
                ['admin.sliders.index', 'Sliders', 'bi-images', 'admin.sliders.*', 0],
                ['admin.menus.index', 'Menus', 'bi-list-nested', 'admin.menus.*', 0],
                ['admin.newsletters.index', 'Newsletter', 'bi-envelope', 'admin.newsletters.*', 0],
            ],
            'Settings' => [
                ['admin.settings.index', 'Settings', 'bi-gear', 'admin.settings.*', 0],
                ['admin.seo.index', 'SEO', 'bi-search', 'admin.seo.*', 0],
                ['admin.theme.index', 'Theme', 'bi-palette', 'admin.theme.*', 0],
                ['admin.languages.index', 'Languages', 'bi-translate', 'admin.languages.*', 0],
                ['admin.currencies.index', 'Currencies', 'bi-currency-rupee', 'admin.currencies.*', 0],
                ['admin.translations.index', 'Translations', 'bi-chat-left-text', 'admin.translations.*', 0],
                ['admin.permissions.index', 'Roles and access', 'bi-shield-lock', 'admin.permissions.*', 0],
            ],
        ];

        // Staff roles only see the areas they can open.
        if ($user && ! $user->hasRole('admin')) {
            foreach ($groups as $label => $items) {
                $groups[$label] = array_values(array_filter($items, fn ($item) => $item[0] !== 'admin.dashboard' && AdminArea::allows($user, $item[0])));
            }
            $groups = array_filter($groups);
        }

        return $groups;
    }

    /** The seller menu, trimmed to the areas this person may use (staff see less than the owner). */
    public static function vendor(?Vendor $vendor, ?\App\Models\User $user = null): array
    {
        $pending = $vendor ? VendorOrder::where('vendor_id', $vendor->id)->where('status', 'pending')->where('payout_status', '!=', 'failed')->count() : 0;
        $can = fn (string $area) => $user === null || $user->vendorCan($area);
        $unanswered = 0;
        if ($vendor && \Illuminate\Support\Facades\Schema::hasColumn('reviews', 'vendor_reply')) {
            $unanswered = \App\Models\Review::whereNull('vendor_reply')->whereHas('product', fn ($q) => $q->where('vendor_id', $vendor->id))->count();
        }

        $groups = [
            'Overview' => [
                ['vendor.dashboard', 'Dashboard', 'bi-speedometer2', 'vendor.dashboard', 0],
            ],
            'Selling' => array_values(array_filter([
                $can('products') ? ['vendor.products.index', 'Products', 'bi-box-seam', 'vendor.products.*', 0] : null,
                $can('orders') ? ['vendor.orders.index', 'Orders', 'bi-cart3', 'vendor.orders.*', $pending] : null,
                $can('orders') ? ['vendor.returns.index', 'Returns', 'bi-arrow-return-left', 'vendor.returns.*', 0] : null,
                $can('products') ? ['vendor.reviews.index', 'Reviews', 'bi-star', 'vendor.reviews.*', $unanswered] : null,
                $can('products') ? ['vendor.coupons.index', 'Coupons', 'bi-ticket-perforated', 'vendor.coupons.*', 0] : null,
            ])),
            'Earnings' => array_values(array_filter([
                $can('payouts') ? ['vendor.wallet.index', 'Wallet', 'bi-wallet2', 'vendor.wallet.*', 0] : null,
                $can('payouts') ? ['vendor.payouts.index', 'Payouts', 'bi-cash-coin', 'vendor.payouts.*', 0] : null,
                $can('payouts') ? ['vendor.withdrawals.index', 'Withdrawals', 'bi-bank', 'vendor.withdrawals.*', 0] : null,
            ])),
            'Account' => $can('owner') ? [
                ['vendor.staff.index', 'Staff', 'bi-people', 'vendor.staff.*', 0],
                ['vendor.profile.edit', 'Business profile', 'bi-building', 'vendor.profile.*', 0],
            ] : [],
        ];

        return array_filter($groups);
    }
}
