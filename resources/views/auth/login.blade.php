@extends('layouts.auth')

@php
    $role = $role ?? null;
    $heading = match ($role) { 'vendor' => __('Seller sign in'), 'customer' => __('Welcome back'), default => __('Welcome back') };
    $sub = match ($role) { 'vendor' => __('Manage your products, orders and payouts.'), default => __('Sign in to track orders, save favourites and check out faster.') };
@endphp

@section('title', $heading)

@if($role === 'vendor')
    @section('pitch_title', __('Sell to customers across India.'))
    @section('pitch_text', __('List your furniture, get orders and receive payouts, with GST-ready invoicing handled for you.'))
@endif

@section('content')
    <h1 class="auth-title">{{ $heading }}</h1>
    <p class="text-muted mb-4">{{ $sub }}</p>

    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if(session('email_verification_notice'))<div class="alert alert-info">{{ session('email_verification_notice') }} <a href="{{ route('verification.notice') }}">{{ __('Verify email') }}</a></div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif

@php $otpReady = \App\Support\FirebaseWeb::config() !== null; @endphp
@if($otpReady)
    <div class="btn-group w-100 mb-4" role="tablist" data-auth-tabs>
        <button type="button" class="btn btn-outline-secondary active" role="tab" aria-selected="true" data-auth-tab="password">{{ __('Email and password') }}</button>
        <button type="button" class="btn btn-outline-secondary" role="tab" aria-selected="false" data-auth-tab="otp">{{ __('Mobile OTP') }}</button>
    </div>
@endif
<div data-auth-pane="password">
    <form action="{{ $action ?? route('login.post') }}" method="POST" data-auth-form>
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label fw-semibold">{{ __('Email') }}</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control form-control-lg @error('email') is-invalid @enderror" placeholder="you@example.com" autocomplete="email" autofocus required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label fw-semibold">{{ __('Password') }}</label>
            <div class="input-group">
                <input type="password" id="password" name="password" class="form-control form-control-lg" placeholder="{{ __('Your password') }}" autocomplete="current-password" required>
                <button class="btn btn-outline-secondary" type="button" data-toggle-password="password" aria-label="{{ __('Show password') }}"><i class="bi bi-eye"></i></button>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check"><input class="form-check-input" type="checkbox" id="remember" name="remember" value="1"><label class="form-check-label" for="remember">{{ __('Keep me signed in') }}</label></div>
            <a href="{{ route('password.request') }}" class="small">{{ __('Forgot password?') }}</a>
        </div>
        <button type="submit" class="btn btn-primary btn-lg w-100">{{ __('Sign in') }}</button>
    </form>
</div>
@if($otpReady)
    <div data-auth-pane="otp" hidden>
        <form method="POST" action="{{ $role === 'vendor' ? route('otp.vendor.login') : route('otp.customer.login') }}">
            @csrf
            @include('partials.firebase-phone', ['mode' => 'submit'])
        </form>
        <p class="small text-muted mt-3 mb-0">{{ __('Use the mobile number on your account. We send a 6-digit code by SMS.') }}</p>
    </div>
@endif

    <p class="text-center mt-4 mb-2">
        @if($role === 'vendor')
            New seller? <a href="{{ route('vendor.register') }}" class="fw-semibold">{{ __('Apply to sell') }}</a>
        @else
            New here? <a href="{{ route('register') }}" class="fw-semibold">{{ __('Create an account') }}</a>
        @endif
    </p>
    <p class="text-center small text-muted mb-0">
        @if($role === 'vendor')
            Shopping instead? <a href="{{ route('login') }}">{{ __('Customer sign in') }}</a>
        @else
            Selling with us? <a href="{{ route('vendor.login') }}">{{ __('Seller sign in') }}</a>
        @endif
    </p>
@endsection
