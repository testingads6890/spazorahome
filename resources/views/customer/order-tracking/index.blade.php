@extends('layouts.account')

@section('title', __('Track order - SpazoraHome'))
@section('heading', __('Track your order'))
@section('crumb', __('Track order'))

@section('account')
    <div class="card card-body mb-4">
        <h5>{{ __('Have a tracking number?') }}</h5>
        <form method="GET" action="{{ route('customer.order-tracking.track') }}" class="d-flex flex-column flex-sm-row gap-2">
            <input type="text" name="tracking_number" class="form-control form-control-lg" placeholder="{{ __('Enter your tracking number') }}" required maxlength="100" aria-label="{{ __('Tracking number') }}">
            <button class="btn btn-primary btn-lg"><i class="bi bi-search"></i> {{ __('Track') }}</button>
        </form>
        @error('tracking_number')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
    </div>

    <h5 class="mb-3">{{ __('Your shipments') }}</h5>
    @php $shipped = $orders->filter(fn ($o) => $o->tracking_number); @endphp
    @forelse($shipped as $order)
        <div class="order-card mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <a class="fw-bold" href="{{ route('customer.orders.show', $order) }}">{{ $order->order_number }}</a>
                <div class="small text-muted">{{ $order->carrier ?: __('Carrier') }} &middot; <code>{{ $order->tracking_number }}</code></div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="status-badge {{ $order->status }}">@status($order->status)</span>
                <a href="{{ route('customer.order-tracking.track', ['tracking_number' => $order->tracking_number]) }}" class="btn btn-outline-primary btn-sm">{{ __('Track') }}</a>
            </div>
        </div>
    @empty
        <div class="empty"><i class="bi bi-truck"></i><p class="mt-2 mb-0">{{ __('No shipments yet. Tracking appears here once an order ships.') }}</p></div>
    @endforelse
@endsection
