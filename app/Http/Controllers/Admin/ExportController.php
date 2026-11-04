<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\VendorPayout;
use App\Services\InvoiceBuilder;
use App\Support\CsvExport;
use Illuminate\Http\Request;

/** CSV downloads of the shop's data for accounts, GST returns and back-ups. All take an optional from / to date. */
class ExportController extends Controller
{
    public function index()
    {
        return view('admin.exports.index', ['exports' => self::catalogue()]);
    }

    /** What the Exports page offers: route name => [title, what the date range applies to, description]. */
    public static function catalogue(): array
    {
        return [
            'admin.exports.orders' => ['Orders', 'Order date', 'One row per order: customer, address, amounts, payment, refund, invoice number and tracking.'],
            'admin.exports.sales-register' => ['GST sales register', 'Invoice date', 'One row per invoice line with HSN/SAC, taxable value and CGST / SGST / IGST. For GSTR-1 and your accountant.'],
            'admin.exports.credit-notes' => ['Credit note register', 'Credit note date', 'One row per credit note line, against its invoice, with the tax reversed.'],
            'admin.exports.sales-by-day' => ['Sales by day', 'Order date', 'Orders and sales value for each day, cancelled orders left out.'],
            'admin.exports.customers' => ['Customers', 'Joined date', 'Name, contact, number of orders and money spent.'],
            'admin.exports.products' => ['Products', 'Added date', 'The catalogue with price, GST rate, HSN code, stock and seller.'],
            'admin.exports.vendors' => ['Vendors', 'Joined date', 'Sellers with GSTIN, sales, commission, TCS and what is still to be paid.'],
            'admin.exports.vendor-orders' => ['Vendor orders', 'Order date', 'One row per vendor line: sale, commission, TCS and payout.'],
            'admin.exports.vendor-payouts' => ['Vendor payouts', 'Payout date', 'Payouts created for sellers and their status.'],
        ];
    }

    public function orders(Request $request)
    {
        $request->validate(['status' => 'nullable|in:pending,processing,shipped,delivered,cancelled', 'search' => 'nullable|string|max:100']);
        $query = $this->between(Order::query(), $request, 'created_at')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn ($q) => $q->where('order_number', 'like', '%' . $request->search . '%'))
            ->with(['user.profile', 'shippingAddress', 'payments'])->withCount('items')->orderBy('id');

        return CsvExport::download('orders', [
            'Order number', 'Date', 'Status', 'Customer', 'Email', 'Phone', 'City', 'State', 'Country', 'Items', 'Subtotal', 'Discount', 'Coupon',
            'Shipping', 'Delivery option', 'GST', 'Total', 'Currency', 'Export', 'Charged currency', 'Charged amount', 'Payment method', 'Payment status', 'Refunded', 'Refund due', 'Invoice number', 'Carrier', 'Tracking number',
        ], $this->rows($query, function (Order $o) {
            $payment = $o->payments->sortByDesc('id')->first();
            $address = $o->shippingAddress;

            return [
                $o->order_number, $o->created_at, $o->status, $o->user?->name, $o->user?->email, $address?->phone ?? $o->user?->profile?->phone,
                $address?->city, $address?->state, $address?->country, $o->items_count, CsvExport::money($o->subtotal), CsvExport::money($o->discount), $o->coupon_code,
                CsvExport::money($o->shipping), $o->shipping_method, CsvExport::money($o->tax), CsvExport::money($o->total), $o->currency, (bool) $o->is_export, $o->charge_currency, $o->charge_total === null ? '' : CsvExport::money($o->charge_total), $payment?->method, $payment?->status,
                CsvExport::money($o->payments->sum('refunded_amount')), CsvExport::money($o->payments->sum('refund_due')), $o->invoice_number, $o->carrier, $o->tracking_number,
            ];
        }));
    }

    /** Every line of every invoice issued in the period. Orders invoiced per supplier give one set of lines per supplier invoice. */
    public function salesRegister(Request $request, InvoiceBuilder $builder)
    {
        $query = $this->between(Order::whereNotNull('invoice_number'), $request, 'invoice_date')
            ->with(['items.vendor', 'user', 'shippingAddress', 'supplierInvoices.vendor'])->orderBy('invoice_date')->orderBy('id');

        $rows = (function () use ($query, $builder) {
            foreach ($query->lazy(200) as $order) {
                $documents = $order->supplierInvoices->isEmpty() ? [null] : $order->supplierInvoices->all();
                foreach ($documents as $document) {
                    yield from $this->documentRows($order, $builder->build($order, $document));
                }
            }
        })();

        return CsvExport::download('gst-sales-register', $this->registerHeadings('Invoice number', 'Invoice date'), $rows);
    }

    public function creditNotes(Request $request, InvoiceBuilder $builder)
    {
        $query = $this->between(CreditNote::query(), $request, 'issued_at')
            ->with(['order.items.vendor', 'order.user', 'order.shippingAddress', 'supplierInvoice.vendor', 'items.orderItem.vendor'])->orderBy('issued_at')->orderBy('id');

        $rows = (function () use ($query, $builder) {
            foreach ($query->lazy(200) as $note) {
                if ($note->order) {
                    yield from $this->documentRows($note->order, $builder->buildCreditNote($note->order, $note));
                }
            }
        })();

        return CsvExport::download('credit-note-register', [...$this->registerHeadings('Credit note number', 'Credit note date'), 'Against invoice', 'Reason'], $rows);
    }

    public function salesByDay(Request $request)
    {
        $days = $this->between(Order::where('status', '!=', 'cancelled'), $request, 'created_at')
            ->selectRaw('DATE(created_at) as day, COUNT(*) as orders, SUM(subtotal) as subtotal, SUM(discount) as discount, SUM(tax) as tax, SUM(shipping) as shipping, SUM(total) as total')
            ->groupBy('day')->orderBy('day')->get();

        return CsvExport::download('sales-by-day', ['Date', 'Orders', 'Subtotal', 'Discount', 'GST', 'Shipping', 'Total'], $days->map(fn ($d) => [
            $d->day, (int) $d->orders, CsvExport::money($d->subtotal), CsvExport::money($d->discount), CsvExport::money($d->tax), CsvExport::money($d->shipping), CsvExport::money($d->total),
        ]));
    }

    public function customers(Request $request)
    {
        $query = $this->between(User::whereHas('roles', fn ($q) => $q->where('name', 'customer')), $request, 'created_at')->with('profile')
            ->withCount('orders')->withSum(['orders as spent' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'total')->orderBy('id');

        return CsvExport::download('customers', ['Name', 'Email', 'Phone', 'Joined', 'Orders', 'Spent', 'Guest checkout account'], $this->rows($query, fn (User $u) => [
            $u->name, $u->email, $u->profile?->phone, $u->created_at, $u->orders_count, CsvExport::money($u->spent), (bool) $u->is_guest,
        ]));
    }

    public function products(Request $request)
    {
        $query = $this->between(Product::query(), $request, 'created_at')->with(['category', 'vendor'])->orderBy('id');

        return CsvExport::download('products', [
            'ID', 'Name', 'SKU', 'Category', 'Seller', 'Price', 'Sale price', 'GST %', 'HSN code', 'Stock', 'Active', 'Featured', 'Seller listing status', 'Added',
            'Available for export', 'Packed weight (kg)', 'Country of origin', 'Minimum export quantity', 'Not delivered to',
        ], $this->rows($query, fn (Product $p) => [
            $p->id, $p->name, $p->sku, $p->category?->name, $p->vendor?->business_name ?? config('shop.seller.name'), CsvExport::money($p->price),
            $p->sale_price === null ? '' : CsvExport::money($p->sale_price), $p->taxRate(), $p->hsn_code, (int) $p->stock_quantity, (bool) $p->is_active, (bool) $p->is_featured,
            $p->vendor_id ? $p->vendor_status : '', $p->created_at,
            (bool) $p->export_available, $p->export_weight_kg, $p->country_of_origin, (int) $p->export_min_qty, implode(', ', $p->export_blocked_countries ?? []),
        ]));
    }

    public function vendors(Request $request)
    {
        $live = fn ($q) => $q->where('payout_status', '!=', 'failed');
        $query = $this->between(Vendor::query(), $request, 'created_at')->withCount('products')
            ->withSum(['orders as sales' => $live], 'subtotal')->withSum(['orders as commission_total' => $live], 'commission')
            ->withSum(['orders as tcs_total' => $live], 'tcs_amount')
            ->withSum(['orders as payout_due' => fn ($q) => $q->whereIn('payout_status', ['pending', 'processing'])], 'payout_amount')
            ->withSum(['orders as payout_paid' => fn ($q) => $q->where('payout_status', 'paid')], 'payout_amount')->orderBy('id');

        return CsvExport::download('vendors', [
            'Business', 'Email', 'Phone', 'City', 'State', 'GSTIN', 'Status', 'Active', 'Commission %', 'Products', 'Sales', 'Commission', 'TCS', 'Payout due', 'Payout paid', 'Joined',
        ], $this->rows($query, fn (Vendor $v) => [
            $v->business_name, $v->email, $v->phone, $v->city, $v->state, $v->tax_number, $v->status, (bool) $v->is_active, $v->effectiveCommissionRate(), $v->products_count,
            CsvExport::money($v->sales), CsvExport::money($v->commission_total), CsvExport::money($v->tcs_total), CsvExport::money($v->payout_due), CsvExport::money($v->payout_paid), $v->created_at,
        ]));
    }

    public function vendorOrders(Request $request)
    {
        $request->validate(['vendor_id' => 'nullable|integer', 'payout_status' => 'nullable|in:pending,processing,paid,failed']);
        $query = $this->between(VendorOrder::query(), $request, 'created_at')
            ->when($request->filled('vendor_id'), fn ($q) => $q->where('vendor_id', $request->vendor_id))
            ->when($request->filled('payout_status'), fn ($q) => $q->where('payout_status', $request->payout_status))
            ->with(['vendor', 'order', 'orderItem'])->orderBy('id');

        return CsvExport::download('vendor-orders', self::vendorOrderHeadings(), $this->rows($query, [self::class, 'vendorOrderRow']));
    }

    public function vendorPayouts(Request $request)
    {
        $query = $this->between(VendorPayout::query(), $request, 'created_at')->with('vendor')->orderBy('id');

        return CsvExport::download('vendor-payouts', ['Vendor', 'Amount', 'Method', 'Reference', 'Status', 'Created', 'Paid'], $this->rows($query, fn (VendorPayout $p) => [
            $p->vendor?->business_name, CsvExport::money($p->amount), $p->method, $p->transaction_id, $p->status, $p->created_at, $p->paid_at,
        ]));
    }

    public static function vendorOrderHeadings(): array
    {
        return ['Order number', 'Order date', 'Vendor', 'Product', 'SKU', 'Quantity', 'Sale (after discount)', 'Commission', 'TCS', 'Payout', 'Order status', 'Fulfilment', 'Payout status', 'Paid on'];
    }

    public static function vendorOrderRow(VendorOrder $vo): array
    {
        return [
            $vo->order?->order_number, $vo->order?->created_at, $vo->vendor?->business_name, $vo->orderItem?->product_name, $vo->orderItem?->product_sku, $vo->orderItem?->quantity,
            CsvExport::money($vo->subtotal), CsvExport::money($vo->commission), CsvExport::money($vo->tcs_amount), CsvExport::money($vo->payout_amount),
            $vo->order?->status, $vo->status, $vo->payout_status, $vo->paid_at,
        ];
    }

    private function registerHeadings(string $number, string $date): array
    {
        return [$number, $date, 'Order number', 'Customer', 'Place of supply', 'Seller', 'Seller GSTIN', 'Item', 'HSN/SAC', 'Quantity', 'Taxable value', 'GST %', 'CGST', 'SGST', 'IGST', 'Line total'];
    }

    /** One CSV row per line of an invoice or credit note built by InvoiceBuilder. */
    private function documentRows(Order $order, array $doc): \Generator
    {
        $lines = $doc['shipping'] ? [...$doc['lines'], $doc['shipping']] : $doc['lines'];
        foreach ($lines as $line) {
            // In a single invoice each line names its own supplier; a per-supplier invoice has one seller for all lines.
            $seller = $doc['has_vendor_lines'] ? $line['supplier'] : $doc['seller'];
            $row = [
                $doc['number'], $doc['date']?->format('Y-m-d'), $order->order_number, $order->user?->name,
                $doc['place_of_supply'] ?? ($order->shippingAddress?->state ?: ''), $seller['name'] ?? '', $seller['gstin'] ?? '',
                $line['name'], $line['hsn'], $line['qty'], CsvExport::money($line['taxable']), $line['rate'],
                CsvExport::money($line['cgst']), CsvExport::money($line['sgst']), CsvExport::money($line['igst']), CsvExport::money($line['taxable'] + $line['tax']),
            ];
            if (isset($doc['against'])) {
                $row[] = $doc['against'];
                $row[] = $doc['reason'];
            }
            yield $row;
        }
    }

    /** Optional from / to dates (whole days) on a date column. */
    private function between($query, Request $request, string $column)
    {
        $dates = $request->validate(['from' => 'nullable|date', 'to' => 'nullable|date|after_or_equal:from']);

        return $query
            ->when($dates['from'] ?? null, fn ($q, $from) => $q->where($column, '>=', \Illuminate\Support\Carbon::parse($from)->startOfDay()))
            ->when($dates['to'] ?? null, fn ($q, $to) => $q->where($column, '<=', \Illuminate\Support\Carbon::parse($to)->endOfDay()));
    }

    /** Lazily map a query to CSV rows, a few hundred records at a time. */
    private function rows($query, callable $map): \Generator
    {
        foreach ($query->lazy(300) as $model) {
            yield $map($model);
        }
    }
}
