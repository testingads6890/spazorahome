@extends('layouts.admin')

@section('title', 'International sales')

@php $ready = collect($checks)->every(fn ($c) => $c[1]); @endphp

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
        <h2 class="h4 mb-0">International sales (export)</h2>
        <a href="{{ route('admin.exports.orders') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-download"></i> Orders CSV (has a Country column)</a>
    </div>
    <p class="text-muted">Customers outside India can order products marked "available for export". Those orders carry no GST (zero-rated export under your LUT), are paid online only, and are delivered only to countries that have a shipping zone.</p>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">Setup {!! $ready ? '<span class="badge text-bg-success ms-1">Ready</span>' : '<span class="badge text-bg-warning ms-1">Not ready</span>' !!}</div>
                <div class="card-body">
                    @foreach($checks as [$label, $ok, $help])
                        <div class="d-flex gap-2 {{ $loop->last ? '' : 'mb-2' }}">
                            <i class="bi {{ $ok ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' }}"></i>
                            <div><strong>{{ $label }}</strong>@unless($ok)<div class="small text-muted">{{ $help }}</div>@endunless</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header">Products</div>
                <div class="card-body">
                    <p class="mb-2"><strong>{{ $exportable }}</strong> of {{ $products }} products are available for export.</p>
                    @if($perKg && $noWeight)<p class="small text-danger mb-2"><i class="bi bi-exclamation-triangle"></i> {{ $noWeight }} of them have no packed weight, so freight charged per kg leaves them out. Add the weight on the product form.</p>@endif
                    <div class="d-flex flex-wrap gap-2">
                        <form action="{{ route('admin.international.products') }}" method="POST" onsubmit="return confirm('Make every active product available for export?');">@csrf
                            <input type="hidden" name="available" value="1"><button class="btn btn-primary btn-sm">Allow all active products</button>
                        </form>
                        <form action="{{ route('admin.international.products') }}" method="POST" onsubmit="return confirm('Stop exporting every product?');">@csrf
                            <input type="hidden" name="available" value="0"><button class="btn btn-outline-secondary btn-sm">Allow none</button>
                        </form>
                        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-primary btn-sm">Choose product by product</a>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">Where and how</div>
                <div class="card-body">
                    <p class="mb-1"><strong>Countries with a shipping zone:</strong>
                        {{ $zones->flatMap(fn ($z) => $z->countries)->reject(fn ($c) => \App\Support\Countries::isIndia($c))->unique()->sort()->implode(', ') ?: 'none yet' }}
                        <a href="{{ route('admin.shipping.zones.index') }}" class="small ms-1">Shipping zones</a></p>
                    <p class="mb-0"><strong>Charged in:</strong>
                        @if($chargeForeign && $currencies->isNotEmpty())
                            the currency the customer picks ({{ $currencies->pluck('code')->implode(', ') }}) at your rate from <a href="{{ route('admin.currencies.index') }}">Currencies</a>, otherwise rupees.
                        @else
                            Indian rupees. <span class="text-muted small">Switch on other currencies under <a href="{{ route('admin.currencies.index') }}">Currencies</a> to charge in them.</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <h3 class="h5 mb-0">International orders</h3>
        <span class="text-muted">{{ $totals['orders'] }} {{ \Illuminate\Support\Str::plural('order', $totals['orders']) }} &middot; ₹{{ number_format($totals['value'], 2) }} (cancelled left out)</span>
    </div>
    <table class="table table-hover align-middle">
        <thead class="table-light"><tr><th>Order</th><th>Date</th><th>Customer</th><th>Country</th><th>Status</th><th>Total (₹)</th><th>Charged</th><th></th></tr></thead>
        <tbody>
        @forelse($orders as $order)
            <tr>
                <td>{{ $order->order_number }}</td>
                <td>{{ $order->created_at->format('d M Y') }}</td>
                <td>{{ $order->user?->name ?? '-' }}</td>
                <td>{{ $order->shippingAddress?->country ?? '-' }}</td>
                <td>{{ ucfirst($order->status) }}</td>
                <td>{{ number_format($order->total, 2) }}</td>
                <td>{{ $order->charge_currency ? $order->charge_currency . ' ' . number_format((float) $order->charge_total, 2) : 'INR' }}</td>
                <td><a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">View</a></td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted">No international orders yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $orders->links() }}

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 mb-2">
        <h3 class="h5 mb-0" id="enquiries">Export enquiries</h3>
        <span class="text-muted">{{ $newEnquiries }} new &middot; sent from the "Request an export quote" form on product pages</span>
    </div>
    <div class="table-responsive">
    <table class="table align-middle">
        <thead class="table-light"><tr><th>Date</th><th>Buyer</th><th>Country</th><th>Product</th><th>Qty</th><th>Message</th><th style="min-width: 260px">Status and note</th></tr></thead>
        <tbody>
        @forelse($enquiries as $enquiry)
            <tr>
                <td class="text-nowrap">{{ $enquiry->created_at->format('d M Y') }}</td>
                <td>{{ $enquiry->name }}@if($enquiry->company)<div class="small text-muted">{{ $enquiry->company }}</div>@endif
                    <div class="small"><a href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a>@if($enquiry->phone) &middot; {{ $enquiry->phone }}@endif</div></td>
                <td>{{ $enquiry->country }}</td>
                <td>{{ $enquiry->product?->name ?? '-' }}</td>
                <td>{{ number_format($enquiry->quantity) }}</td>
                <td class="small">{{ $enquiry->message ?: '-' }}</td>
                <td>
                    <form action="{{ route('admin.international.enquiries.update', $enquiry) }}" method="POST" class="d-flex flex-wrap gap-1">@csrf @method('PUT')
                        <select name="status" class="form-select form-select-sm w-auto" aria-label="Status">
                            @foreach(\App\Models\ExportEnquiry::STATUSES as $value => $label)<option value="{{ $value }}" @selected($enquiry->status === $value)>{{ $label }}</option>@endforeach
                        </select>
                        <input type="text" name="admin_notes" class="form-control form-control-sm flex-fill w-auto" value="{{ $enquiry->admin_notes }}" placeholder="Note" aria-label="Note">
                        <button class="btn btn-sm btn-outline-primary">Save</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted">No enquiries yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    {{ $enquiries->links() }}
</div>
@endsection
