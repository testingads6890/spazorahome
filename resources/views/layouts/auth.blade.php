<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locales::direction() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="{{ \App\Support\Theme::secondary() }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', __('Sign in')) - Spazora Home</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Noto+Sans+Devanagari:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/storefront.css') }}?v={{ filemtime(public_path('css/storefront.css')) }}">
    <style>body { padding-bottom: 0; }</style>
    @include('partials.theme-vars')
</head>
<body>
<div class="auth-shell">
    <aside class="auth-side">
        <a class="brand text-white" href="{{ route('home') }}"><span class="mark" style="background:var(--accent)"><i class="bi bi-house-heart-fill"></i></span><span>InteriorFurniture</span></a>
        <div class="auth-pitch">
            <h2>@yield('pitch_title', __('Make every room feel like home.'))</h2>
            <p>@yield('pitch_text', __('Sofas, beds, tables and decor from trusted makers, delivered across India with a GST invoice on every order.'))</p>
            <ul class="list-unstyled d-grid gap-2 mt-4 mb-0">
                @section('pitch_points')
                    <li><i class="bi bi-truck"></i> {{ __('Free delivery above :amount', ['amount' => '₹' . number_format(config('shop.free_shipping_over'))]) }}</li>
                    <li><i class="bi bi-arrow-repeat"></i> {{ __('Easy returns') }}</li>
                    <li><i class="bi bi-shield-check"></i> {{ __('Secure payments and COD') }}</li>
                @show
            </ul>
        </div>
        <small class="text-white-50">&copy; {{ date('Y') }} {{ config('shop.seller.name') }}</small>
    </aside>

    <main class="auth-main">
        <div class="auth-card">
            <a class="brand d-lg-none mb-4" href="{{ route('home') }}"><span class="mark"><i class="bi bi-house-heart-fill"></i></span><span>Interior<span style="color:var(--accent)">Furniture</span></span></a>
            @yield('content')
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Show / hide password toggles and double-submit protection.
    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.dataset.togglePassword);
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.querySelector('i').className = 'bi ' + (show ? 'bi-eye-slash' : 'bi-eye');
            btn.setAttribute('aria-label', show ? @json(__('Hide password')) : @json(__('Show password')));
        });
    });
    document.querySelectorAll('form[data-auth-form]').forEach(function (f) {
        f.addEventListener('submit', function () {
            var b = f.querySelector('[type=submit]');
            if (b && f.checkValidity()) { b.disabled = true; b.innerHTML = '<span class="spinner-border spinner-border-sm"></span> ' + @json(__('Please wait...')); }
        });
    });
</script>
@stack('scripts')
</body>
</html>
