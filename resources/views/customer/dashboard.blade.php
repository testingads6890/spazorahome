@extends('layouts.account')

@section('title', __('My account - SpazoraHome'))
@section('heading', __('Hello, :name', ['name' => \Illuminate\Support\Str::words($user->name, 1, '')]))

@php $inr = fn ($v) => '₹' . number_format((float) $v, 2); @endphp

@section('account')
    <div class="row g-3 mb-4">
        @foreach([
            ['customer.orders.index', 'bi-box-seam', $stats['orders'], __('Orders')],
            ['customer.wishlists.index', 'bi-heart', $stats['wishlist'], __('Wishlist')],
            ['customer.support-tickets.index', 'bi-life-preserver', $stats['tickets'], __('Open tickets')],
            ['customer.orders.index', 'bi-wallet2', $inr($stats['spent']), __('Total spent')],
        ] as [$route, $icon, $value, $label])
            <div class="col-6 col-xl-3 stat-col">
                <a class="stat-tile" href="{{ route($route) }}"><span class="ico"><i class="bi {{ $icon }}"></i></span><span><strong>{{ $value }}</strong><small>{{ $label }}</small></span></a>
            </div>
        @endforeach
    </div>

    <div class="card card-body mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">{{ __('Recent orders') }}</h5>
            @if($recent->isNotEmpty())<a href="{{ route('customer.orders.index') }}" class="link-arrow">{{ __('View all') }}</a>@endif
        </div>
        @forelse($recent as $order)
            <a href="{{ route('customer.orders.show', $order) }}" class="d-flex align-items-center justify-content-between gap-3 py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                <span>
                    <strong class="text-dark">{{ $order->order_number }}</strong>
                    <span class="d-block small text-muted">{{ $order->created_at->translatedFormat('d M Y') }} &middot; {{ trans_choice('shop.cart.item', $order->items->sum('quantity'), ['count' => $order->items->sum('quantity')]) }}</span>
                </span>
                <span class="text-end">
                    <span class="status-badge {{ $order->status }}">@status($order->status)</span>
                    <strong class="d-block text-dark">{{ $inr($order->total) }}</strong>
                </span>
            </a>
        @empty
            <div class="empty py-3"><i class="bi bi-bag"></i><p class="mb-3 mt-2">{{ __('You have not placed any orders yet.') }}</p><a href="{{ route('search') }}" class="btn btn-primary">{{ __('Start shopping') }}</a></div>
        @endforelse
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card card-body h-100">
                <div class="d-flex justify-content-between align-items-center mb-2"><h5 class="mb-0">{{ __('Default address') }}</h5><a href="{{ route('customer.addresses.index') }}" class="small">{{ __('Manage') }}</a></div>
                @if($address)
                    <strong>{{ $address->full_name }}</strong>
                    <span class="text-muted small">{{ $address->address_line_1 }}@if($address->address_line_2), {{ $address->address_line_2 }}@endif<br>{{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}<br>{{ $address->phone }}</span>
                @else
                    <p class="text-muted mb-2">{{ __('No address saved yet.') }}</p><a class="btn btn-outline-primary btn-sm align-self-start" href="{{ route('customer.addresses.create') }}">{{ __('Add address') }}</a>
                @endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-body h-100">
                <h5 class="mb-2">{{ __('Account') }}</h5>
                <div class="small text-muted mb-3">{{ $user->email }}</div>
                <div class="d-flex flex-wrap gap-2 mt-auto">
                    <a href="{{ route('customer.profile.edit') }}" class="btn btn-outline-primary btn-sm">{{ __('Edit profile') }}</a>
                    <a href="{{ route('customer.reviews.index') }}" class="btn btn-outline-primary btn-sm">{{ __('My reviews') }}</a>
                    <a href="{{ route('customer.support-tickets.create') }}" class="btn btn-outline-primary btn-sm">{{ __('Get help') }}</a>
                </div>
            </div>
        </div>
    </div>
@endsection
