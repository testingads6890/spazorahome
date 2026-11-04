<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index()
    {
        $wishlists = auth()->user()->wishlists()->with(['product.images', 'product.category:id,name'])->latest()->paginate(12);
        return view('customer.wishlist.index', compact('wishlists'));
    }

    public function store(Request $request, Product $product)
    {
        Wishlist::firstOrCreate(['user_id' => auth()->id(), 'product_id' => $product->id]);
        return back()->with('success', __('Added to wishlist.'));
    }

    /** Move a saved product into the cart. Products with options go to their page so the customer can pick one. */
    public function moveToCart(Product $product)
    {
        abort_unless($product->is_active && ($product->vendor_id === null || $product->vendor_status === 'approved'), 404);

        if ($product->variants()->exists()) {
            return redirect()->route('products.show', $product)->with('success', __('shop.messages.choose_options_first'));
        }

        $cart = \App\Models\Cart::current(create: true);
        $item = $cart->items()->firstOrNew(['product_id' => $product->id, 'variant_id' => null]);
        $item->setRelation('product', $product)->setRelation('variant', null);
        $quantity = max(($item->exists ? $item->quantity : 0) + 1, (int) $product->min_order_qty, 1);
        if ($quantity > $item->availableStock()) {
            return back()->with('error', __('shop.messages.cart_only_stock', ['count' => $item->availableStock()]));
        }
        $item->quantity = $quantity;
        $item->unit_price = $item->currentUnitPrice();
        $item->save();

        Wishlist::where('user_id', auth()->id())->where('product_id', $product->id)->delete();

        return back()->with('success', __('shop.messages.moved_to_cart'));
    }

    public function destroy(Product $product)
    {
        Wishlist::where('user_id', auth()->id())->where('product_id', $product->id)->delete();
        return back()->with('success', __('Removed from wishlist.'));
    }
}
