@extends('layouts.app')

@section('title', __('Checkout as a guest') . ' - SpazoraHome')

@php
    $inr = fn ($v) => '₹' . number_format((float) $v, 2);
    $subtotal = $cartItems->sum(fn ($i) => $i->currentUnitPrice() * $i->quantity);
@endphp

@section('content')
<div class="page-head"><div class="container">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('shop.cart.home') }}</a></li><li class="breadcrumb-item"><a href="{{ route('customer.cart.index') }}">{{ __('shop.nav.cart') }}</a></li><li class="breadcrumb-item active">{{ __('shop.checkout.title') }}</li></ol></nav>
    <h1>{{ __('Checkout as a guest') }}</h1>
    <p class="text-muted mb-0">{{ __('No need to register. Tell us where to deliver; you choose how to pay on the next step.') }}</p>
</div></div>

<div class="container py-4">
    @if(session('existing_account'))
        <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>{{ $errors->first('email') }}</span>
            <span class="d-flex gap-2">
                <a href="{{ route('login') }}" class="btn btn-primary btn-sm">{{ __('Sign in') }}</a>
                <a href="{{ route('password.request') }}" class="btn btn-outline-primary btn-sm">{{ __('Forgot password?') }}</a>
            </span>
        </div>
    @elseif($errors->any())
        <div class="alert alert-danger">{{ __('Please fix the highlighted fields.') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7 col-xl-8">
            <form method="POST" action="{{ route('checkout.guest.store') }}" novalidate>
                @csrf
                <section class="card card-body mb-3">
                    <h5 class="mb-3"><span class="step-no">1</span> {{ __('Your details') }}</h5>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold" for="first_name">{{ __('First Name') }}</label>
                            <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" class="form-control @error('first_name') is-invalid @enderror" autocomplete="given-name" required>
                            @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold" for="last_name">{{ __('Last Name') }}</label>
                            <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" class="form-control @error('last_name') is-invalid @enderror" autocomplete="family-name" required>
                            @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold" for="email">{{ __('Email') }}</label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" autocomplete="email" required>
                            <div class="form-text">{{ __('Your order updates and GST invoice are sent here.') }}</div>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold" for="phone">{{ __('Mobile number') }}</label>
                            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" inputmode="tel" autocomplete="tel" required>
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </section>

                <section class="card card-body mb-3">
                    <h5 class="mb-3"><span class="step-no">2</span> {{ __('shop.checkout.address') }}</h5>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="address_line_1">{{ __('Address Line 1') }}</label>
                            <input type="text" id="address_line_1" name="address_line_1" value="{{ old('address_line_1') }}" class="form-control @error('address_line_1') is-invalid @enderror" autocomplete="address-line1" required>
                            @error('address_line_1')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="address_line_2">{{ __('Address Line 2 (optional)') }}</label>
                            <input type="text" id="address_line_2" name="address_line_2" value="{{ old('address_line_2') }}" class="form-control @error('address_line_2') is-invalid @enderror" autocomplete="address-line2">
                            @error('address_line_2')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        @if($countries)
                            @php $chosen = old('country', \App\Support\Countries::INDIA); $abroad = $chosen !== \App\Support\Countries::INDIA; @endphp
                            <div class="col-12">
                                <label class="form-label fw-semibold" for="country">{{ __('Country') }}</label>
                                <select id="country" name="country" class="form-select @error('country') is-invalid @enderror" autocomplete="country-name" required>
                                    @foreach($countries as $name)
                                        <option value="{{ $name }}" {{ $chosen === $name ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @else
                            @php $abroad = false; @endphp
                        @endif
                        <div class="col-sm-5">
                            <label class="form-label fw-semibold" for="city">{{ __('City') }}</label>
                            <input type="text" id="city" name="city" value="{{ old('city') }}" class="form-control @error('city') is-invalid @enderror" autocomplete="address-level2" required>
                            @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label fw-semibold" for="state">{{ __('State') }}</label>
                            {{-- India: the GST state list. Abroad: the state or region as typed. Only one of the two is sent. --}}
                            <select id="state" name="state" class="form-select @error('state') is-invalid @enderror" autocomplete="address-level1" required @if($abroad) hidden disabled @endif>
                                <option value="">{{ __('Select state') }}</option>
                                @foreach($states as $code => $name)
                                    <option value="{{ $name }}" {{ old('state') === $name ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                            @if($countries)
                                <input type="text" id="state_text" name="state" value="{{ $abroad ? old('state') : '' }}" class="form-control @error('state') is-invalid @enderror" maxlength="100" autocomplete="address-level1" required @unless($abroad) hidden disabled @endunless>
                            @endif
                            @error('state')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label fw-semibold" for="postal_code">{{ __('PIN code') }}</label>
                            <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code') }}" class="form-control @error('postal_code') is-invalid @enderror" autocomplete="postal-code" required>
                            @error('postal_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </section>

                <section class="card card-body mb-3">
                    <h5 class="mb-1"><span class="step-no">3</span> {{ __('Create a password') }} <small class="text-muted fw-normal">{{ __('(optional)') }}</small></h5>
                    <p class="small text-muted">{{ __('Add one to sign in later and track this order. You can also skip it and set one afterwards.') }}</p>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold" for="password">{{ __('Password') }}</label>
                            <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold" for="password_confirmation">{{ __('Confirm password') }}</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" autocomplete="new-password">
                        </div>
                    </div>
                </section>

                <button type="submit" class="btn btn-accent btn-lg w-100">{{ __('Continue to payment') }} <i class="bi bi-arrow-right"></i></button>
                <p class="text-center small mt-3 mb-0">{{ __('Have an account?') }} <a href="{{ route('login') }}" class="fw-semibold">{{ __('Sign in') }}</a></p>
            </form>
        </div>

        <div class="col-lg-5 col-xl-4">
            <div class="summary-card card card-body">
                <h5 class="mb-3">{{ __('shop.checkout.summary') }}</h5>
                @foreach($cartItems as $item)
                    @php $img = $item->product->photo(); @endphp
                    <div class="sum-item">
                        <span class="mini">@if($img)<img src="{{ $img['thumb'] }}" alt="">@else<i class="bi bi-house-heart"></i>@endif<b>{{ $item->quantity }}</b></span>
                        <span class="flex-grow-1 small">{{ $item->product->name }}@if($item->variant)<span class="d-block text-muted">{{ $item->variant->label }}</span>@endif</span>
                        <span class="small fw-semibold">{{ $inr($item->currentUnitPrice() * $item->quantity) }}</span>
                    </div>
                @endforeach
                <hr>
                <div class="sum-row"><span>{{ __('shop.cart.subtotal') }}</span><strong>{{ $inr($subtotal) }}</strong></div>
                <div class="sum-row text-muted"><span>{{ __('shop.cart.gst_delivery') }}</span><span>{{ __('Next step') }}</span></div>
            </div>
        </div>
    </div>
</div>
@endsection

@if($countries)
@push('scripts')
<script>
    (function () {
        var country = document.getElementById('country'), list = document.getElementById('state'), text = document.getElementById('state_text');
        country.addEventListener('change', function () {
            var india = country.value === @json(\App\Support\Countries::INDIA);
            list.hidden = list.disabled = !india;
            text.hidden = text.disabled = india;
        });
    })();
</script>
@endpush
@endif
