<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;

class OrderController extends Controller
{
    use \App\Http\Controllers\Concerns\ShowsInvoices;

    public function index(\Illuminate\Http\Request $request)
    {
        $request->validate(['status' => 'nullable|in:pending,processing,shipped,delivered,cancelled']);

        $orders = Order::where('user_id', auth()->id())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->with(['items.product.images'])
            ->latest()->paginate(10)->withQueryString();
        return view('customer.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);
        $order->load(['items.product.images', 'payments', 'shippingAddress', 'billingAddress', 'creditNote', 'creditNotes', 'supplierInvoices.vendor']);

        return view('customer.orders.show', compact('order'));
    }

    public function cancel(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        if (! $order->cancel()) {
            return back()->with('error', __('This order can no longer be cancelled.'));
        }

        return back()->with('success', __('Order cancelled.'));
    }

    public function invoice(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        return $this->invoiceDocument($order, 'customer', false);
    }

    public function invoicePdf(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        return $this->invoiceDocument($order, 'customer', true);
    }

    public function showInvoice(Order $order, \App\Models\SupplierInvoice $supplierInvoice)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        return $this->invoiceDocument($order, 'customer', false, $supplierInvoice);
    }

    public function showInvoicePdf(Order $order, \App\Models\SupplierInvoice $supplierInvoice)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        return $this->invoiceDocument($order, 'customer', true, $supplierInvoice);
    }

    public function creditNote(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        return $this->creditNoteDocument($order, null, 'customer', false);
    }

    public function creditNotePdf(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        return $this->creditNoteDocument($order, null, 'customer', true);
    }

    public function showCreditNote(Order $order, \App\Models\CreditNote $creditNote)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        return $this->creditNoteDocument($order, $creditNote, 'customer', false);
    }

    public function showCreditNotePdf(Order $order, \App\Models\CreditNote $creditNote)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        return $this->creditNoteDocument($order, $creditNote, 'customer', true);
    }
}
