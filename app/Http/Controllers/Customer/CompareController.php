<?php
namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Compare;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CompareController extends Controller
{
    private const SESSION_KEY = 'guest_compare_product_ids';
    private const MAX_COMPARE = 4;

    public function index()
    {
        if (auth()->check()) {
            $this->mergeGuestComparisons();

            $compares = Compare::with('product.images')
                ->where('user_id', auth()->id())
                ->whereHas('product')
                ->get();
        } else {
            $compares = $this->getGuestComparisons();
        }

        return view('customer.compare.index', compact('compares'));
    }

    public function store(Request $request, Product $product)
    {
        if (auth()->check()) {

            $this->mergeGuestComparisons();

            $query = Compare::where('user_id', auth()->id());

            if ($query->where('product_id', $product->id)->exists()) {
                return back()->with('info', 'Product already added to compare.');
            }

            if (Compare::where('user_id', auth()->id())->count() >= self::MAX_COMPARE) {
                return back()->with('error', 'You can compare maximum 4 products.');
            }

            Compare::create([
                'user_id' => auth()->id(),
                'product_id' => $product->id,
            ]);

        } else {

            $ids = collect(session(self::SESSION_KEY, []))
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values();

            if ($ids->contains((int) $product->id)) {
                return back()->with('info', 'Product already added to compare.');
            }

            if ($ids->count() >= self::MAX_COMPARE) {
                return back()->with('error', 'You can compare maximum 4 products.');
            }

            $ids->push((int) $product->id);

            session([self::SESSION_KEY => $ids->all()]);
        }

        return back()->with('success', 'Product added to compare list.');
    }

    public function destroy(Product $product)
    {
        if (auth()->check()) {

            $this->mergeGuestComparisons();

            Compare::where('user_id', auth()->id())
                ->where('product_id', $product->id)
                ->delete();

        } else {

            $ids = collect(session(self::SESSION_KEY, []))
                ->map(fn($id) => (int) $id)
                ->reject(fn($id) => $id === (int) $product->id)
                ->values()
                ->all();

            session([self::SESSION_KEY => $ids]);
        }

        return back()->with('success', 'Product removed from compare list.');
    }

    public function clear()
    {
        if (auth()->check()) {

            $this->mergeGuestComparisons();

            Compare::where('user_id', auth()->id())->delete();

        } else {

            session()->forget(self::SESSION_KEY);
        }

        return back()->with('success', 'Compare list cleared.');
    }

    /**
     * Get guest products in the same format as existing Blade view.
     */
    private function getGuestComparisons(): Collection
    {
        $ids = collect(session(self::SESSION_KEY, []))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->take(self::MAX_COMPARE)
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $products = Product::with('images')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return $ids->map(function ($id) use ($products) {

            $product = $products->get($id);

            if (!$product) {
                return null;
            }

            $compare = new Compare();

            $compare->product_id = $product->id;

            $compare->setRelation('product', $product);

            return $compare;

        })->filter()->values();
    }

    /**
     * Merge guest comparison products into customer account.
     */
    private function mergeGuestComparisons(): void
    {
        if (!auth()->check()) {
            return;
        }

        $guestIds = collect(session(self::SESSION_KEY, []))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($guestIds->isEmpty()) {
            return;
        }

        $userId = auth()->id();

        $existingIds = Compare::where('user_id', $userId)
            ->orderBy('id')
            ->pluck('product_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        foreach ($guestIds as $productId) {

            if ($existingIds->count() >= self::MAX_COMPARE) {
                break;
            }

            if ($existingIds->contains($productId)) {
                continue;
            }

            if (!Product::whereKey($productId)->exists()) {
                continue;
            }

            Compare::create([
                'user_id' => $userId,
                'product_id' => $productId,
            ]);

            $existingIds->push($productId);
        }

        session()->forget(self::SESSION_KEY);
    }
}
