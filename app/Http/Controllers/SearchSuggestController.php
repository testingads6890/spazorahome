<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Live suggestions while a shopper types in the search box: a few product names and category names.
 * Only what the storefront shows (live products from approved sellers) is ever suggested.
 */
class SearchSuggestController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));
        if (mb_strlen($term) < 2) {
            return response()->json(['products' => [], 'categories' => []]);
        }
        $term = mb_substr($term, 0, 60);

        $products = Product::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('vendor_id')->orWhere('vendor_status', 'approved'))
            ->where('name', 'like', "%{$term}%")
            ->orderBy('name')->limit(6)
            ->get(['id', 'name', 'slug'])
            ->map(fn (Product $p) => ['name' => $p->name, 'url' => route('products.show', $p->slug)]);

        $categories = Category::where('status', true)
            ->where('name', 'like', "%{$term}%")
            ->orderBy('name')->limit(3)
            ->get()
            ->map(fn (Category $c) => ['name' => $c->name, 'url' => route('search', ['category' => $c->urlSlug()])]);

        return response()->json(['products' => $products->values(), 'categories' => $categories->values()])
            ->header('Cache-Control', 'private, max-age=60');
    }
}
