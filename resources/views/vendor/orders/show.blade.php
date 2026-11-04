@extends('layouts.vendor')

@section('title', 'Order ' . ($vendorOrder->order?->order_number ?? ''))

@php
    $order = $vendorOrder->order;
    $cancelled = $vendorOrder->payout_status === 'failed';
    $steps = ['pending' => 'New', 'processing' => 'Processing', 'ready_to_ship' => 'Ready to ship', 'shipped' => 'Shipped', 'delivered' => 'Delivered'];
    $keys = array_keys($steps);
    $at = array_search($vendorOrder->status, $keys, true);
    $at = $at === false ? 0 : $at;
    $nextKey = $keys[$at + 1] ?? null;
    $ship = $order?->shippingAddress;
    $inr = fn ($v) => '₹' . number_format((float) $v, 2);
@endphp

@section('content')
<a href="{{ route('vendor.orders.index') }}" class="btn btn-outline-secondary btn-sm mb-3"><i class="bi bi-arrow-left"></i> All orders</a>

<div class="card mb-3">
    <div class="card-header fw-semibold">Progress</div>
    <div class="card-body">
        @if($cancelled)
            <p class="text-danger mb-0"><i class="bi bi-x-circle"></i> This order was cancelled. Nothing needs to be shipped.</p>
        @else
            <ol class="list-unstyled d-flex flex-wrap gap-2 mb-3">
                @foreach($steps as $key => $label)
                    @php $i = array_search($key, $keys, true); @endphp
                    <li class="badge rounded-pill {{ $i < $at ? 'text-bg-success' : ($i === $at ? 'text-bg-primary' : 'text-bg-light border') }}" @if($i === $at) aria-current="step" @endif>@if($i < $at)<i class="bi bi-check-lg"></i> @endif{{ $label }}</li>
                @endforeach
            </ol>
            @if($nextKey)
                <form action="{{ route('vendor.orders.updateStatus', $vendorOrder) }}" method="POST">@csrf @method('PUT')
                    <input type="hidden" name="status" value="{{ $nextKey }}">
                    <button class="btn btn-primary">Mark as {{ strtolower($steps[$nextKey]) }}</button>
                </form>
                @if($nextKey === 'ready_to_ship')<p class="form-text mb-0">Mark it ready once it is packed. The marketplace team then books the courier and adds the tracking number.</p>@endif
            @else
                <p class="text-success mb-0"><i class="bi bi-check-circle"></i> Delivered.</p>
            @endif
        @endif
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">Ship to</div>
            <div class="card-body">
                <p class="mb-1"><strong>{{ $ship ? trim($ship->first_name . ' ' . $ship->last_name) : ($order?->user?->name ?? 'Customer') }}</strong></p>
                @if($ship)
                    <p class="mb-1">{{ collect([$ship->address_line_1, $ship->address_line_2])->filter()->implode(', ') }}</p>
                    <p class="mb-1">{{ collect([$ship->city, $ship->state, $ship->postal_code])->filter()->implode(', ') }}</p>
                    <p class="mb-0">{{ $ship->country }}@if($ship->phone) &middot; {{ $ship->phone }}@endif</p>
                @else
                    <p class="text-muted mb-0">No delivery address on this order.</p>
                @endif
                @if($order?->tracking_number)<hr><p class="mb-0"><strong>Tracking:</strong> {{ $order->carrier ?: 'Courier' }} &middot; <code>{{ $order->tracking_number }}</code></p>@endif
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">Your earnings on this order</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-7 text-muted fw-normal">Sale value</dt><dd class="col-5 text-end">{{ $inr($vendorOrder->subtotal) }}</dd>
                    <dt class="col-7 text-muted fw-normal">Marketplace commission</dt><dd class="col-5 text-end">-{{ $inr($vendorOrder->commission) }}</dd>
                    @if((float) $vendorOrder->tcs_amount > 0)<dt class="col-7 text-muted fw-normal">TCS held back</dt><dd class="col-5 text-end">-{{ $inr($vendorOrder->tcs_amount) }}</dd>@endif
                    <dt class="col-7">Payout</dt><dd class="col-5 text-end fw-bold">{{ $inr($vendorOrder->payout_amount) }}</dd>
                    <dt class="col-7 text-muted fw-normal">Payout status</dt><dd class="col-5 text-end">{{ $cancelled ? 'Cancelled' : ucfirst($vendorOrder->payout_status) }}</dd>
                </dl>
                @if(! empty($invoice))
                    <a href="{{ route('vendor.orders.invoice', $vendorOrder) }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm mt-3">Your tax invoice {{ $invoice->number }}</a>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="table-responsive">
<table class="table table-bordered align-middle">
    <thead><tr><th>Product</th><th>SKU</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
    <tbody>
        @foreach($order?->items ?? [] as $item)
            @if($item->vendor_id == $vendorOrder->vendor_id)
                <tr>
                    <td>{{ $item->product_name ?: $item->product?->name }}</td>
                    <td>{{ $item->product_sku }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $inr($item->unit_price) }}</td>
                    <td>{{ $inr($item->subtotal) }}</td>
                </tr>
            @endif
        @endforeach
    </tbody>
</table>
</div>
@endsection
