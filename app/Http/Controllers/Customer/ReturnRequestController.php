<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ReturnRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Order return requests: the customer picks items and a reason; the team approves or declines. */
class ReturnRequestController extends Controller
{
    public function index(Request $request)
    {
        $requests = ReturnRequest::where('user_id', $request->user()->id)->with('order:id,order_number')->latest('id')->paginate(15);
        $returnable = Order::where('user_id', $request->user()->id)->whereIn('status', ['shipped', 'delivered'])->latest('id')->limit(20)->get()
            ->filter(fn (Order $o) => ReturnRequest::windowOpen($o) && array_sum($o->returnableQuantities()) > 0)->values();

        return view('customer.returns.index', compact('requests', 'returnable'));
    }

    public function create(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);
        $order->load('items.product');

        return view('customer.returns.create', ['order' => $order, 'left' => $order->returnableQuantities(), 'reasons' => ReturnRequest::REASONS]);
    }

    public function store(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);
        $data = $request->validate([
            'quantities' => 'required|array',
            'quantities.*' => 'nullable|integer|min:0',
            'reason' => ['required', Rule::in(array_keys(ReturnRequest::REASONS))],
            'customer_note' => 'nullable|string|max:2000',
        ], ['reason.required' => __('Choose why you are returning the items.')]);

        $left = $order->returnableQuantities();
        $items = [];
        foreach ($data['quantities'] as $itemId => $qty) {
            $qty = (int) $qty;
            if ($qty <= 0) {
                continue;
            }
            if (! isset($left[$itemId]) || $qty > $left[$itemId]) {
                throw ValidationException::withMessages(['quantities' => __('You can return at most the quantity you received.')]);
            }
            $items[(int) $itemId] = $qty;
        }
        if (! $items) {
            throw ValidationException::withMessages(['quantities' => __('Choose at least one item to return.')]);
        }

        ReturnRequest::create([
            'order_id' => $order->id,
            'user_id' => $request->user()->id,
            'status' => 'pending',
            'items' => $items,
            'reason' => $data['reason'],
            'customer_note' => $data['customer_note'] ?? null,
        ]);

        return redirect()->route('customer.returns.index')->with('success', __('Return request sent. We will reply within 1–2 working days and arrange the pickup.'));
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        if (! ReturnRequest::windowOpen($order)) {
            abort(redirect()->route('customer.orders.show', $order)->with('error', __('This order can no longer be returned.')));
        }
        if (ReturnRequest::where('order_id', $order->id)->where('status', 'pending')->exists()) {
            abort(redirect()->route('customer.returns.index')->with('error', __('A return request for this order is already being reviewed.')));
        }
    }
}
