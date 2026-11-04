@extends('layouts.auth')

@section('title', 'Admin sign in')
@section('pitch_title', 'Store administration')
@section('pitch_text', 'Catalogue, orders, vendors, shipping and GST invoicing in one place.')
@section('pitch_points')
    <li><i class="bi bi-shield-lock"></i> Authorised staff only</li>
    <li><i class="bi bi-receipt"></i> Invoices and credit notes</li>
@endsection

@section('content')
    <h1 class="auth-title">Admin sign in</h1>
    <p class="text-muted mb-4">Use your administrator account.</p>

    @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif

    <form action="{{ route('admin.login.post') }}" method="POST" data-auth-form>
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label fw-semibold">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control form-control-lg @error('email') is-invalid @enderror" autocomplete="username" autofocus required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label fw-semibold">Password</label>
            <div class="input-group">
                <input type="password" id="password" name="password" class="form-control form-control-lg" autocomplete="current-password" required>
                <button class="btn btn-outline-secondary" type="button" data-toggle-password="password" aria-label="Show password"><i class="bi bi-eye"></i></button>
            </div>
        </div>
        <div class="form-check mb-4"><input class="form-check-input" type="checkbox" id="remember" name="remember" value="1"><label class="form-check-label" for="remember">Keep me signed in</label></div>
        <button type="submit" class="btn btn-primary btn-lg w-100">Sign in</button>
    </form>
    <p class="text-center small text-muted mt-4 mb-0"><a href="{{ route('home') }}">Back to store</a></p>
@endsection
