@extends('layouts.account')

@section('title', __('My orders - SpazoraHome'))
@section('heading', __('My orders'))
@section('crumb', __('Orders'))

@php $inr = fn ($v) => '₹' . number_format((float) $v, 2); @endphp

@section('account')
    <form method="GET" class="account-tabs" role="tablist">
        @foreach(['' => __('All'), 'pending' => __('Pending'), 'processing' => __('Processing'), 'shipped' => __('Shipped'), 'delivered' => __('Delivered'), 'cancelled' => __('Cancelled')] as $k => $label)
            <a href="{{ route('customer.orders.index', $k ? ['status' => $k] : []) }}" class="{{ (string) request('status') === (string) $k ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </form>

    @forelse($orders as $order)
        <div class="order-card mb-3">
            <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                <div>
                    <a href="{{ route('customer.orders.show', $order) }}" class="fw-bold">{{ $order->order_number }}</a>
                    <div class="small text-muted">{{ __('Placed :date', ['date' => $order->created_at->translatedFormat('d M Y')]) }}</div>
                </div>
                <div class="text-end">
                    <span class="status-badge {{ $order->status }}">@status($order->status)</span>
                    <div class="fw-bold">{{ $inr($order->total) }}</div>
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="thumbs-row">
                    @foreach($order->items->take(4) as $item)
                        @php $img = $item->product?->photo(); @endphp
                        <span class="mini" title="{{ $item->product_name }}">@if($img)<img src="{{ $img['thumb'] }}" alt="{{ $item->product_name }}" loading="lazy">@else<i class="bi bi-house-heart"></i>@endif</span>
                    @endforeach
                    @if($order->items->count() > 4)<span class="mini small">+{{ $order->items->count() - 4 }}</span>@endif
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('customer.orders.show', $order) }}" class="btn btn-primary btn-sm">{{ __('View details') }}</a>
                    @if($order->hasInvoice())<a href="{{ route('customer.orders.invoice', $order) }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">{{ __('Invoice') }}</a>@endif
                    @if($order->tracking_number)<a href="{{ route('customer.order-tracking.track', ['tracking_number' => $order->tracking_number]) }}" class="btn btn-outline-primary btn-sm">{{ __('Track') }}</a>@endif
                </div>
            </div>
        </div>
    @empty
        <div class="empty">
            <i class="bi bi-box-seam"></i>
            <h4 class="mt-3">{{ request('status') ? __('No orders with this status') : __('No orders yet') }}</h4>
            <p>{{ __('When you place an order it will show up here.') }}</p>
            <a href="{{ route('search') }}" class="btn btn-primary">{{ __('Start shopping') }}</a>
        </div>
    @endforelse

    <div class="d-flex justify-content-center">{{ $orders->links() }}</div>
@endsection
