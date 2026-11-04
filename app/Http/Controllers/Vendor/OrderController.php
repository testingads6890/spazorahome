<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorOrder;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /** Where a seller's order line can be: the stages they move it through, plus cancelled and returned for looking back. */
    public const TABS = [
        'all' => 'All',
        'pending' => 'New',
        'processing' => 'Processing',
        'ready_to_ship' => 'Ready to ship',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'returned' => 'Returned',
    ];

    public function index(Request $request)
    {
        $vendor = auth()->user()->vendorAccount();
        $base = fn () => VendorOrder::where('vendor_id', $vendor->id);
        $returned = \App\Models\CreditNoteItem::query()->select('order_item_id');
        // A cancelled order has its payout marked failed; a returned line has a credit note against it.
        $scope = function ($query, string $tab) use ($returned) {
            return match ($tab) {
                'all' => $query,
                'cancelled' => $query->where('payout_status', 'failed'),
                'returned' => $query->whereIn('order_item_id', $returned),
                default => $query->where('status', $tab)->where('payout_status', '!=', 'failed'),
            };
        };

        $counts = [];
        foreach (array_keys(self::TABS) as $key) {
            $counts[$key] = $scope($base(), $key)->count();
        }
        $tab = array_key_exists((string) $request->query('status'), self::TABS) ? (string) $request->query('status') : 'all';

        $orders = $scope($base(), $tab)->with(['order.user', 'order.shippingAddress', 'orderItem.product'])->latest('id')->paginate(20)->withQueryString();
        $returnedIds = \App\Models\CreditNoteItem::whereIn('order_item_id', $orders->pluck('order_item_id'))->pluck('order_item_id')->all();

        return view('vendor.orders.index', ['orders' => $orders, 'counts' => $counts, 'tab' => $tab, 'tabs' => self::TABS, 'returnedIds' => $returnedIds]);
    }

    /** The shop's own order lines as a CSV file (sale, commission, TCS, payout). */
    public function export(Request $request)
    {
        $dates = $request->validate(['from' => 'nullable|date', 'to' => 'nullable|date|after_or_equal:from']);
        $query = VendorOrder::where('vendor_id', auth()->user()->vendorAccount()->id)
            ->when($dates['from'] ?? null, fn ($q, $from) => $q->where('created_at', '>=', \Illuminate\Support\Carbon::parse($from)->startOfDay()))
            ->when($dates['to'] ?? null, fn ($q, $to) => $q->where('created_at', '<=', \Illuminate\Support\Carbon::parse($to)->endOfDay()))
            ->with(['vendor', 'order', 'orderItem'])->orderBy('id');

        $rows = (function () use ($query) {
            foreach ($query->lazy(300) as $vendorOrder) {
                yield \App\Http\Controllers\Admin\ExportController::vendorOrderRow($vendorOrder);
            }
        })();

        return \App\Support\CsvExport::download('my-orders', \App\Http\Controllers\Admin\ExportController::vendorOrderHeadings(), $rows);
    }

    public function show(VendorOrder $vendorOrder)
    {
        $this->authorizeVendor($vendorOrder);
        $vendorOrder->load('order.items.product', 'order.user', 'order.shippingAddress', 'orderItem.product');
        $invoice = \App\Models\SupplierInvoice::where('order_id', $vendorOrder->order_id)->where('vendor_id', $vendorOrder->vendor_id)->first();

        return view('vendor.orders.show', compact('vendorOrder', 'invoice'));
    }

    /**
     * The seller moves their line forward: processing, ready to ship, shipped, delivered.
     * The customer's order follows along as far as "ready to ship"; shipping itself (courier and tracking number)
     * is recorded by the marketplace team on the order.
     */
    public function updateStatus(Request $request, VendorOrder $vendorOrder)
    {
        $this->authorizeVendor($vendorOrder);
        abort_if($vendorOrder->payout_status === 'failed', 403, 'This order was cancelled.');
        $request->validate(['status' => 'required|in:processing,ready_to_ship,shipped,delivered']);
        $vendorOrder->update(['status' => $request->status]);

        $order = $vendorOrder->order;
        if ($order && in_array($request->status, ['processing', 'ready_to_ship'], true)) {
            $lines = VendorOrder::where('order_id', $order->id)->where('payout_status', '!=', 'failed')->pluck('status');
            $allReady = $lines->every(fn ($s) => in_array($s, ['ready_to_ship', 'shipped', 'delivered'], true));
            if ($allReady && in_array($order->status, ['pending', 'processing'], true)) {
                $order->update(['status' => 'ready_to_ship']);
            } elseif ($order->status === 'pending') {
                $order->update(['status' => 'processing']);
            }
        }

        return back()->with('success', 'Status updated.');
    }

    /** The vendor's own tax invoice for this order (only when the marketplace invoices per supplier). */
    public function invoice(VendorOrder $vendorOrder, bool $pdf = false)
    {
        $this->authorizeVendor($vendorOrder);
        $invoice = \App\Models\SupplierInvoice::where('order_id', $vendorOrder->order_id)->where('vendor_id', $vendorOrder->vendor_id)->first();
        abort_unless($invoice, 404);

        $data = app(\App\Services\InvoiceBuilder::class)->build($vendorOrder->order, $invoice);
        $service = app(\App\Services\InvoicePdf::class);
        if ($pdf) {
            return $service->available() ? $service->download($vendorOrder->order, $data) : redirect()->route('vendor.orders.invoice', $vendorOrder);
        }

        return view('invoices.show', ['order' => $vendorOrder->order, 'invoice' => $data, 'pdfUrl' => $service->available() ? route('vendor.orders.invoice.pdf', $vendorOrder) : null]);
    }

    public function invoicePdf(VendorOrder $vendorOrder)
    {
        return $this->invoice($vendorOrder, true);
    }

    private function authorizeVendor(VendorOrder $vendorOrder)
    {
        $vendor = auth()->user()->vendorAccount();
        abort_if($vendorOrder->vendor_id !== $vendor->id, 403);
    }
}
