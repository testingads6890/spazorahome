<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\Address;
use App\Services\CouponService;
use Illuminate\Validation\ValidationException;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Models\VendorOrder;
use App\Models\VendorProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    public function index()
    {
        $cart = Cart::where('user_id', auth()->id())->where('status', 'active')->first();
        $cartItems = $cart ? $cart->items()->with(['product.images', 'variant'])->get() : collect();

        if ($cartItems->isEmpty()) {
            return redirect()->route('customer.cart.index')->with('error', __('shop.messages.cart_empty'));
        }

        $addresses = auth()->user()->addresses()->get();

        $lines = $cartItems->values()->map(fn ($i) => ['product_id' => $i->product_id, 'category_id' => $i->product->category_id, 'total' => round($i->currentUnitPrice() * $i->quantity, 2)])->all();
        $coupon = app(CouponService::class)->apply(session(CouponService::SESSION_KEY), $lines);
        if ($coupon['error']) {
            session()->forget(CouponService::SESSION_KEY);
            session()->now('error', $coupon['error']);
        }
        $taxLines = $cartItems->values()->map(fn ($i, $n) => [$lines[$n]['total'] - $coupon['allocations'][$n], $i->product->taxRate()])->all();

        // Shipping depends on the chosen address and delivery option, so quote every combination for the page to switch between.
        // Without delivery options set up in admin there is a single quote per address, under key 0.
        $methods = ShippingMethod::availableFor(round(array_sum(array_column($taxLines, 0)), 2));
        $options = $methods->isEmpty() ? collect([0 => null]) : $methods->keyBy('id');
        $weight = $this->packedWeight($cartItems);
        // Abroad only courier options apply (see deliveryOptions); key 0 is the plain zone rate when none of them is a courier option.
        $quotes = $addresses->mapWithKeys(fn ($a) => [$a->id => $options->map(fn ($m) => $this->totals($taxLines, $a, $coupon['discount'], $m, $this->exportProblems($cartItems, $a), $weight))
            ->when($a->isForeign(), fn ($q) => $q->put(0, $this->totals($taxLines, $a, $coupon['discount'], null, $this->exportProblems($cartItems, $a), $weight)))]);
        $default = $addresses->firstWhere('is_default', true) ?? $addresses->first();
        $totals = $this->totals($taxLines, $default, $coupon['discount'], $this->deliveryOptions($methods, (bool) $default?->isForeign())->first(), $this->exportProblems($cartItems, $default), $weight);
        $couponCode = $coupon['coupon']?->code;

        $razorpayEnabled = filled(config('services.razorpay.key')) && filled(config('services.razorpay.secret'));

        return view('customer.checkout.index', compact('cart', 'cartItems', 'addresses', 'totals', 'quotes', 'default', 'razorpayEnabled', 'couponCode', 'methods'));
    }

    public function store(Request $request)
    {
        $ownAddress = Rule::exists('addresses', 'id')->where('user_id', auth()->id());
        $data = $request->validate([
            'shipping_address_id' => ['required', $ownAddress],
            'billing_address_id' => ['required', $ownAddress],
            'shipping_method_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:1000',
            'payment_method' => ['required', Rule::in(filled(config('services.razorpay.key')) && filled(config('services.razorpay.secret')) ? ['cod', 'razorpay'] : ['cod'])],
        ]);

        $order = DB::transaction(function () use ($data) {
            $cart = Cart::where('user_id', auth()->id())->where('status', 'active')->lockForUpdate()->first();
            $items = $cart ? $cart->items()->with(['product', 'variant'])->get() : collect();
            abort_if($items->isEmpty(), 422, __('shop.messages.cart_empty'));

            // Delivery PIN: the shipping address's PIN, or the PIN the shopper gave for this visit.
            $shipTo = Address::where('user_id', auth()->id())->find($data['shipping_address_id']);
            $area = \App\Support\DeliveryArea::forPin($shipTo?->postal_code) ?? \App\Support\DeliveryArea::current();
            foreach ($items as $item) {
                if (! \App\Support\DeliveryArea::canDeliver($item->product, $area)) {
                    throw ValidationException::withMessages(['shipping_address_id' => __('shop.messages.not_deliverable_at_checkout', ['product' => $item->product->name])]);
                }
            }

            $subtotal = 0;
            $lines = [];
            $couponLines = [];
            foreach ($items as $item) {
                // Lock the stock row so concurrent orders cannot oversell.
                $stockRow = $item->variant_id
                    ? ProductVariant::lockForUpdate()->find($item->variant_id)
                    : Product::lockForUpdate()->find($item->product_id);

                abort_if(! $stockRow || ! $item->product->is_active, 422, "{$item->product->name} is no longer available.");
                abort_if($item->quantity > $stockRow->stock_quantity, 422, "Not enough stock for {$item->product->name}.");

                $unitPrice = $item->product->bulkUnitPrice((float) ($stockRow->sale_price ?? $stockRow->price), (int) $item->quantity);
                $lineTotal = round($unitPrice * $item->quantity, 2);
                $subtotal += $lineTotal;
                $lines[] = [$item, $stockRow, $unitPrice, $lineTotal];
                $couponLines[] = ['product_id' => $item->product_id, 'category_id' => $item->product->category_id, 'total' => $lineTotal];
            }

            // Re-check the coupon under a row lock so a limited coupon cannot be over-used by parallel checkouts.
            $coupon = app(CouponService::class)->apply(session(CouponService::SESSION_KEY), $couponLines, lock: true);
            if ($coupon['error']) {
                session()->forget(CouponService::SESSION_KEY);
                throw ValidationException::withMessages(['coupon' => $coupon['error']]);
            }
            $shippingAddress = Address::where('user_id', auth()->id())->find($data['shipping_address_id']);
            // Delivered outside India: a zero-rated export, so no GST on the goods or the shipping.
            $export = (bool) $shippingAddress?->isForeign();
            $rateFor = fn ($item) => $export ? 0.0 : $item->product->taxRate();

            $taxLines = [];
            foreach ($lines as $n => [$item, , , $lineTotal]) {
                $taxLines[] = [$lineTotal - $coupon['allocations'][$n], $rateFor($item)];
            }

            // The delivery option is re-read here: the customer may only pick one that is on offer for this order value.
            $methods = $this->deliveryOptions(ShippingMethod::availableFor(round(array_sum(array_column($taxLines, 0)), 2)), $export);
            $method = $methods->firstWhere('id', (int) ($data['shipping_method_id'] ?? 0));
            if ($methods->isNotEmpty() && ! $method) {
                throw ValidationException::withMessages(['shipping_method_id' => __('shop.checkout.choose_delivery')]);
            }

            $weight = $this->packedWeight($items);
            $quote = $this->totals($taxLines, $shippingAddress, $coupon['discount'], $method, $this->exportProblems($items, $shippingAddress), $weight);
            ['tax' => $tax, 'shipping' => $shipping, 'shipping_tax' => $shippingTax, 'total' => $total, 'charge' => $charge] = $quote;
            if ($quote['blocked']) {
                throw ValidationException::withMessages(['shipping_address_id' => implode(' ', $quote['blocked'])]);
            }
            if (! $quote['deliverable']) {
                throw ValidationException::withMessages(['shipping_address_id' => __('shop.checkout.no_delivery', ['country' => $shippingAddress->country])]);
            }
            if ($export && $data['payment_method'] !== 'razorpay') {
                throw ValidationException::withMessages(['payment_method' => __('shop.checkout.export_online_only')]);
            }

            $order = Order::create([
                'user_id' => auth()->id(),
                'order_number' => 'ORD-' . strtoupper(Str::random(10)),
                'status' => 'pending',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping_tax' => $shippingTax,
                'shipping' => $shipping,
                'shipping_method' => $method?->name,
                'discount' => $coupon['discount'],
                'coupon_code' => $coupon['coupon']?->code,
                'total' => $total,
                'currency' => 'INR',
                'is_export' => $export,
                'charge_currency' => $charge['currency'] ?? null,
                'charge_total' => $charge['amount'] ?? null,
                'exchange_rate' => $charge['rate'] ?? null,
                'export_weight_kg' => $export && $weight > 0 ? $weight : null,
                'billing_address_id' => $data['billing_address_id'],
                'shipping_address_id' => $data['shipping_address_id'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lines as $n => [$item, $stockRow, $unitPrice, $lineTotal]) {
                $vendorId = $item->product->vendor_id;
                $discountShare = $coupon['allocations'][$n];
                $net = round($lineTotal - $discountShare, 2);
                $commission = $vendorId ? $this->commissionFor($item->product, $net) : null;

                $orderItem = $order->items()->create([
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'vendor_id' => $vendorId,
                    'product_name' => $item->product->name,
                    'product_sku' => $item->variant?->sku ?? $item->product->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $lineTotal,
                    'hsn_code' => $item->product->hsn_code,
                    'tax_rate' => $rateFor($item),
                    'discount_amount' => $discountShare,
                    'tax_amount' => round($net * $rateFor($item) / 100, 2),
                    'vendor_subtotal' => $vendorId ? $net - $commission : null,
                    'vendor_commission' => $commission,
                ]);

                if ($vendorId) {
                    // As an e-commerce operator the marketplace holds back TCS from what it pays the vendor.
                    $tcs = config('shop.invoice_mode') === 'per_supplier' ? round($net * (float) config('shop.tcs_rate') / 100, 2) : 0.0;
                    VendorOrder::create([
                        'vendor_id' => $vendorId,
                        'order_id' => $order->id,
                        'order_item_id' => $orderItem->id,
                        'subtotal' => $net,
                        'commission' => $commission,
                        'tcs_amount' => $tcs,
                        'payout_amount' => round($net - $commission - $tcs, 2),
                        'payout_status' => 'pending',
                    ]);
                }
                $stockRow->decrement('stock_quantity', $item->quantity);
            }

            // Payment stays pending until COD is collected or Razorpay confirms it.
            $order->payments()->create([
                'method' => $data['payment_method'],
                'amount' => $order->total,
                // The books stay in rupees; an international order may be charged in the customer's own currency.
                'currency' => $charge['currency'] ?? 'INR',
                'charge_amount' => $charge['amount'] ?? null,
                'status' => 'pending',
            ]);

            if ($coupon['coupon']) {
                $coupon['coupon']->increment('used_count');
            }

            $cart->update(['status' => 'completed']);

            return $order;
        });

        session()->forget(CouponService::SESSION_KEY);

        if ($data['payment_method'] === 'razorpay') {
            return redirect()->route('payments.razorpay.initiate', $order);
        }

        return redirect()->route('customer.orders.show', $order)->with('success', __('shop.messages.order_placed'));
    }

    /** Packed weight of the cart in kg; products without a weight count as nothing. */
    private function packedWeight($items): float
    {
        return round((float) $items->sum(fn ($i) => $i->quantity * (float) $i->product->export_weight_kg), 2);
    }

    /**
     * Why this cart cannot be sent to an address abroad, as messages for the customer; empty when it can (or the address is in India).
     * A product must be ticked for export, must not block that country, and must meet its minimum export quantity.
     *
     * @return array<int, string>
     */
    private function exportProblems($items, ?Address $address): array
    {
        if (! $address?->isForeign()) {
            return [];
        }

        $products = $items->groupBy('product_id')->map(fn ($rows) => [$rows->first()->product, (int) $rows->sum('quantity')]);
        $names = fn ($rows) => $rows->map(fn ($r) => $r[0]->name)->implode(', ');
        $homeOnly = $products->filter(fn ($r) => ! $r[0]->export_available);
        $banned = $products->filter(fn ($r) => $r[0]->export_available && ! $r[0]->exportableTo($address->country));
        $tooFew = $products->filter(fn ($r) => $r[0]->exportableTo($address->country) && $r[1] < (int) $r[0]->export_min_qty);

        return array_values(array_filter([
            $homeOnly->isNotEmpty() ? __('shop.checkout.not_exportable', ['products' => $names($homeOnly)]) : null,
            $banned->isNotEmpty() ? __('shop.checkout.not_to_country', ['products' => $names($banned), 'country' => $address->country]) : null,
            ...$tooFew->map(fn ($r) => __('shop.checkout.export_min_qty', ['product' => $r[0]->name, 'min' => $r[0]->export_min_qty]))->values()->all(),
        ]));
    }

    /**
     * Delivery options open to this order. Abroad only courier (flat rate) options apply: collecting from the store is not an
     * export, and the shop's free-delivery offer is meant for India.
     */
    private function deliveryOptions($methods, bool $export)
    {
        return $export ? $methods->where('type', 'flat_rate')->values() : $methods;
    }

    /** Per-product commission override, else the vendor's default rate. */
    private function commissionFor(Product $product, float $lineTotal): float
    {
        $rate = VendorProduct::where('vendor_id', $product->vendor_id)->where('product_id', $product->id)->value('commission_rate')
            ?? $product->vendor->effectiveCommissionRate();

        return round($lineTotal * ((float) $rate / 100), 2);
    }

    /**
     * Single source of truth for tax and shipping, used by both the checkout page and order creation.
     * Lines arrive already reduced by the coupon, so GST is charged on the discounted value. GST is worked out
     * per line at the product's own rate, plus GST on the shipping fee.
     *
     * @param  array<int, array{0: float, 1: float}>  $lines  [line total after discount, GST percent]
     * @param  array<int, string>  $problems  reasons this cart cannot go to this address abroad (see exportProblems)
     * @param  float  $weight  packed weight of the cart in kg, for zones priced per kg
     */
    private function totals(array $lines, ?Address $shippingAddress = null, float $discount = 0.0, ?ShippingMethod $method = null, array $problems = [], float $weight = 0.0): array
    {
        // An address outside India (only possible with exports switched on) is a zero-rated export: no GST at all,
        // and it can only be delivered where the admin has set up a shipping zone for that country.
        $export = (bool) $shippingAddress?->isForeign();
        $blocked = $export ? $problems : [];
        $deliverable = ! $export || (ShippingZone::forAddress($shippingAddress) !== null && ! $blocked);

        $net = round(array_sum(array_column($lines, 0)), 2);
        $itemTax = $export ? 0.0 : round(array_sum(array_map(fn ($l) => round($l[0] * $l[1] / 100, 2), $lines)), 2);
        $shipping = $method ? $method->chargeFor($shippingAddress, $net, $weight) : ShippingZone::shippingFor($shippingAddress, $net, $weight);
        $shippingTax = $export ? 0.0 : round($shipping * config('shop.shipping_tax_rate') / 100, 2);
        $tax = round($itemTax + $shippingTax, 2);
        $total = round($net + $tax + $shipping, 2);

        return [
            'subtotal' => round($net + $discount, 2), 'discount' => $discount, 'tax' => $tax, 'shipping' => $shipping, 'shipping_tax' => $shippingTax,
            'total' => $total, 'export' => $export, 'deliverable' => $deliverable, 'blocked' => $blocked, 'country' => $export ? $shippingAddress->country : null, 'charge' => $export ? $this->chargeFor($total) : null,
        ];
    }

    /**
     * What an international order is charged in the currency the visitor picked, or null to charge rupees.
     * The rate is the admin's own (Admin > Currencies) and is saved on the order, so later rate changes do not touch it.
     *
     * @return array{currency: string, rate: float, amount: float, label: string}|null
     */
    private function chargeFor(float $inr): ?array
    {
        $currency = config('shop.export.charge_foreign_currency') ? \App\Support\Money::current() : null;
        // Razorpay takes amounts in hundredths; currencies with no or three decimal places would need other handling.
        if (! $currency || in_array($currency->code, ['JPY', 'KRW', 'VND', 'CLP', 'ISK', 'HUF', 'TWD', 'BHD', 'KWD', 'OMR', 'JOD', 'TND'], true)) {
            return null;
        }

        $amount = round($inr * (float) $currency->exchange_rate, 2);

        return [
            'currency' => $currency->code,
            'rate' => (float) $currency->exchange_rate,
            'amount' => $amount,
            'label' => $currency->position === 'after' ? number_format($amount, 2) . ' ' . $currency->symbol : $currency->symbol . number_format($amount, 2),
        ];
    }
}
