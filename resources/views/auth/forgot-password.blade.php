@extends('layouts.auth')

@section('title', __('Forgot password'))

@section('content')
    <h1 class="auth-title">{{ __('Forgot password') }}</h1>
    <p class="text-muted mb-4">{{ __('Enter your email and we will send you a link to choose a new password. This also works if you checked out as a guest.') }}</p>

    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif

    <form action="{{ route('password.email') }}" method="POST" data-auth-form>
        @csrf
        <div class="mb-4">
            <label for="email" class="form-label fw-semibold">{{ __('Email') }}</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control form-control-lg @error('email') is-invalid @enderror" placeholder="you@example.com" autocomplete="email" autofocus required>
        </div>
        <button type="submit" class="btn btn-primary btn-lg w-100">{{ __('Email me a link') }}</button>
    </form>

    <p class="text-center mt-4 mb-0"><a href="{{ route('login') }}" class="fw-semibold">{{ __('Back to sign in') }}</a></p>
@endsection
