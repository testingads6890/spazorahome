@extends('layouts.account')

@section('title', __('Tracking') . ' ' . $order->tracking_number . ' - SpazoraHome')
@section('heading', __('Tracking result'))
@section('crumb', __('Tracking'))

@php
    $steps = ['pending' => __('Placed'), 'processing' => __('Processing'), 'shipped' => __('Shipped'), 'delivered' => __('Delivered')];
    $keys = array_keys($steps);
    $idx = array_search($order->status, $keys, true);
@endphp

@section('account')
    <div class="card card-body mb-3">
        <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
            <div>
                <div class="small text-muted">{{ __(':carrier tracking number', ['carrier' => $order->carrier ?: __('Carrier')]) }}</div>
                <div class="fs-5 fw-bold"><code>{{ $order->tracking_number }}</code></div>
            </div>
            <div class="text-sm-end">
                <span class="status-badge {{ $order->status }}">@status($order->status)</span>
                @if($order->estimated_delivery)<div class="small text-muted mt-1">{{ __('Estimated delivery :date', ['date' => \Illuminate\Support\Carbon::parse($order->estimated_delivery)->translatedFormat('d M Y')]) }}</div>@endif
            </div>
        </div>

        @if($order->status === 'cancelled')
            <div class="alert alert-danger mb-0">{{ __('This order was cancelled.') }}</div>
        @else
            <ol class="timeline" aria-label="{{ __('Shipment progress') }}">
                @foreach($steps as $key => $label)
                    @php $i = array_search($key, $keys, true); @endphp
                    <li class="{{ $i < $idx ? 'done' : ($i === $idx ? 'now' : '') }}">{{ $label }}</li>
                @endforeach
            </ol>
        @endif

        <div class="d-flex flex-wrap gap-2 mt-4">
            <a href="{{ route('customer.orders.show', $order) }}" class="btn btn-primary btn-sm">{{ __('Order :number', ['number' => $order->order_number]) }}</a>
            @if($order->tracking_url)<a href="{{ $order->tracking_url }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">{{ __('Open on carrier website') }}</a>@endif
            <a href="{{ route('customer.order-tracking.index') }}" class="btn btn-outline-primary btn-sm">{{ __('Track another') }}</a>
        </div>
    </div>

    <div class="card card-body">
        <h6 class="text-muted text-uppercase small">{{ __('In this shipment') }}</h6>
        @foreach($order->items as $item)
            <div class="d-flex justify-content-between py-1"><span>{{ $item->product_name }} <span class="text-muted">&times; {{ $item->quantity }}</span></span></div>
        @endforeach
    </div>
@endsection
