<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\RazorpayController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductAttributeController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\CollectionController;
use App\Http\Controllers\Admin\DiscountController;
use App\Http\Controllers\Admin\ShippingController;
use App\Http\Controllers\Admin\DealController;
use App\Http\Controllers\Admin\FrequentlyBoughtTogetherController;
use App\Http\Controllers\Admin\SwatchController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\MarketplaceController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\CurrencyController;
use App\Http\Controllers\Admin\SeoController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\TranslationController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\StaticPageController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\RoomIdeaController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SupportTicketController as AdminSupportTicketController;
use App\Http\Controllers\Admin\VendorController as AdminVendorController;
use App\Http\Controllers\Admin\VendorOrderController as AdminVendorOrderController;
use App\Http\Controllers\Admin\VendorPayoutController as AdminVendorPayoutController;
use App\Http\Controllers\Admin\VendorProductController as AdminVendorProductController;
use App\Http\Controllers\Customer\AddressController;
use App\Http\Controllers\Customer\CompareController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\Customer\OrderTrackingController;
use App\Http\Controllers\Customer\ReviewController;
use App\Http\Controllers\Customer\WishlistController;
use App\Http\Controllers\Customer\SupportTicketController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\Customer\ProfessionalProfileController;
use App\Http\Controllers\Customer\GalleryController;
use App\Http\Controllers\Vendor\DashboardController as VendorDashboardController;
use App\Http\Controllers\Vendor\ProductController as VendorProductController;
use App\Http\Controllers\Vendor\OrderController as VendorOrderController;
use App\Http\Controllers\Vendor\StaffController as VendorStaffController;
use App\Http\Controllers\Vendor\PayoutController as VendorPayoutController;
use App\Http\Controllers\Vendor\ProfileController as VendorProfileController;


// TEMPORARY: open /clear-cache-YOUR-SECRET-123 once, then DELETE these lines.
Route::get('/clear-cache-YOUR-SECRET-123', function () {
    foreach (['route:clear', 'config:clear', 'view:clear', 'cache:clear'] as $cmd) {
        \Illuminate\Support\Facades\Artisan::call($cmd);
    }
    return 'Caches cleared';
});
// Public storefront
Route::get('/', [StorefrontController::class, 'home'])->name('home');
// Uploaded pictures on a server where the public/storage link was never created (php artisan storage:link).
// With the link in place the web server answers these itself and this route is never reached.
Route::get('/storage/{path}', function (string $path) {
    $disk = \Illuminate\Support\Facades\Storage::disk('public');
    abort_unless(! str_contains($path, '..') && $disk->exists($path), 404);

    return $disk->response($path, null, ['Cache-Control' => 'public, max-age=2592000']);
})->where('path', '.*')->name('storage.public');

// Readable addresses: /shop, /shop/living-room, /product/oak-dining-table
Route::get('/shop/{category?}', [StorefrontController::class, 'search'])->name('search');
Route::get('/product/{product:slug}', [StorefrontController::class, 'show'])->name('products.show');
Route::post('/product/{product:slug}/export-enquiry', [\App\Http\Controllers\ExportEnquiryController::class, 'store'])->middleware('throttle:5,1')->name('products.export-enquiry');
Route::get('/product/{product:slug}/quick-view', [StorefrontController::class, 'quick'])->name('products.quick-view');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

// Addresses used before the readable ones: each is sent on permanently (301), so old links and bookmarks keep working.
Route::get('/search', [StorefrontController::class, 'search']);
Route::get('/products/category/{slug}', [StorefrontController::class, 'category'])->name('products.category');
Route::get('/products/{id}', [StorefrontController::class, 'legacyProduct'])->whereNumber('id');
foreach ([
    '/terms' => '/terms-and-conditions',
    '/privacy' => '/privacy-policy',
    '/shipping' => '/shipping-policy',
    '/returns' => '/returns-and-refunds',
    '/vendor/register' => '/sell-with-us',
    '/customer/cart' => '/cart',
    '/customer/wishlists' => '/wishlist',
    '/customer/compare' => '/compare',
    '/customer/order-tracking' => '/track-order',
] as $old => $new) {
    // GET only: forms still post to their own addresses.
    Route::get($old, fn (\Illuminate\Http\Request $request) => redirect()->to(url($new) . ($request->getQueryString() ? '?' . $request->getQueryString() : ''), 301));
}
Route::post('/newsletter', [\App\Http\Controllers\NewsletterController::class, 'subscribe'])->middleware('throttle:6,1')->name('newsletter.subscribe');

Route::post('/language', [\App\Http\Controllers\LanguageController::class, 'switch'])->middleware('throttle:30,1')->name('language.switch');
Route::post('/currency', [\App\Http\Controllers\CurrencyController::class, 'switch'])->middleware('throttle:30,1')->name('currency.switch');

// Marketplace sellers
Route::get('/stores', [\App\Http\Controllers\StoreController::class, 'index'])->name('stores.index');
Route::get('/stores/{vendor:slug}', [\App\Http\Controllers\StoreController::class, 'show'])->name('stores.show');

// Public blog (posts are written in Admin > Blog)
Route::get('/blog', [\App\Http\Controllers\BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [\App\Http\Controllers\BlogController::class, 'show'])->name('blog.show');

// Static pages
Route::get('/about-us', [StaticPageController::class, 'show'])->defaults('slug', 'about-us')->name('about-us');
Route::get('/contact-us', [StaticPageController::class, 'contact'])->name('contact-us');
Route::get('/terms-and-conditions', [StaticPageController::class, 'terms'])->name('terms');
Route::get('/privacy-policy', [StaticPageController::class, 'privacy'])->name('privacy');
Route::get('/shipping-policy', [StaticPageController::class, 'shipping'])->name('shipping');
Route::get('/returns-and-refunds', [StaticPageController::class, 'returns'])->name('returns');
Route::get('/faq', [StaticPageController::class, 'faq'])->name('faq');

// Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1')->name('login.post');
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->name('register.post');

    Route::get('/admin/login', [LoginController::class, 'showAdminLoginForm'])->name('admin.login');
    Route::post('/admin/login', [LoginController::class, 'adminLogin'])->middleware('throttle:10,1')->name('admin.login.post');

    Route::get('/customer/login', [LoginController::class, 'showLoginForm'])->name('customer.login');
    Route::post('/customer/login', [LoginController::class, 'customerLogin'])->middleware('throttle:10,1')->name('customer.login.post');
    Route::get('/customer/register', [RegisterController::class, 'showCustomerRegistrationForm'])->name('customer.register');
    Route::post('/customer/register', [RegisterController::class, 'customerRegister'])->name('customer.register.post');

    Route::get('/vendor/login', [LoginController::class, 'showLoginForm'])->name('vendor.login');
    Route::post('/vendor/login', [LoginController::class, 'vendorLogin'])->middleware('throttle:10,1')->name('vendor.login.post');
    Route::get('/sell-with-us', [RegisterController::class, 'showVendorRegistrationForm'])->name('vendor.register');
    Route::post('/vendor/register', [RegisterController::class, 'vendorRegister'])->name('vendor.register.post');
});
Route::get('/dashboard', function () {
    $user = auth()->user();
    if (\App\Support\AdminArea::isStaff($user)) {
        return redirect()->to(\App\Support\AdminArea::home($user));
    }
    return redirect()->route(match (true) {
        $user->hasRole('vendor') => 'vendor.dashboard',
        default => 'customer.dashboard',
    });
})->middleware('auth')->name('dashboard');
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// Email verification (the link in the welcome email). Signing in never depends on it.
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', fn () => view('auth.verify-email'))->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect('/dashboard')->with('status', __('Thanks, your email address is verified.'));
    })->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', function (\Illuminate\Http\Request $request) {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return back()->with('status', __('A new verification link is on its way to your inbox.'));
    })->middleware('throttle:6,1')->name('verification.send');
});

// Payments
Route::middleware('auth')->prefix('payments/razorpay')->name('payments.razorpay.')->group(function () {
    Route::get('/callback', [RazorpayController::class, 'callback'])->name('callback');
    Route::get('/{order}', [RazorpayController::class, 'initiate'])->name('initiate');
});

// Admin Routes
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin.area'])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Catalog
    Route::resource('categories', CategoryController::class);
    Route::resource('banners', BannerController::class);
    Route::resource('products', AdminProductController::class);
    Route::resource('products.variants', ProductVariantController::class)->except(['show']);
    Route::resource('product-attributes', ProductAttributeController::class)->except(['show']);
    Route::prefix('product-attributes/{productAttribute}/swatches')->name('swatches.')->group(function () {
        Route::get('/', [SwatchController::class, 'index'])->name('index');
        Route::get('/create', [SwatchController::class, 'create'])->name('create');
        Route::post('/', [SwatchController::class, 'store'])->name('store');
        Route::get('/{swatch}/edit', [SwatchController::class, 'edit'])->name('edit');
        Route::put('/{swatch}', [SwatchController::class, 'update'])->name('update');
        Route::delete('/{swatch}', [SwatchController::class, 'destroy'])->name('destroy');
    });
    Route::resource('collections', CollectionController::class)->except(['show']);
    Route::resource('frequently-bought', FrequentlyBoughtTogetherController::class)->except(['show'])->parameters(['frequently-bought' => 'frequentlyBoughtTogether']);
    Route::resource('room-ideas', RoomIdeaController::class);

    // Sales
    Route::resource('discounts', DiscountController::class)->except(['show']);
    Route::resource('deals', DealController::class)->except(['show']);
    Route::resource('payments', PaymentController::class)->except(['show'])->parameters(['payments' => 'paymentMethod']);
    Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::get('orders/{order}/shipping/create', [AdminOrderController::class, 'createShipment'])->name('orders.shipping.create');
    Route::post('orders/{order}/shipping', [AdminOrderController::class, 'storeShipment'])->name('orders.shipping.store');
    Route::post('orders/{order}/shipment', [AdminOrderController::class, 'updateShipment'])->name('orders.shipment.update');
    Route::get('orders/{order}/invoice', [AdminOrderController::class, 'invoice'])->name('orders.invoice');
    Route::get('orders/{order}/packing-list', [AdminOrderController::class, 'packingList'])->name('orders.packing-list');
    Route::get('orders/{order}/invoice/pdf', [AdminOrderController::class, 'invoicePdf'])->name('orders.invoice.pdf');
    Route::get('orders/{order}/invoices/{supplierInvoice}', [AdminOrderController::class, 'showInvoice'])->name('orders.invoices.show');
    Route::get('orders/{order}/invoices/{supplierInvoice}/pdf', [AdminOrderController::class, 'showInvoicePdf'])->name('orders.invoices.pdf');
    Route::get('orders/{order}/credit-note', [AdminOrderController::class, 'creditNote'])->name('orders.credit-note');
    Route::get('orders/{order}/credit-note/pdf', [AdminOrderController::class, 'creditNotePdf'])->name('orders.credit-note.pdf');
    Route::get('orders/{order}/credit-notes/{creditNote}', [AdminOrderController::class, 'showCreditNote'])->name('orders.credit-notes.show');
    Route::get('orders/{order}/credit-notes/{creditNote}/pdf', [AdminOrderController::class, 'showCreditNotePdf'])->name('orders.credit-notes.pdf');
    Route::post('orders/{order}/returns', [AdminOrderController::class, 'storeReturn'])->name('orders.returns.store');
    Route::post('orders/{order}/payments/{payment}/refund', [AdminOrderController::class, 'retryRefund'])->name('orders.payments.refund');
    Route::post('orders/{order}/payments/{payment}/refunded', [AdminOrderController::class, 'markRefunded'])->name('orders.payments.refunded');
    Route::post('orders/{order}/payments/{payment}/paid', [AdminOrderController::class, 'markPaid'])->name('orders.payments.paid');
    Route::put('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::resource('reviews', AdminReviewController::class)->only(['index', 'edit', 'update', 'destroy']);
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

    Route::prefix('shipping')->name('shipping.')->group(function () {
        Route::get('/zones', [ShippingController::class, 'zonesIndex'])->name('zones.index');
        Route::get('/zones/create', [ShippingController::class, 'zonesCreate'])->name('zones.create');
        Route::post('/zones', [ShippingController::class, 'zonesStore'])->name('zones.store');
        Route::get('/zones/{shippingZone}/edit', [ShippingController::class, 'zonesEdit'])->name('zones.edit');
        Route::put('/zones/{shippingZone}', [ShippingController::class, 'zonesUpdate'])->name('zones.update');
        Route::delete('/zones/{shippingZone}', [ShippingController::class, 'zonesDestroy'])->name('zones.destroy');
        Route::get('/methods', [ShippingController::class, 'methodsIndex'])->name('methods.index');
        Route::get('/methods/create', [ShippingController::class, 'methodsCreate'])->name('methods.create');
        Route::post('/methods', [ShippingController::class, 'methodsStore'])->name('methods.store');
        Route::get('/methods/{shippingMethod}/edit', [ShippingController::class, 'methodsEdit'])->name('methods.edit');
        Route::put('/methods/{shippingMethod}', [ShippingController::class, 'methodsUpdate'])->name('methods.update');
        Route::delete('/methods/{shippingMethod}', [ShippingController::class, 'methodsDestroy'])->name('methods.destroy');
    });

    // Support
    Route::get('support-tickets', [AdminSupportTicketController::class, 'index'])->name('support-tickets.index');
    Route::get('support-tickets/{ticket}', [AdminSupportTicketController::class, 'show'])->name('support-tickets.show');
    Route::post('support-tickets/{ticket}/assign', [AdminSupportTicketController::class, 'assign'])->name('support-tickets.assign');
    Route::post('support-tickets/{ticket}/status', [AdminSupportTicketController::class, 'updateStatus'])->name('support-tickets.status');
    Route::post('support-tickets/{ticket}/messages', [AdminSupportTicketController::class, 'message'])->name('support-tickets.message');
    Route::post('support-tickets/{ticket}/take', [AdminSupportTicketController::class, 'take'])->name('support-tickets.take');
    Route::post('support-tickets/{ticket}/escalate', [AdminSupportTicketController::class, 'escalate'])->name('support-tickets.escalate');
    Route::post('support-tickets/{ticket}/follow-up', [AdminSupportTicketController::class, 'followUp'])->name('support-tickets.follow-up');
    Route::post('support-tickets/{ticket}/calls', [AdminSupportTicketController::class, 'storeCall'])->name('support-tickets.calls.store');

    // Designers and architects who signed up as professionals
    Route::get('professionals', [\App\Http\Controllers\Admin\ProfessionalController::class, 'index'])->name('professionals.index');
    Route::post('professionals/{profile}/verify', [\App\Http\Controllers\Admin\ProfessionalController::class, 'verify'])->name('professionals.verify');
    Route::post('professionals/{profile}/unverify', [\App\Http\Controllers\Admin\ProfessionalController::class, 'unverify'])->name('professionals.unverify');

    // Content & site settings
    Route::resource('pages', PageController::class)->except(['show']);
    Route::resource('blogs', BlogController::class)->except(['show']);
    Route::resource('menus', \App\Http\Controllers\Admin\MenuController::class)->except(['show']);
    Route::resource('sliders', \App\Http\Controllers\Admin\SliderController::class)->except(['show']);
    Route::get('newsletters', [\App\Http\Controllers\Admin\NewsletterController::class, 'index'])->name('newsletters.index');
    Route::get('newsletters/export', [\App\Http\Controllers\Admin\NewsletterController::class, 'export'])->name('newsletters.export');
    Route::post('newsletters', [\App\Http\Controllers\Admin\NewsletterController::class, 'store'])->name('newsletters.store');
    Route::put('newsletters/{newsletter}', [\App\Http\Controllers\Admin\NewsletterController::class, 'update'])->name('newsletters.update');
    Route::delete('newsletters/{newsletter}', [\App\Http\Controllers\Admin\NewsletterController::class, 'destroy'])->name('newsletters.destroy');
    Route::resource('static-pages', StaticPageController::class)->except(['show']);
    Route::resource('settings', SettingController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('languages', LanguageController::class)->except(['show']);
    Route::resource('currencies', CurrencyController::class)->except(['show']);
    Route::resource('translations', TranslationController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('translations/bulk-import', [TranslationController::class, 'bulkImport'])->name('translations.bulk-import');
    Route::get('seo', [SeoController::class, 'index'])->name('seo.index');
    Route::put('seo', [SeoController::class, 'update'])->name('seo.update');
    Route::get('sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
    Route::get('theme', [ThemeController::class, 'index'])->name('theme.index');
    Route::put('theme', [ThemeController::class, 'update'])->name('theme.update');

    // Selling outside India
    Route::get('international', [\App\Http\Controllers\Admin\InternationalController::class, 'index'])->name('international.index');
    Route::post('international/products', [\App\Http\Controllers\Admin\InternationalController::class, 'products'])->name('international.products');
    Route::put('international/enquiries/{exportEnquiry}', [\App\Http\Controllers\Admin\InternationalController::class, 'enquiry'])->name('international.enquiries.update');

    // Data exports (CSV)
    Route::prefix('exports')->name('exports.')->controller(\App\Http\Controllers\Admin\ExportController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('orders', 'orders')->name('orders');
        Route::get('sales-register', 'salesRegister')->name('sales-register');
        Route::get('credit-notes', 'creditNotes')->name('credit-notes');
        Route::get('sales-by-day', 'salesByDay')->name('sales-by-day');
        Route::get('customers', 'customers')->name('customers');
        Route::get('products', 'products')->name('products');
        Route::get('vendors', 'vendors')->name('vendors');
        Route::get('vendor-orders', 'vendorOrders')->name('vendor-orders');
        Route::get('vendor-payouts', 'vendorPayouts')->name('vendor-payouts');
    });

    // Reports & analytics
    Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('products', [ReportController::class, 'products'])->name('products');
        Route::get('customers', [ReportController::class, 'customers'])->name('customers');
        Route::get('vendors', [ReportController::class, 'vendors'])->name('vendors');
        Route::get('support', [ReportController::class, 'support'])->name('support');
    });

    // Roles & permissions
    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/', [PermissionController::class, 'index'])->name('index');
        Route::get('/roles/create', [PermissionController::class, 'createRole'])->name('create-role');
        Route::post('/roles', [PermissionController::class, 'storeRole'])->name('store-role');
        Route::get('/roles/{role}/edit', [PermissionController::class, 'editRole'])->name('edit-role');
        Route::put('/roles/{role}', [PermissionController::class, 'updateRole'])->name('update-role');
        Route::delete('/roles/{role}', [PermissionController::class, 'destroyRole'])->name('destroy-role');
        Route::get('/permissions/create', [PermissionController::class, 'createPermission'])->name('create-permission');
        Route::post('/permissions', [PermissionController::class, 'storePermission'])->name('store-permission');
        Route::put('/users/{user}/roles', [PermissionController::class, 'updateUserRole'])->name('update-user-role');
        Route::post('/staff', [PermissionController::class, 'storeStaff'])->name('store-staff');
    });

    // Marketplace
    Route::prefix('marketplace')->name('marketplace.')->group(function () {
        Route::get('/', [MarketplaceController::class, 'index'])->name('index');
        Route::post('/toggle', [MarketplaceController::class, 'toggle'])->name('toggle');
        Route::get('/vendors', [MarketplaceController::class, 'vendors'])->name('vendors');
        Route::get('/products', [MarketplaceController::class, 'products'])->name('products');
        Route::get('/commissions', [MarketplaceController::class, 'commissions'])->name('commissions');
        Route::get('/payouts', [MarketplaceController::class, 'payouts'])->name('payouts');
    });
    Route::get('vendors', [AdminVendorController::class, 'index'])->name('vendors.index');
    Route::get('vendors/{vendor}', [AdminVendorController::class, 'show'])->name('vendors.show');
    Route::post('vendors/{vendor}/approve', [AdminVendorController::class, 'approve'])->name('vendors.approve');
    Route::post('vendors/{vendor}/reject', [AdminVendorController::class, 'reject'])->name('vendors.reject');
    Route::post('vendors/{vendor}/under-review', [AdminVendorController::class, 'underReview'])->name('vendors.under-review');
    Route::post('vendors/{vendor}/suspend', [AdminVendorController::class, 'suspend'])->name('vendors.suspend');
    Route::delete('vendors/{vendor}', [AdminVendorController::class, 'destroy'])->name('vendors.destroy');
    // Seller documents: open the private file, then verify or reject it.
    Route::get('vendors/{vendor}/documents/{document}', [AdminVendorController::class, 'document'])->name('vendors.documents.show');
    Route::post('vendors/{vendor}/documents/{document}/verify', [AdminVendorController::class, 'verifyDocument'])->name('vendors.documents.verify');
    Route::post('vendors/{vendor}/documents/{document}/reject', [AdminVendorController::class, 'rejectDocument'])->name('vendors.documents.reject');
    Route::get('vendor-orders', [AdminVendorOrderController::class, 'index'])->name('vendor-orders.index');
    Route::post('vendor-orders/{vendorOrder}/paid', [AdminVendorOrderController::class, 'markPaid'])->name('vendor-orders.markPaid');
    Route::get('vendor-products', [AdminVendorProductController::class, 'index'])->name('vendor-products.index');
    Route::post('vendor-products/{vendorProduct}/approve', [AdminVendorProductController::class, 'approve'])->name('vendor-products.approve');
    Route::post('vendor-products/{vendorProduct}/reject', [AdminVendorProductController::class, 'reject'])->name('vendor-products.reject');
    Route::post('vendor-products/{vendorProduct}/request-changes', [AdminVendorProductController::class, 'requestChanges'])->name('vendor-products.request-changes');
    Route::post('vendor-products/{vendorProduct}/publish', [AdminVendorProductController::class, 'togglePublish'])->name('vendor-products.publish');
    Route::post('vendor-products/{vendorProduct}/featured', [AdminVendorProductController::class, 'toggleFeatured'])->name('vendor-products.featured');
    Route::get('return-requests', [\App\Http\Controllers\Admin\ReturnRequestController::class, 'index'])->name('return-requests.index');
    Route::post('return-requests/{returnRequest}/approve', [\App\Http\Controllers\Admin\ReturnRequestController::class, 'approve'])->name('return-requests.approve');
    Route::post('return-requests/{returnRequest}/reject', [\App\Http\Controllers\Admin\ReturnRequestController::class, 'reject'])->name('return-requests.reject');
    Route::get('vendor-withdrawals', [\App\Http\Controllers\Admin\VendorWithdrawalController::class, 'index'])->name('vendor-withdrawals.index');
    Route::post('vendor-withdrawals/{withdrawal}/approve', [\App\Http\Controllers\Admin\VendorWithdrawalController::class, 'approve'])->name('vendor-withdrawals.approve');
    Route::post('vendor-withdrawals/{withdrawal}/paid', [\App\Http\Controllers\Admin\VendorWithdrawalController::class, 'paid'])->name('vendor-withdrawals.paid');
    Route::post('vendor-withdrawals/{withdrawal}/reject', [\App\Http\Controllers\Admin\VendorWithdrawalController::class, 'reject'])->name('vendor-withdrawals.reject');
    Route::get('vendor-wallets', [\App\Http\Controllers\Admin\VendorWalletController::class, 'index'])->name('vendor-wallets.index');
    Route::get('vendor-wallets/{vendor}', [\App\Http\Controllers\Admin\VendorWalletController::class, 'show'])->name('vendor-wallets.show');
    Route::post('vendor-wallets/{vendor}/adjust', [\App\Http\Controllers\Admin\VendorWalletController::class, 'adjust'])->name('vendor-wallets.adjust');
    Route::get('vendor-payouts', [AdminVendorPayoutController::class, 'index'])->name('vendor-payouts.index');
    Route::post('vendor-payouts', [AdminVendorPayoutController::class, 'store'])->name('vendor-payouts.store');
    Route::post('vendor-payouts/{vendorPayout}/complete', [AdminVendorPayoutController::class, 'complete'])->name('vendor-payouts.complete');
});

// Cart: open to visitors who are not signed in (guest cart) and to customers. Same addresses and names as before.
Route::get('/cart', [CartController::class, 'index'])->middleware('shopper')->name('customer.cart.index');
Route::middleware('shopper')->prefix('customer')->name('customer.')->group(function () {
    Route::post('coupon', [\App\Http\Controllers\Customer\SellerCouponController::class, 'apply'])->middleware('throttle:20,1')->name('coupon.apply');
    Route::delete('coupon', [\App\Http\Controllers\Customer\CouponController::class, 'remove'])->name('coupon.remove');
    Route::post('cart/{product}/{variant?}', [CartController::class, 'store'])->name('cart.store');
    Route::put('cart/{cartItem}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('cart/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');
});

// Guest checkout: delivery details first, which quietly creates the account; payment follows on the normal checkout page.
Route::middleware('guest')->group(function () {
    Route::get('/checkout', [\App\Http\Controllers\GuestCheckoutController::class, 'show'])->name('checkout.guest');
    Route::post('/checkout', [\App\Http\Controllers\GuestCheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.guest.store');

    Route::get('/forgot-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [\App\Http\Controllers\Auth\PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'update'])->middleware('throttle:10,1')->name('password.update');
});

// Customer pages with short addresses of their own
Route::middleware(['auth', 'role:customer'])->name('customer.')->group(function () {
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlists.index');
    // Route::get('/compare', [CompareController::class, 'index'])->name('compare.index');
    Route::get('/track-order', [OrderTrackingController::class, 'index'])->name('order-tracking.index');
});



// Product comparison - Guest + Logged-in users
Route::get('/compare', [CompareController::class, 'index'])
    ->name('customer.compare.index');

Route::post('/compare/{product}', [CompareController::class, 'store'])
    ->name('customer.compare.store');

Route::delete('/compare/{product}', [CompareController::class, 'destroy'])
    ->name('customer.compare.destroy');

Route::delete('/compare', [CompareController::class, 'clear'])
    ->name('customer.compare.clear');
// Customer Routes
Route::middleware(['auth', 'role:customer'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/dashboard', [ProfileController::class, 'dashboard'])->name('dashboard');
    Route::resource('addresses', AddressController::class)->except(['show']);
    Route::post('compare/{product}', [CompareController::class, 'store'])->name('compare.store');
    Route::delete('compare', [CompareController::class, 'clear'])->name('compare.clear');
    Route::delete('compare/{product}', [CompareController::class, 'destroy'])->name('compare.destroy');
    Route::resource('orders', OrderController::class)->only(['index', 'show']);
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('orders/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');
    Route::get('orders/{order}/invoice/pdf', [OrderController::class, 'invoicePdf'])->name('orders.invoice.pdf');
    Route::get('orders/{order}/invoices/{supplierInvoice}', [OrderController::class, 'showInvoice'])->name('orders.invoices.show');
    Route::get('orders/{order}/invoices/{supplierInvoice}/pdf', [OrderController::class, 'showInvoicePdf'])->name('orders.invoices.pdf');
    Route::get('orders/{order}/credit-note', [OrderController::class, 'creditNote'])->name('orders.credit-note');
    Route::get('orders/{order}/credit-note/pdf', [OrderController::class, 'creditNotePdf'])->name('orders.credit-note.pdf');
    Route::get('orders/{order}/credit-notes/{creditNote}', [OrderController::class, 'showCreditNote'])->name('orders.credit-notes.show');
    Route::get('orders/{order}/credit-notes/{creditNote}/pdf', [OrderController::class, 'showCreditNotePdf'])->name('orders.credit-notes.pdf');
    Route::resource('reviews', ReviewController::class)->except(['show']);
    Route::post('wishlists/{product}', [WishlistController::class, 'store'])->name('wishlists.store');
    Route::delete('wishlists/{product}', [WishlistController::class, 'destroy'])->name('wishlists.destroy');
    Route::post('wishlists/{product}/move-to-cart', [WishlistController::class, 'moveToCart'])->name('wishlists.move');
    Route::resource('support-tickets', SupportTicketController::class)->only(['index', 'create', 'store', 'show'])->parameters(['support-tickets' => 'ticket']);
    Route::post('support-tickets/{ticket}/messages', [SupportTicketController::class, 'message'])->name('support-tickets.message');
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('password', [ProfileController::class, 'password'])->middleware('throttle:10,1')->name('password.update');
    Route::get('professional-profile', [ProfessionalProfileController::class, 'edit'])->name('professional-profile.edit');
    Route::get('become-seller', [RegisterController::class, 'showVendorRegistrationForm'])->name('become-seller');
    Route::post('become-seller', [RegisterController::class, 'vendorRegister'])->middleware('throttle:10,1')->name('become-seller.store');
    Route::get('invoices', [\App\Http\Controllers\Customer\InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('returns', [\App\Http\Controllers\Customer\ReturnRequestController::class, 'index'])->name('returns.index');
    Route::get('orders/{order}/return', [\App\Http\Controllers\Customer\ReturnRequestController::class, 'create'])->name('returns.create');
    Route::post('orders/{order}/return', [\App\Http\Controllers\Customer\ReturnRequestController::class, 'store'])->middleware('throttle:10,1')->name('returns.store');
    Route::post('professional-profile', [ProfessionalProfileController::class, 'store'])->name('professional-profile.store');
    Route::get('/products/{product}/gallery', [GalleryController::class, 'index'])->name('products.gallery');
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::prefix('order-tracking')->name('order-tracking.')->group(function () {
        Route::get('/track', [OrderTrackingController::class, 'trackOrder'])->name('track');
    });
});

// A seller who is not approved yet: application status, their own documents, and replacing a rejected file.
Route::middleware(['auth', 'role:vendor'])->prefix('vendor')->name('vendor.')->group(function () {
    Route::get('/application', [\App\Http\Controllers\Vendor\DocumentController::class, 'status'])->name('application');
    Route::get('/documents/{document}', [\App\Http\Controllers\Vendor\DocumentController::class, 'show'])->name('documents.show');
    Route::post('/documents', [\App\Http\Controllers\Vendor\DocumentController::class, 'store'])->middleware('throttle:10,1')->name('documents.store');
});

// Vendor Routes. The owner can do everything; staff only the areas their account allows (vendor.can).
Route::middleware(['auth', 'role:vendor', 'vendor.approved'])->prefix('vendor')->name('vendor.')->group(function () {
    Route::get('/dashboard', [VendorDashboardController::class, 'index'])->name('dashboard');

    Route::middleware('vendor.can:products')->group(function () {
        Route::get('products/import', [\App\Http\Controllers\Vendor\ProductImportController::class, 'create'])->name('products.import');
        Route::get('products/import/template', [\App\Http\Controllers\Vendor\ProductImportController::class, 'template'])->name('products.import.template');
        Route::post('products/import', [\App\Http\Controllers\Vendor\ProductImportController::class, 'store'])->middleware('throttle:10,1')->name('products.import.store');
        Route::resource('products', VendorProductController::class)->except(['show']);
        Route::get('reviews', [\App\Http\Controllers\Vendor\ReviewController::class, 'index'])->name('reviews.index');
        Route::post('reviews/{review}/reply', [\App\Http\Controllers\Vendor\ReviewController::class, 'reply'])->name('reviews.reply');
        Route::resource('coupons', \App\Http\Controllers\Vendor\CouponController::class)->except(['show']);
    });
    Route::middleware('vendor.can:orders')->group(function () {
        Route::get('orders', [VendorOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/export', [VendorOrderController::class, 'export'])->name('orders.export');
        Route::get('orders/{vendorOrder}', [VendorOrderController::class, 'show'])->name('orders.show');
        Route::put('orders/{vendorOrder}/status', [VendorOrderController::class, 'updateStatus'])->name('orders.updateStatus');
        Route::get('orders/{vendorOrder}/invoice', [VendorOrderController::class, 'invoice'])->name('orders.invoice');
        Route::get('orders/{vendorOrder}/invoice/pdf', [VendorOrderController::class, 'invoicePdf'])->name('orders.invoice.pdf');
        Route::get('returns', [\App\Http\Controllers\Vendor\ReturnController::class, 'index'])->name('returns.index');
    });
    Route::middleware('vendor.can:payouts')->group(function () {
        Route::get('payouts', [VendorPayoutController::class, 'index'])->name('payouts.index');
        Route::get('withdrawals', [\App\Http\Controllers\Vendor\WithdrawalController::class, 'index'])->name('withdrawals.index');
        Route::post('withdrawals', [\App\Http\Controllers\Vendor\WithdrawalController::class, 'store'])->middleware('throttle:10,1')->name('withdrawals.store');
        Route::post('withdrawals/{withdrawal}/cancel', [\App\Http\Controllers\Vendor\WithdrawalController::class, 'cancel'])->name('withdrawals.cancel');
        Route::get('wallet', [\App\Http\Controllers\Vendor\WalletController::class, 'index'])->name('wallet.index');
        Route::get('wallet/export', [\App\Http\Controllers\Vendor\WalletController::class, 'export'])->name('wallet.export');
    });
    Route::middleware('vendor.can:owner')->group(function () {
        Route::resource('staff', VendorStaffController::class)->except(['show']);
        Route::get('profile', [VendorProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [VendorProfileController::class, 'update'])->name('profile.update');
    });
});