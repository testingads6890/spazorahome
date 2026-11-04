<?php

use App\Http\Controllers\DeliveryAreaController;
use Illuminate\Support\Facades\Route;

// The shopper's delivery PIN (kept in the session). Lives in its own file so the main routes file stays untouched.
Route::middleware('web')->group(function () {
    Route::post('/delivery-area', [DeliveryAreaController::class, 'store'])->middleware('throttle:30,1')->name('delivery-area.store');
    Route::post('/delivery-area/clear', [DeliveryAreaController::class, 'clear'])->name('delivery-area.clear');
    Route::get('/search/suggest', \App\Http\Controllers\SearchSuggestController::class)->middleware('throttle:60,1')->name('search.suggest');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/product-units', [\App\Http\Controllers\ProductUnitController::class, 'store'])->middleware('throttle:30,1')->name('product-units.store');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/product/{product:slug}/review', [\App\Http\Controllers\ProductReviewController::class, 'store'])->middleware('throttle:10,1')->name('products.review.store');
});

// Bulk enquiry: public form, and the admin list.
Route::middleware('web')->group(function () {
    Route::get('/bulk-enquiry', [\App\Http\Controllers\BulkEnquiryController::class, 'create'])->name('bulk-enquiry.create');
    Route::post('/bulk-enquiry', [\App\Http\Controllers\BulkEnquiryController::class, 'store'])->middleware('throttle:5,1')->name('bulk-enquiry.store');
});
Route::middleware(['web', 'auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('bulk-enquiries', [\App\Http\Controllers\Admin\BulkEnquiryController::class, 'index'])->name('bulk-enquiries.index');
    Route::get('bulk-enquiries/{enquiry}', [\App\Http\Controllers\Admin\BulkEnquiryController::class, 'show'])->name('bulk-enquiries.show');
    Route::put('bulk-enquiries/{enquiry}', [\App\Http\Controllers\Admin\BulkEnquiryController::class, 'update'])->name('bulk-enquiries.update');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/product-types', [\App\Http\Controllers\ProductTypeController::class, 'store'])->middleware('throttle:30,1')->name('product-types.store');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/subcategories', [\App\Http\Controllers\SubcategoryController::class, 'store'])->middleware('throttle:30,1')->name('subcategories.store');
});
