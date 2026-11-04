@extends('layouts.auth')

@section('title', __('Verify your email'))

@section('content')
    <h1 class="auth-title">{{ __('Verify your email address') }}</h1>
    <p class="text-muted mb-4">{{ __('Before continuing, please check your inbox for a verification link. If you did not receive the email, you can request another one below.') }}</p>

    @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn btn-primary btn-lg w-100">{{ __('Resend verification email') }}</button>
    </form>
    <p class="text-center mt-3 mb-0"><a href="{{ route('dashboard') }}">{{ __('Continue to dashboard') }}</a></p>
@endsection
