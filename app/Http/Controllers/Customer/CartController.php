<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $cart = Cart::current();
        $cartItems = $cart ? $cart->items()->with(['product.images', 'variant'])->get() : collect();

        $coupons = app(\App\Services\CouponService::class);
        $coupon = $coupons->apply(session(\App\Services\CouponService::SESSION_KEY), $coupons->linesFor($cartItems));
        if ($coupon['error']) {
            session()->forget(\App\Services\CouponService::SESSION_KEY);
            session()->now('coupon_error', $coupon['error']);
        }

        return view('customer.cart.index', compact('cart', 'cartItems', 'coupon'));
    }

    public function store(Request $request, Product $product, ?ProductVariant $variant = null)
    {
        $data = $request->validate(['quantity' => 'nullable|integer|min:1|max:100000', 'buy_now' => 'nullable|boolean']);
        $quantity = $data['quantity'] ?? 1;
        $minimum = max(1, (int) $product->min_order_qty);

        abort_unless($product->is_active && ($product->vendor_id === null || $product->vendor_status === 'approved'), 404);
        abort_if($variant && $variant->product_id !== $product->id, 404);

        // Products with a dispatch location are only added when they can be delivered to the shopper's PIN.
        if (! \App\Support\DeliveryArea::canDeliver($product, \App\Support\DeliveryArea::current())) {
            return back()->with('error', __('shop.messages.not_deliverable_here'));
        }

        // Products with a dispatch location are only added when they can be delivered to the shopper's PIN.
        if (! \App\Support\DeliveryArea::canDeliver($product, \App\Support\DeliveryArea::current())) {
            return back()->with('error', __('shop.messages.not_deliverable_here'));
        }

        $cart = Cart::current(create: true);

        $item = $cart->items()->firstOrNew(['product_id' => $product->id, 'variant_id' => $variant?->id]);
        $item->setRelation('product', $product)->setRelation('variant', $variant);

        // Buy now replaces the quantity already in the cart instead of adding to it.
        $newQuantity = $request->boolean('buy_now') ? $quantity : ($item->exists ? $item->quantity : 0) + $quantity;
        // Products sold only in sets: never fewer than the minimum order quantity.
        $raised = $newQuantity < $minimum;
        $newQuantity = max($newQuantity, $minimum);
        if ($newQuantity > $item->availableStock()) {
            return back()->with('error', __('shop.messages.cart_only_stock', ['count' => $item->availableStock()]));
        }

        $item->quantity = $newQuantity;
        $item->unit_price = $item->currentUnitPrice();
        $item->save();

        if ($request->boolean('buy_now')) {
            // Straight to paying: the customer's checkout, or the guest checkout for visitors.
            return auth()->check() ? redirect()->route('customer.checkout.index') : redirect()->route('checkout.guest');
        }

        return back()->with('success', $raised ? __('shop.messages.cart_min_raised', ['count' => $minimum]) : __('shop.messages.cart_added'));
    }

    public function update(Request $request, int $cartItem)
    {
        $data = $request->validate(['quantity' => 'required|integer|min:1|max:100000']);
        $item = $this->ownedItem($cartItem);

        $minimum = max(1, (int) $item->product?->min_order_qty);
        if ($data['quantity'] < $minimum) {
            return back()->with('error', __('shop.messages.cart_min_qty', ['count' => $minimum]));
        }

        if ($data['quantity'] > $item->availableStock()) {
            return back()->with('error', __('shop.messages.cart_only_stock', ['count' => $item->availableStock()]));
        }

        // Quantity first: the unit price depends on it (bulk discount).
        $item->quantity = $data['quantity'];
        $item->unit_price = $item->currentUnitPrice();
        $item->save();

        return back()->with('success', __('shop.messages.cart_updated'));
    }

    public function destroy(int $cartItem)
    {
        $this->ownedItem($cartItem)->delete();

        return back()->with('success', __('shop.messages.cart_removed'));
    }

    /** Only items in this visitor's own active cart (the customer's, or the guest cart of this session). */
    private function ownedItem(int $id): CartItem
    {
        $cart = Cart::current();
        abort_unless($cart, 404);

        return CartItem::where('cart_id', $cart->id)
            ->with(['product', 'variant'])
            ->findOrFail($id);
    }
}
