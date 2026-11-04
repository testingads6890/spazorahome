@extends('layouts.account')

@section('title', __('Profile - SpazoraHome'))
@section('heading', __('My profile'))
@section('crumb', __('Profile'))

@php $profile = auth()->user()->profile; @endphp

@section('account')
    <div class="card card-body mb-3">
        <form method="POST" action="{{ route('customer.profile.update') }}">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="name">{{ __('Full name') }}</label>
                    <input type="text" id="name" name="name" value="{{ old('name', auth()->user()->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="email">{{ __('Email') }}</label>
                    <input type="email" id="email" name="email" value="{{ old('email', auth()->user()->email) }}" class="form-control @error('email') is-invalid @enderror" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="phone">{{ __('Mobile number') }}</label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone', $profile?->phone) }}" class="form-control @error('phone') is-invalid @enderror">
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="whatsapp">{{ __('WhatsApp number') }}</label>
                    <input type="tel" id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $profile?->whatsapp) }}" class="form-control @error('whatsapp') is-invalid @enderror" placeholder="+91 98xxxxxxxx">
                    @error('whatsapp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="other_phone">{{ __('Other phone number') }}</label>
                    <input type="tel" id="other_phone" name="other_phone" value="{{ old('other_phone', $profile?->other_phone) }}" class="form-control @error('other_phone') is-invalid @enderror">
                    @error('other_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" for="date_of_birth">{{ __('Date of birth') }}</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', $profile?->date_of_birth?->format('Y-m-d')) }}" class="form-control @error('date_of_birth') is-invalid @enderror">
                    @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" for="gender">{{ __('Gender') }}</label>
                    <select id="gender" name="gender" class="form-select @error('gender') is-invalid @enderror">
                        <option value="">{{ __('Prefer not to say') }}</option>
                        @foreach(['male' => __('Male'), 'female' => __('Female'), 'other' => __('Other')] as $k => $l)
                            <option value="{{ $k }}" {{ old('gender', $profile?->gender) === $k ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                    @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <button class="btn btn-primary mt-4">{{ __('Save changes') }}</button>
        </form>
    </div>
    <div class="card card-body mb-3" id="password">
        <h5 class="mb-1">{{ auth()->user()->is_guest ? __('Create a password') : __('Change password') }}</h5>
        @if(auth()->user()->is_guest)<p class="small text-muted">{{ __('You checked out as a guest. Create a password so you can sign in later and see your orders.') }}</p>@endif
        <form method="POST" action="{{ route('customer.password.update') }}">
            @csrf @method('PUT')
            <div class="row g-3">
                @unless(auth()->user()->is_guest)
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" for="current_password">{{ __('Current password') }}</label>
                        <input type="password" id="current_password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" autocomplete="current-password" required>
                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @endunless
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="new_password">{{ __('New password') }}</label>
                    <input type="password" id="new_password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" required>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="password_confirmation">{{ __('Confirm password') }}</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" autocomplete="new-password" required>
                </div>
            </div>
            <p class="small text-muted mt-2 mb-0">{{ __('Use at least 8 characters.') }}</p>
            <button class="btn btn-primary mt-3">{{ __('Save password') }}</button>
        </form>
    </div>
    <div class="card card-body d-flex flex-row flex-wrap justify-content-between align-items-center gap-2">
        <div><strong>{{ __('Interior professional?') }}</strong><div class="small text-muted">{{ __('Designers and architects can add their business details.') }}</div></div>
        <a href="{{ route('customer.professional-profile.edit') }}" class="btn btn-outline-primary btn-sm">{{ __('Professional profile') }}</a>
    </div>
@endsection
