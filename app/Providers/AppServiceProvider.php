<?php

namespace App\Providers;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Compare;
use App\Models\Wishlist;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        \Illuminate\Support\Facades\Route::group([], base_path('routes/delivery.php'));

        // Whatever a visitor put in the cart before signing in or signing up follows them into their account.
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, fn ($event) => Cart::adoptGuestCart($event->user));

        // @status($value): a status word such as in_progress shown as a translated, human label.
        \Illuminate\Support\Facades\Blade::directive('status', fn ($expression) => "<?php echo e(__(ucfirst(str_replace('_', ' ', {$expression})))); ?>");

        // @price($inr): a browsing-page price in the visitor's chosen display currency (approximate when not INR).
        \Illuminate\Support\Facades\Blade::directive('price', fn ($expression) => "<?php echo e(\App\Support\Money::browse({$expression})); ?>");

        // Header and footer data, computed once per request rather than inside the views.
        View::composer(['components.header', 'components.footer'], function ($view) {
            // Kept on the request itself, so nothing can leak from one request into the next in a long-lived worker.
            $request = request();
            $shared = $request->attributes->get('storefront_nav') ?? (function () {
                $user = auth()->user();

                return [
                    'navCategories' => Category::where('status', true)->whereNull('parent_id')->orderBy('name')->limit(12)->get(['id', 'name', 'slug']),
                    // Guests have a cart too (kept against a token in their session).
                    'cartCount' => (int) (Cart::current()?->items()->sum('quantity') ?? 0),
                    'wishlistCount' => $user ? Wishlist::where('user_id', $user->id)->count() : 0,
                    'compareCount' => $user ? Compare::where('user_id', $user->id)->count() : 0,
                    'headerLinks' => \App\Models\Menu::linksFor('header'),
                    'footerLinks' => \App\Models\Menu::linksFor('footer'),
                ];
            })();

            $request->attributes->set('storefront_nav', $shared);

            $view->with($shared);
        });
    }
}
