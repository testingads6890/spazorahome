@extends('layouts.auth')

@section('title', __('Create your account'))

@section('content')
    <h1 class="auth-title">{{ __('Create your account') }}</h1>
    <p class="text-muted mb-4">{{ __('Join to track orders, save favourites and check out faster.') }}</p>

    @if($errors->any())<div class="alert alert-danger" role="alert">{{ __('Please fix the highlighted fields.') }}</div>@endif

@php $otpReady = \App\Support\FirebaseWeb::config() !== null; @endphp
@if($otpReady)
    <div class="btn-group w-100 mb-4" role="tablist" data-auth-tabs>
        <button type="button" class="btn btn-outline-secondary active" role="tab" aria-selected="true" data-auth-tab="password">{{ __('Email and password') }}</button>
        <button type="button" class="btn btn-outline-secondary" role="tab" aria-selected="false" data-auth-tab="otp">{{ __('Mobile OTP') }}</button>
    </div>
@endif
<div data-auth-pane="password">
    <form action="{{ $action ?? route('register.post') }}" method="POST" data-auth-form novalidate>
        @csrf
        <div class="mb-3">
            <label for="name" class="form-label fw-semibold">{{ __('Full name') }}</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-control form-control-lg @error('name') is-invalid @enderror" autocomplete="name" autofocus required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="email" class="form-label fw-semibold">{{ __('Email') }}</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control form-control-lg @error('email') is-invalid @enderror" autocomplete="email" required>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="phone" class="form-label fw-semibold">{{ __('Mobile number') }} @unless($phoneRequired ?? false)<span class="text-muted fw-normal">{{ __('(optional)') }}</span>@endunless</label>
            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" class="form-control form-control-lg @error('phone') is-invalid @enderror" inputmode="tel" autocomplete="tel" {{ ($phoneRequired ?? false) ? 'required' : '' }}>
            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="whatsapp" class="form-label fw-semibold">{{ __('WhatsApp number') }} <span class="text-muted fw-normal">{{ __('(optional)') }}</span></label>
            <input type="tel" id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}" class="form-control form-control-lg @error('whatsapp') is-invalid @enderror" inputmode="tel" placeholder="+91 98xxxxxxxx">
            @error('whatsapp')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="other_phone" class="form-label fw-semibold">{{ __('Other phone number') }} <span class="text-muted fw-normal">{{ __('(optional)') }}</span></label>
            <input type="tel" id="other_phone" name="other_phone" value="{{ old('other_phone') }}" class="form-control form-control-lg @error('other_phone') is-invalid @enderror" inputmode="tel">
            @error('other_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        @php $acct = old('account_type', 'homeowner'); @endphp
        <fieldset class="mb-3">
            <legend class="form-label fw-semibold fs-6">{{ __('I am joining as') }}</legend>
            <div class="d-flex flex-wrap gap-2" role="radiogroup">
                @foreach(['homeowner' => [__('Homeowner'), 'bi-house'], 'interior_designer' => [__('Interior designer'), 'bi-palette'], 'architect' => [__('Architect'), 'bi-rulers']] as $val => [$lbl, $ico])
                    <input type="radio" class="btn-check" name="account_type" id="acct_{{ $val }}" value="{{ $val }}" autocomplete="off" @checked($acct === $val) data-acct>
                    <label class="btn btn-outline-secondary" for="acct_{{ $val }}"><i class="bi {{ $ico }} me-1"></i>{{ $lbl }}</label>
                @endforeach
            </div>
            @error('account_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </fieldset>
        <div class="mb-3" id="proFields" @if($acct === 'homeowner') hidden @endif>
            <label for="company_name" class="form-label fw-semibold">{{ __('Studio or company name') }}</label>
            <input type="text" id="company_name" name="company_name" value="{{ old('company_name') }}" class="form-control form-control-lg @error('company_name') is-invalid @enderror" autocomplete="organization">
            @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <p class="small text-muted mt-1 mb-0">{{ __('Professionals get trade pricing and project support once our team verifies the profile.') }}</p>
        </div>
        <script>
            document.querySelectorAll('[data-acct]').forEach(function (r) {
                r.addEventListener('change', function () { document.getElementById('proFields').hidden = this.value === 'homeowner'; });
            });
        </script>
        <div class="row g-3 mb-2">
            <div class="col-sm-6">
                <label for="password" class="form-label fw-semibold">{{ __('Password') }}</label>
                <div class="input-group has-validation">
                    <input type="password" id="password" name="password" class="form-control form-control-lg @error('password') is-invalid @enderror" autocomplete="new-password" required>
                    <button class="btn btn-outline-secondary" type="button" data-toggle-password="password" aria-label="{{ __('Show password') }}"><i class="bi bi-eye"></i></button>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col-sm-6">
                <label for="password_confirmation" class="form-label fw-semibold">{{ __('Confirm password') }}</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control form-control-lg" autocomplete="new-password" required>
            </div>
        </div>
        <p class="small text-muted mb-4">{{ __('Use at least 8 characters.') }}</p>
        <button type="submit" class="btn btn-primary btn-lg w-100">{{ __('Create account') }}</button>
        <p class="small text-muted text-center mt-3 mb-0">{{ __('By creating an account you agree to our') }} <a href="{{ route('terms') }}">{{ __('terms') }}</a> {{ __('and') }} <a href="{{ route('privacy') }}">{{ __('privacy policy') }}</a>.</p>
    </form>
</div>
@if($otpReady)
    <div data-auth-pane="otp" hidden>
        <form method="POST" action="{{ route('otp.customer.register') }}">
            @csrf
            <div class="mb-3">
                <label for="otp_name" class="form-label fw-semibold">{{ __('Full name') }}</label>
                <input type="text" id="otp_name" name="name" value="{{ old('name') }}" class="form-control form-control-lg @error('name') is-invalid @enderror" autocomplete="name" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            @include('partials.firebase-phone', ['mode' => 'submit'])
        </form>
        <p class="small text-muted mt-3 mb-0">{{ __('No password needed. Your verified mobile number is how you sign in. You can add an email later.') }}</p>
    </div>
@endif

    <p class="text-center mt-4 mb-0">{{ __('Already have an account?') }} <a href="{{ route('login') }}" class="fw-semibold">{{ __('Sign in') }}</a></p>
@endsection
