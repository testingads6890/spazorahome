<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // The display-currency choice is not sensitive, so it is stored as a plain, readable cookie.
        $middleware->encryptCookies(except: [\App\Support\Money::COOKIE, \App\Support\Locales::COOKIE]);
        $middleware->web(append: [\App\Http\Middleware\SetLocale::class, \App\Http\Middleware\CompressResponse::class]);

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'vendor.approved' => \App\Http\Middleware\VendorApproved::class,
            'vendor.can' => \App\Http\Middleware\VendorCan::class,
            'shopper' => \App\Http\Middleware\Shopper::class,
            'admin.area' => \App\Http\Middleware\AdminArea::class,
        ]);
    })
    ->booting(function (Application $app) {
        // Seller coupons ride on the store's coupon service (only when that class can be extended).
        if (class_exists(\App\Services\CouponService::class) && ! (new \ReflectionClass(\App\Services\CouponService::class))->isFinal()) {
            $app->bind(\App\Services\CouponService::class, \App\Services\MarketplaceCouponService::class);
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
