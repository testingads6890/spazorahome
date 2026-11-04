<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SupplierInvoice;
use App\Services\RefundService;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    use \App\Http\Controllers\Concerns\ShowsInvoices;

    public function index(Request $request)
    {
        $query = Order::query();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('order_number', 'like', '%' . $request->search . '%');
        }
        $orders = $query->latest()->paginate(20);
        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['items', 'payments', 'creditNotes', 'user', 'supplierInvoices.vendor', 'shippingAddress']);
        $returnable = $order->hasInvoice() && in_array($order->status, ['shipped', 'delivered'], true) ? $order->returnableQuantities() : [];

        return view('admin.orders.show', compact('order', 'returnable'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate(['status' => 'required|in:pending,processing,ready_to_ship,shipped,out_for_delivery,delivered,cancelled']);
        if ($order->status === 'cancelled') {
            return back()->with('error', 'A cancelled order cannot be changed.');
        }

        if ($request->status === 'cancelled') {
            $done = $order->cancel(allowAfterShipment: true);
            if (! $done) {
                return back()->with('error', 'This order can no longer be cancelled.');
            }

            $order = $order->fresh();
            $message = 'Order cancelled and stock restored' . ($order->creditNote ? '; credit note ' . $order->creditNote->number . ' issued.' : '.');
            $refunded = (float) $order->payments()->sum('refunded_amount');
            $due = (float) $order->payments()->sum('refund_due');

            return back()->with('success', $message . $this->refundSummary($order, ['refunded' => $refunded, 'due' => $due]));
        }

        $order->update(['status' => $request->status] + ($request->status === 'delivered' && ! $order->delivered_at ? ['delivered_at' => now()] : []));
        // Keep the shipment record in step with the order.
        if ($order->shipment && in_array($request->status, ['out_for_delivery', 'delivered'], true)) {
            $order->shipment->update(['status' => $request->status] + ($request->status === 'delivered' ? ['delivered_at' => now()] : []));
        }

        return back()->with('success', 'Order status updated.');
    }

    public function createShipment(Order $order)
    {
        abort_if(in_array($order->status, ['cancelled', 'delivered'], true), 404);

        return view('admin.shipping.create', compact('order'));
    }

    public function storeShipment(Request $request, Order $order)
    {
        abort_if(in_array($order->status, ['cancelled', 'delivered'], true), 404);

        $data = $request->validate([
            'carrier' => 'required|string|max:100',
            'tracking_number' => ['required', 'string', 'max:100', \Illuminate\Validation\Rule::unique('shipments', 'tracking_number'), \Illuminate\Validation\Rule::unique('orders', 'tracking_number')->ignore($order->id)],
            'status' => 'required|in:pending,picked_up,shipped,in_transit,delivered',
        ]);

        DB::transaction(function () use ($data, $order) {
            $shipped = $data['status'] !== 'pending';
            $delivered = $data['status'] === 'delivered';

            Shipment::create([
                'order_id' => $order->id,
                'carrier' => $data['carrier'],
                'tracking_number' => $data['tracking_number'],
                'status' => $delivered ? 'delivered' : ($data['status'] === 'picked_up' ? 'picked_up' : ($shipped ? 'in_transit' : 'pending')),
                'shipped_at' => $shipped ? now() : null,
                'delivered_at' => $delivered ? now() : null,
            ]);

            $order->update([
                'carrier' => $data['carrier'],
                'tracking_number' => $data['tracking_number'],
                'status' => $delivered ? 'delivered' : ($shipped ? 'shipped' : $order->status),
                'delivered_at' => $delivered ? now() : $order->delivered_at,
            ]);
        });

        return redirect()->route('admin.orders.show', $order)->with('success', 'Shipment created.');
    }

    /** Move an existing shipment along: picked up, in transit, out for delivery, delivered. The order follows. */
    public function updateShipment(Request $request, Order $order)
    {
        $shipment = $order->shipment;
        abort_unless($shipment && $order->status !== 'cancelled', 404);
        $data = $request->validate(['status' => 'required|in:pending,picked_up,in_transit,out_for_delivery,delivered']);

        DB::transaction(function () use ($data, $order, $shipment) {
            $status = $data['status'];
            $shipment->update(['status' => $status]
                + (in_array($status, ['picked_up', 'in_transit'], true) && ! $shipment->shipped_at ? ['shipped_at' => now()] : [])
                + ($status === 'delivered' ? ['delivered_at' => now()] : []));

            $orderStatus = match ($status) {
                'picked_up', 'in_transit' => 'shipped',
                'out_for_delivery' => 'out_for_delivery',
                'delivered' => 'delivered',
                default => $order->status,
            };
            if ($orderStatus !== $order->status) {
                $order->update(['status' => $orderStatus] + ($orderStatus === 'delivered' ? ['delivered_at' => now()] : []));
            }
        });

        return back()->with('success', 'Shipment updated.');
    }

    public function invoice(Order $order)
    {
        return $this->invoiceDocument($order, 'admin', false);
    }

    /** Printable packing list for the courier or freight forwarder: what is in the consignment, HS codes, origin and weights. */
    public function packingList(Order $order)
    {
        $order->load(['items.product' => fn ($q) => $q->withTrashed(), 'shippingAddress']);
        $rows = $order->items->map(fn ($item) => [
            'name' => $item->product_name, 'sku' => $item->product_sku, 'hs' => $item->hsn_code, 'qty' => (int) $item->quantity,
            'origin' => $item->product?->country_of_origin ?: 'India',
            'unit' => (float) $item->product?->export_weight_kg > 0 ? (float) $item->product->export_weight_kg : null,
            'total' => (float) $item->product?->export_weight_kg > 0 ? round($item->quantity * (float) $item->product->export_weight_kg, 2) : null,
        ]);

        return view('invoices.packing-list', compact('order', 'rows'));
    }

    public function invoicePdf(Order $order)
    {
        return $this->invoiceDocument($order, 'admin', true);
    }

    public function showInvoice(Order $order, SupplierInvoice $supplierInvoice)
    {
        return $this->invoiceDocument($order, 'admin', false, $supplierInvoice);
    }

    public function showInvoicePdf(Order $order, SupplierInvoice $supplierInvoice)
    {
        return $this->invoiceDocument($order, 'admin', true, $supplierInvoice);
    }

    public function creditNote(Order $order)
    {
        return $this->creditNoteDocument($order, null, 'admin', false);
    }

    public function creditNotePdf(Order $order)
    {
        return $this->creditNoteDocument($order, null, 'admin', true);
    }

    public function showCreditNote(Order $order, CreditNote $creditNote)
    {
        return $this->creditNoteDocument($order, $creditNote, 'admin', false);
    }

    public function showCreditNotePdf(Order $order, CreditNote $creditNote)
    {
        return $this->creditNoteDocument($order, $creditNote, 'admin', true);
    }

    /** Partial return: credit some items of a shipped order, restock them and refund the customer. */
    public function storeReturn(Request $request, Order $order, RefundService $refunds)
    {
        $data = $request->validate([
            'quantities' => 'nullable|array',
            'quantities.*' => 'nullable|integer|min:0',
            'credit_shipping' => 'nullable|boolean',
            'restock' => 'nullable|boolean',
            'reason' => 'nullable|string|max:255',
        ]);

        $quantities = array_filter(array_map('intval', $data['quantities'] ?? []));
        if (! $quantities && ! $request->boolean('credit_shipping')) {
            return back()->with('error', 'Enter the quantity being returned for at least one item.');
        }

        $notes = $order->returnItems($quantities, $request->boolean('credit_shipping'), $request->boolean('restock'), $data['reason'] ?? 'Goods returned');
        if (! $notes || $notes->isEmpty()) {
            return back()->with('error', 'This return could not be recorded. Check the order has shipped and the quantities are not more than what is left.');
        }

        $total = round((float) $notes->sum('total'), 2);
        $numbers = $notes->pluck('number')->implode(', ');
        $result = $refunds->refundOrder($order, $total, 'Return ' . $numbers);
        $nothing = $result['refunded'] <= 0 && $result['due'] <= 0 ? ' No payment has been received for this order yet, so nothing was refunded.' : '';

        return back()->with('success', 'Credit ' . ($notes->count() > 1 ? 'notes ' : 'note ') . $numbers . ' issued for ' . number_format($total, 2) . '.' . $this->refundSummary($order, $result) . $nothing);
    }

    public function retryRefund(Order $order, Payment $payment, RefundService $refunds)
    {
        abort_unless($payment->order_id === $order->id, 404);

        return $refunds->retry($payment)
            ? back()->with('success', 'Refund sent.')
            : back()->with('error', 'Refund failed: ' . ($payment->fresh()->payment_details['refund_error'] ?? 'unknown error'));
    }

    public function markRefunded(Request $request, Order $order, Payment $payment, RefundService $refunds)
    {
        abort_unless($payment->order_id === $order->id, 404);
        $data = $request->validate(['reference' => 'nullable|string|max:100']);

        $refunds->markManuallyRefunded($payment, $data['reference'] ?? null);

        return back()->with('success', 'Refund recorded as paid by hand.');
    }

    /** Cash on delivery was handed over by the courier. */
    public function markPaid(Order $order, Payment $payment)
    {
        abort_unless($payment->order_id === $order->id, 404);
        if ($payment->status !== 'pending' || $payment->method !== 'cod' || $order->status === 'cancelled') {
            return back()->with('error', 'Only a pending cash-on-delivery payment can be marked as collected.');
        }

        $payment->update(['status' => 'completed', 'paid_at' => now()]);

        return back()->with('success', 'Payment marked as collected.');
    }

    /** One sentence for the flash message about where the money went. */
    private function refundSummary(Order $order, array $result): string
    {
        $text = '';
        if ($result['refunded'] > 0) {
            $text .= ' Refunded ' . number_format($result['refunded'], 2) . ' through Razorpay.';
        }
        if ($result['due'] > 0) {
            $text .= ' Refund of ' . number_format($result['due'], 2) . ' is still due: see Payments below.';
        }

        return $text;
    }
}
