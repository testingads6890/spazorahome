@extends('layouts.auth')

@section('title', __('Choose a new password'))

@section('content')
    <h1 class="auth-title">{{ __('Choose a new password') }}</h1>
    <p class="text-muted mb-4">{{ __('Use at least 8 characters.') }}</p>

    @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif

    <form action="{{ route('password.update') }}" method="POST" data-auth-form>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="mb-3">
            <label for="email" class="form-label fw-semibold">{{ __('Email') }}</label>
            <input type="email" id="email" name="email" value="{{ old('email', $email) }}" class="form-control form-control-lg" autocomplete="email" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label fw-semibold">{{ __('New password') }}</label>
            <div class="input-group">
                <input type="password" id="password" name="password" class="form-control form-control-lg" autocomplete="new-password" autofocus required>
                <button class="btn btn-outline-secondary" type="button" data-toggle-password="password" aria-label="{{ __('Show password') }}"><i class="bi bi-eye"></i></button>
            </div>
        </div>
        <div class="mb-4">
            <label for="password_confirmation" class="form-label fw-semibold">{{ __('Confirm password') }}</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control form-control-lg" autocomplete="new-password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-lg w-100">{{ __('Save password') }}</button>
    </form>

    <p class="text-center mt-4 mb-0"><a href="{{ route('password.request') }}">{{ __('Ask for a new link') }}</a></p>
@endsection
