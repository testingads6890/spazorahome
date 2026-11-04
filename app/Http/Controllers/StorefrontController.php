<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Deal;
use App\Models\Product;
use Illuminate\Http\Request;

class StorefrontController extends Controller
{
    public function home()
    {
        $categories = Category::where('status', true)->whereNull('parent_id')->orderBy('name')->limit(12)
            ->withCount(['products as products_count' => fn ($q) => $q->where('is_active', true)->where(fn ($w) => $w->whereNull('vendor_id')->orWhere('vendor_status', 'approved'))])
            ->get();
        // Eight cards a row keeps the page light on a phone; every row links to the full list.
        $featuredProducts = $this->cards($this->visibleProducts()->where('is_featured', true)->latest())->limit(8)->get();
        $newArrivals = $this->cards($this->visibleProducts()->latest())->limit(8)->get();
        $bestSellers = $this->cards($this->visibleProducts()->where('purchase_count', '>', 0)->orderByDesc('purchase_count'))->limit(8)->get();
        $onSale = $this->cards($this->visibleProducts()->whereNotNull('sale_price')->whereColumn('sale_price', '<', 'price')
            ->orderByRaw('(price - sale_price) / price desc'))->limit(8)->get();
        // "Light, layered": lighting and decorative pieces, when the shop has those categories.
        $moodIds = $categories->filter(fn ($c) => in_array($c->urlSlug(), ['lighting', 'decoration', 'textiles-rugs'], true))->pluck('id');
        $moodProducts = $moodIds->isEmpty() ? collect()
            : $this->cards($this->visibleProducts()->whereIn('category_id', $moodIds)->orderByDesc('is_featured')->orderByDesc('purchase_count'))->limit(8)->get();
        $ideas = \App\Models\RoomIdea::where('is_active', true)->latest()->limit(4)->get();
        $deal = Deal::with('product.images')->where('is_active', true)
            ->where('starts_at', '<=', now())->where('ends_at', '>', now())->orderBy('ends_at')->first();
        $stats = ['products' => $this->visibleProducts()->count(), 'categories' => Category::where('status', true)->count()];

        $slider = \App\Models\Slider::forLocation('home');

        return view('welcome', compact('categories', 'featuredProducts', 'newArrivals', 'bestSellers', 'onSale', 'moodProducts', 'ideas', 'deal', 'stats', 'slider'));
    }

    /**
     * The shop listing: /shop for everything, /shop/living-room for one category.
     * Old addresses (/search, ?category=4, /shop/4) are sent on to the readable one for good.
     */
    public function search(Request $request, ?string $category = null)
    {
        $request->validate([
            'q' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:150',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'sort' => 'nullable|in:price_asc,price_desc,newest,name_asc,rating',
            'view' => 'nullable|in:grid,list',
            'brand' => 'nullable|string|max:100', 'material' => 'nullable|string|max:100', 'color' => 'nullable|string|max:100',
            'size' => 'nullable|string|max:100', 'style' => 'nullable|string|max:100', 'type' => 'nullable|string|max:100',
            'seller' => 'nullable|string|max:150',
            'in_stock' => 'nullable|boolean', 'featured' => 'nullable|boolean', 'new' => 'nullable|boolean', 'on_sale' => 'nullable|boolean',
        ]);

        $wanted = $request->query('category') ?: $category;
        $activeCategory = filled($wanted) ? Category::fromUrl((string) $wanted) : null;
        abort_if(filled($category) && ! $request->filled('category') && ! $activeCategory, 404);

        $canonical = route('search', array_filter(['category' => $activeCategory?->urlSlug()]) + $request->except('category'));
        if ($request->has('category') || $request->is('search') || (filled($category) && $category !== $activeCategory?->urlSlug())) {
            return redirect()->to($canonical, 301);
        }

        $query = $this->cards($this->visibleProducts());

        if ($q = $request->query('q')) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('short_description', 'like', "%{$q}%")
                  ->orWhere('sku', 'like', "%{$q}%")
                  ->orWhere('brand', 'like', "%{$q}%")
                  ->orWhere('tags', 'like', "%{$q}%");
            });
        }
        // A category includes its subcategories.
        $categoryIds = $activeCategory ? Category::where('status', true)->where('parent_id', $activeCategory->id)->pluck('id')->push($activeCategory->id)->all() : [];
        if ($activeCategory) {
            // A product counts under every category and subcategory it was saved with, not only its main one.
            $query->where(fn ($q) => $q->whereIn('category_id', $categoryIds)->orWhereIn('id', self::productIdsIn($categoryIds)));
        }
        // Attribute filters; "type" is the product type (kept short in the address).
        foreach (['brand' => 'brand', 'material' => 'material', 'color' => 'color', 'size' => 'size', 'style' => 'style', 'type' => 'product_type'] as $param => $column) {
            if ($request->filled($param)) {
                $query->where($column, $request->input($param));
            }
        }
        if ($request->filled('seller')) {
            $query->whereHas('vendor', fn ($v) => $v->where('slug', $request->input('seller')));
        }
        if ($request->boolean('in_stock')) {
            $query->where('stock_quantity', '>', 0);
        }
        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }
        if ($request->boolean('new')) {
            $query->where(fn ($w) => $w->where('is_new_arrival', true)->orWhere('created_at', '>=', now()->subDays(30)));
        }
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }
        if ($request->boolean('on_sale')) {
            $query->whereNotNull('sale_price')->whereColumn('sale_price', '<', 'price');
        }

        match ($request->sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'name_asc' => $query->orderBy('name'),
            'rating' => $query->orderByDesc('reviews_avg_rating'),
            'newest' => $query->latest(),
            default => $query->orderByDesc('is_featured')->latest(),
        };

        $products = $query->paginate(24)->withQueryString();

        $all = Category::where('status', true)->orderBy('name')->get();
        $categories = $all->whereNull('parent_id')->values();
        // Subcategories of the category being viewed (or of its parent, when a subcategory is open).
        $parentId = $activeCategory ? ($activeCategory->parent_id ?: $activeCategory->id) : null;
        $subcategories = $parentId ? $all->where('parent_id', $parentId)->values() : collect();
        $parentCategory = $activeCategory?->parent_id ? $all->firstWhere('id', $activeCategory->parent_id) : null;

        // Filter choices come from the products in view, so every option leads somewhere.
        $brands = $this->distinctValues('brand', $categoryIds);
        $materials = $this->distinctValues('material', $categoryIds);
        $colors = $this->distinctValues('color', $categoryIds);
        $sizes = $this->distinctValues('size', $categoryIds);
        $styles = $this->distinctValues('style', $categoryIds);
        $types = $this->distinctValues('product_type', $categoryIds);
        $sellers = \App\Models\Vendor::live()->whereHas('products', fn ($p) => $p->where('is_active', true)->where('vendor_status', 'approved'))
            ->orderBy('business_name')->get(['id', 'business_name', 'slug']);

        return view('search', compact('products', 'categories', 'subcategories', 'parentCategory', 'brands', 'materials', 'colors', 'sizes', 'styles', 'types', 'sellers', 'activeCategory'));
    }

    /** Old address /products/category/{slug}: sent on to /shop/{slug}. */
    public function category(Request $request, string $slug)
    {
        if ($slug === 'all') {
            return redirect()->route('search', $request->query(), 301);
        }

        $category = Category::fromUrl($slug) ?? abort(404);

        return redirect()->to($category->shopUrl($request->query()), 301);
    }

    /** Old address /products/123: sent on to /product/{slug}. */
    public function legacyProduct(Request $request, int $id)
    {
        $product = Product::findOrFail($id);
        abort_unless($this->isVisible($product), 404);

        return redirect()->to(route('products.show', $product) . ($request->getQueryString() ? '?' . $request->getQueryString() : ''), 301);
    }

    public function show(Product $product, Request $request)
    {
        abort_unless($this->isVisible($product), 404);

        $product->increment('view_count');
        $product->load(['category', 'images', 'variants', 'vendor:id,business_name,slug,city,state']);

        $reviews = $product->reviews()->where('is_approved', true)->with('user:id,name')->latest()->limit(20)->get();
        $ratingCounts = $product->reviews()->where('is_approved', true)->selectRaw('rating, count(*) as c')->groupBy('rating')->pluck('c', 'rating');
        $reviewCount = (int) $ratingCounts->sum();
        $rating = $reviewCount ? round($ratingCounts->map(fn ($c, $r) => $c * $r)->sum() / $reviewCount, 1) : 0.0;

        $related = $this->cards($this->visibleProducts()->where('category_id', $product->category_id)->where('id', '!=', $product->id)->inRandomOrder())->limit(4)->get();

        // Recently viewed, most recent first, kept in the session (never more than 8, never the current product).
        $seen = collect($request->session()->get('recently_viewed', []))->reject(fn ($id) => $id === $product->id);
        $recent = $seen->isEmpty() ? collect() : $this->cards($this->visibleProducts()->whereIn('id', $seen->take(4)))->get()->sortBy(fn ($p) => $seen->search($p->id))->values();
        $request->session()->put('recently_viewed', $seen->prepend($product->id)->take(8)->values()->all());

        $canReview = auth()->check() && $product->orderItems()->whereHas('order', fn ($q) => $q->where('user_id', auth()->id()))->exists();

        return view('products.show', compact('product', 'reviews', 'ratingCounts', 'reviewCount', 'rating', 'related', 'recent', 'canReview'));
    }

    /** Bare HTML fragment for the quick-view modal. */
    public function quick(Product $product)
    {
        abort_unless($this->isVisible($product), 404);

        $product->load(['category', 'images', 'variants']);
        $reviews = $product->reviews()->where('is_approved', true);
        $reviewCount = $reviews->count();
        $rating = $reviewCount ? round((float) $reviews->avg('rating'), 1) : 0.0;

        return view('products._quick', compact('product', 'reviewCount', 'rating'));
    }

    /** Everything the product card shows, loaded in a fixed number of queries. */
    private function cards($query)
    {
        return $query->with(['images', 'category:id,name'])
            ->withCount(['variants', 'reviews as reviews_count' => fn ($q) => $q->where('is_approved', true)])
            ->withAvg(['reviews as reviews_avg_rating' => fn ($q) => $q->where('is_approved', true)], 'rating');
    }

    /** Active products; vendor products only once approved. */
    private function visibleProducts()
    {
        return Product::where('is_active', true)->where(function ($q) {
            $q->whereNull('vendor_id')->orWhere('vendor_status', 'approved');
        });
    }

    private function isVisible(Product $product): bool
    {
        return $product->is_active
            && ($product->vendor_id === null || $product->vendor_status === 'approved');
    }

    /** Products saved under any of these categories (the multi-category links). */
    private static function productIdsIn(array $categoryIds)
    {
        if (! $categoryIds || ! \Illuminate\Support\Facades\Schema::hasTable('category_product')) {
            return [];
        }

        return \Illuminate\Support\Facades\DB::table('category_product')->whereIn('category_id', $categoryIds)->pluck('product_id')->all();
    }

    private function distinctValues(string $column, array $categoryIds = [])
    {
        return $this->visibleProducts()->when($categoryIds, fn ($q) => $q->where(fn ($w) => $w->whereIn('category_id', $categoryIds)->orWhereIn('id', self::productIdsIn($categoryIds))))
            ->whereNotNull($column)->where($column, '!=', '')
            ->distinct()->orderBy($column)->pluck($column);
    }
}
