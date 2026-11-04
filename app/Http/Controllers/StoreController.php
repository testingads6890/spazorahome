<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Http\Request;

/** Public marketplace pages: the list of sellers and each seller's storefront. */
class StoreController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:100', 'sort' => 'nullable|in:name,products,newest']);

        $stores = Vendor::live()
            ->withCount(['products as products_count' => fn ($q) => $q->where('is_active', true)->where('vendor_status', 'approved')])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('business_name', 'like', '%' . $request->q . '%')->orWhere('city', 'like', '%' . $request->q . '%')))
            ->when($request->sort === 'products', fn ($q) => $q->orderByDesc('products_count'))
            ->when($request->sort === 'newest', fn ($q) => $q->latest())
            ->when(! in_array($request->sort, ['products', 'newest'], true), fn ($q) => $q->orderBy('business_name'))
            ->paginate(12)->withQueryString();

        abort_unless(\App\Support\Stores::enabled(), 404);

        return view('stores.index', compact('stores'));
    }

    public function show(Vendor $vendor, Request $request)
    {
        abort_unless(\App\Support\Stores::enabled(), 404);

        abort_unless($vendor->status === 'approved' && $vendor->is_active, 404);

        $request->validate(['sort' => 'nullable|in:newest,price_asc,price_desc,name_asc']);

        $products = Product::where('vendor_id', $vendor->id)->where('is_active', true)->where('vendor_status', 'approved')
            ->with(['images', 'category:id,name'])
            ->withCount(['variants', 'reviews as reviews_count' => fn ($q) => $q->where('is_approved', true)])
            ->withAvg(['reviews as reviews_avg_rating' => fn ($q) => $q->where('is_approved', true)], 'rating');

        match ($request->sort) {
            'price_asc' => $products->orderBy('price'),
            'price_desc' => $products->orderByDesc('price'),
            'name_asc' => $products->orderBy('name'),
            default => $products->latest(),
        };

        $products = $products->paginate(12)->withQueryString();
        $total = Product::where('vendor_id', $vendor->id)->where('is_active', true)->where('vendor_status', 'approved')->count();

        return view('stores.show', compact('vendor', 'products', 'total'));
    }
}
