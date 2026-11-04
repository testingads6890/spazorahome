@extends('layouts.app')

@section('title', __('shop.cart.title') . ' - SpazoraHome')

@php
    $inr = fn ($v) => '₹' . number_format((float) $v);
    $subtotal = $cartItems->sum(fn ($i) => $i->currentUnitPrice() * $i->quantity);
    $threshold = (float) config('shop.free_shipping_over');
    $remaining = max(0, $threshold - $subtotal);
    $progress = $threshold > 0 ? min(100, round($subtotal / $threshold * 100)) : 100;
    $couponCode = $coupon['coupon']?->code;
    $discount = (float) $coupon['discount'];
    $problems = $cartItems->filter(fn ($i) => ! $i->product->is_active || $i->quantity > $i->availableStock());
@endphp

@section('content')
<div class="page-head"><div class="container">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('shop.cart.home') }}</a></li><li class="breadcrumb-item active">{{ __('shop.nav.cart') }}</li></ol></nav>
    <h1>{{ __('shop.cart.title') }} @if($cartItems->isNotEmpty())<small class="text-muted fs-6">({{ trans_choice('shop.cart.item', $cartItems->sum('quantity'), ['count' => $cartItems->sum('quantity')]) }})</small>@endif</h1>
</div></div>

<div class="container py-4">
    @if(\App\Support\Money::isForeign())
        <div class="alert alert-info d-flex gap-2 align-items-start"><i class="bi bi-info-circle"></i><span>{{ __('shop.cart.inr_notice') }}</span></div>
    @endif
    @foreach(['success' => 'success', 'error' => 'danger'] as $key => $cls)
        @if(session($key))<div class="alert alert-{{ $cls }}">{{ session($key) }}</div>@endif
    @endforeach

    @if($cartItems->isEmpty())
        <div class="empty">
            <i class="bi bi-bag"></i>
            <h4 class="mt-3">{{ __('shop.cart.empty_title') }}</h4>
            <p>{{ __('shop.cart.empty_text') }}</p>
            <a href="{{ route('search') }}" class="btn btn-primary">{{ __('shop.cart.start_shopping') }}</a>
        </div>
    @else
        <div class="row g-4">
            <div class="col-lg-8">
                @if($problems->isNotEmpty())
                    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> {{ __('shop.cart.problems') }}</div>
                @endif

                <div class="ship-progress card card-body mb-3">
                    @if($remaining > 0)
                        <div class="small mb-2">{{ __('shop.cart.add_more', ['amount' => $inr($remaining)]) }}</div>
                    @else
                        <div class="small mb-2 text-success"><i class="bi bi-check-circle-fill"></i> {{ __('shop.cart.unlocked') }}</div>
                    @endif
                    <div class="progress" style="height: 8px"><div class="progress-bar" role="progressbar" style="width: {{ $progress }}%; background: var(--accent)" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div></div>
                </div>

                <div class="cart-lines">
                    @foreach($cartItems as $item)
                        @php
                            $p = $item->product;
                            $unit = $item->currentUnitPrice();
                            $img = $p->photo();
                            $stock = $item->availableStock();
                            $bad = ! $p->is_active || $item->quantity > $stock;
                        @endphp
                        <div class="cart-line {{ $bad ? 'is-bad' : '' }}">
                            <a class="thumb" href="{{ route('products.show', $p) }}">
                                @if($img)<img src="{{ $img['thumb'] }}" alt="{{ $p->name }}" loading="lazy">@else<i class="bi bi-house-heart"></i>@endif
                            </a>
                            <div class="info">
                                <a class="name" href="{{ route('products.show', $p) }}">{{ $p->name }}</a>
                                @if($item->variant)<div class="text-muted small">{{ $item->variant->label }}</div>@endif
                                <div class="text-muted small">{{ __('shop.cart.each', ['price' => $inr($unit)]) }}</div>
                                @php
                                    $bulkOff = $p->bulkDiscountFor((int) $item->quantity);
                                    $bulkNext = collect($p->bulkTiers())->filter(fn ($pct, $min) => $min > $item->quantity && $min <= $stock);
                                @endphp
                                @if($bulkOff > 0)
                                    <div class="cart-bulk"><i class="bi bi-tag"></i> {{ __('pages.product.bulk_save', ['pct' => rtrim(rtrim(number_format($bulkOff, 2), '0'), '.')]) }} &middot; {{ __('pages.product.bulk_title') }}</div>
                                @endif
                                @if($bulkNext->isNotEmpty())
                                    <div class="cart-bulk-next"><i class="bi bi-plus-circle"></i> {{ __('pages.product.bulk_next', ['more' => $bulkNext->keys()->first() - $item->quantity, 'pct' => rtrim(rtrim(number_format($bulkNext->first(), 2), '0'), '.')]) }}</div>
                                @endif
                                @if(! $p->is_active)
                                    <div class="text-danger small fw-semibold">{{ __('shop.cart.no_longer_available') }}</div>
                                @elseif($item->quantity > $stock)
                                    <div class="text-danger small fw-semibold">{{ __('shop.cart.only_in_stock', ['count' => $stock]) }}</div>
                                @endif
                            </div>
                            <div class="actions">
                                <form action="{{ route('customer.cart.update', $item) }}" method="POST" class="qty-form">
                                    @csrf @method('PUT')
                                    <div class="qty" data-qty>
                                        <button type="button" data-step="-1" aria-label="{{ __('shop.cart.decrease') }}"><i class="bi bi-dash"></i></button>
                                        <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="{{ max($stock, 1) }}" aria-label="{{ __('shop.cart.quantity') }}">
                                        <button type="button" data-step="1" aria-label="{{ __('shop.cart.increase') }}"><i class="bi bi-plus"></i></button>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary update-btn" hidden>{{ __('shop.cart.update') }}</button>
                                </form>
                                <div class="line-total">{{ $inr($unit * $item->quantity) }}</div>
                                <form action="{{ route('customer.cart.destroy', $item) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-link text-danger p-0 small" aria-label="{{ __('shop.cart.remove') }} {{ $p->name }}"><i class="bi bi-trash3"></i> {{ __('shop.cart.remove') }}</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                <a href="{{ route('search') }}" class="btn btn-outline-primary mt-3"><i class="bi bi-arrow-left"></i> {{ __('shop.cart.continue_shopping') }}</a>
            </div>

            <div class="col-lg-4">
                <div class="summary-card card card-body">
                    <h5 class="mb-3">{{ __('shop.cart.summary') }}</h5>

                    <div class="coupon-box mb-3">
                        @if($couponCode)
                            <div class="d-flex justify-content-between align-items-center gap-2 p-2 rounded" style="background:var(--brand-100)">
                                <span class="small"><i class="bi bi-tag-fill text-accent"></i> {{ __('shop.coupon.applied', ['code' => $couponCode]) }}</span>
                                <form action="{{ route('customer.coupon.remove') }}" method="POST">@csrf @method('DELETE')<button class="btn btn-link btn-sm text-danger p-0">{{ __('shop.coupon.remove') }}</button></form>
                            </div>
                        @else
                            <form action="{{ route('customer.coupon.apply') }}" method="POST" class="d-flex gap-2">
                                @csrf
                                <input type="text" name="code" value="{{ old('code') }}" class="form-control form-control-sm text-uppercase flex-grow-1" style="min-width:0" placeholder="{{ __('shop.coupon.code') }}" maxlength="50" aria-label="{{ __('shop.coupon.code') }}" autocomplete="off">
                                <button class="btn btn-outline-primary btn-sm">{{ __('shop.coupon.apply') }}</button>
                            </form>
                            @if(session('coupon_error'))<div class="text-danger small mt-1">{{ session('coupon_error') }}</div>@endif
                            @error('coupon')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        @endif
                    </div>
                    <div class="sum-row"><span>{{ __('shop.cart.subtotal') }}</span><strong>{{ $inr($subtotal) }}</strong></div>
                    @if($discount > 0)<div class="sum-row text-success"><span>{{ __('shop.cart.coupon_discount') }}</span><strong>-{{ $inr($discount) }}</strong></div>@endif
                    <div class="sum-row text-muted"><span>{{ __('shop.cart.gst_delivery') }}</span><span>{{ __('shop.cart.at_checkout') }}</span></div>
                    <hr>
                    <div class="sum-row total"><span>{{ __('shop.cart.estimated_total') }}</span><strong>{{ $inr($subtotal - $discount) }}+</strong></div>
                    <a href="{{ auth()->check() ? route('customer.checkout.index') : route('checkout.guest') }}" class="btn btn-accent btn-lg w-100 mt-3 {{ $problems->isNotEmpty() ? 'disabled' : '' }}" @if($problems->isNotEmpty()) aria-disabled="true" @endif>{{ __('shop.cart.proceed') }} <i class="bi bi-arrow-right"></i></a>
                    @guest<p class="small text-center mt-2 mb-0">{{ __('Have an account?') }} <a href="{{ route('login') }}" class="fw-semibold">{{ __('Sign in') }}</a></p>@endguest
                    <ul class="list-unstyled small text-muted mt-3 mb-0 d-grid gap-1">
                        <li><i class="bi bi-shield-check text-accent"></i> {{ __('shop.cart.secure') }}</li>
                        <li><i class="bi bi-receipt text-accent"></i> {{ __('shop.cart.gst_invoice') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Show the Update button only once the quantity was changed.
    document.querySelectorAll('.qty-form').forEach(function (f) {
        var input = f.querySelector('input'), btn = f.querySelector('.update-btn'), start = input.value;
        f.querySelectorAll('.qty button').forEach(function (b) { b.addEventListener('click', function () { btn.hidden = input.value === start; }); });
        input.addEventListener('input', function () { btn.hidden = input.value === start; });
    });
</script>
@endpush
