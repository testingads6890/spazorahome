<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="{{ \App\Support\Theme::secondary() }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - @yield('panel_name') - Spazora Home</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}?v={{ filemtime(public_path('css/panel.css')) }}">
    @stack('head')
    @include('partials.theme-vars')
</head>
<body class="panel">
@php
    $groups = $groups ?? [];
    $user = auth()->user();
    $initials = collect(explode(' ', trim($user?->name ?? 'U')))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
@endphp

<aside class="panel-sidebar" id="panelSidebar" aria-label="Main navigation">
    <a class="panel-brand" href="@yield('home_url', url('/'))">
        <span class="mark"><i class="bi bi-house-heart-fill"></i></span>
        <span>InteriorFurniture<small>@yield('panel_name')</small></span>
    </a>
    <nav class="panel-nav">
        @foreach($groups as $label => $items)
            <div class="group">{{ $label }}</div>
            @foreach($items as [$route, $text, $icon, $pattern, $badge])
                <a href="{{ route($route) }}" class="{{ request()->routeIs($pattern) ? 'active' : '' }}" @if(request()->routeIs($pattern)) aria-current="page" @endif>
                    <i class="bi {{ $icon }}"></i> {{ $text }}
                    @if($badge > 0)<span class="badge-count" title="{{ $badge }} need attention">{{ $badge > 99 ? '99+' : $badge }}</span>@endif
                </a>
            @endforeach
        @endforeach
    </nav>
    <!--<div class="panel-side-foot">-->
    <!--    <a href="{{ route('home') }}" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> View store</a>-->
    <!--</div>-->
</aside>
<div class="panel-backdrop" id="panelBackdrop"></div>

<div class="panel-main">
    <header class="panel-top">
        <button class="toggle" type="button" id="panelToggle" aria-label="Open menu" aria-controls="panelSidebar" aria-expanded="false"><i class="bi bi-list"></i></button>
        <h1>@yield('title', 'Dashboard')</h1>
        <div class="actions">
            <a href="{{ route('home') }}" class="btn btn-outline-primary btn-sm d-none d-sm-inline-flex" target="_blank" rel="noopener"><i class="bi bi-shop-window me-1"></i> Store</a>
            <div class="dropdown">
                <button class="btn p-0 border-0 d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu">
                    <span class="avatar">{{ $initials }}</span>
                    <span class="d-none d-md-block text-start lh-sm"><span class="d-block small fw-bold">{{ $user?->name }}</span><span class="d-block small text-muted">{{ $user?->email }}</span></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                    <!--<li><a class="dropdown-item" href="{{ route('home') }}">View store</a></li>-->
                    <!--<li><hr class="dropdown-divider"></li>-->
                    <li><form action="{{ route('logout') }}" method="POST">@csrf<button class="dropdown-item text-danger">Sign out</button></form></li>
                </ul>
            </div>
        </div>
    </header>

    @php $flashes = collect(['success' => 'success', 'status' => 'success', 'error' => 'danger', 'warning' => 'warning'])->filter(fn ($c, $k) => session()->has($k)); @endphp
    @if($flashes->isNotEmpty())
        <div class="panel-flash" role="status">
            @foreach($flashes as $key => $cls)
                <div class="alert alert-{{ $cls }} alert-dismissible fade show">{{ session($key) }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
            @endforeach
        </div>
    @endif

    <main class="panel-content" id="content">
        @yield('content')
    </main>
    <footer class="panel-foot">&copy; {{ date('Y') }} {{ config('shop.seller.name') }}</footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        // Off-canvas sidebar on phones and tablets
        var sb = document.getElementById('panelSidebar'), bd = document.getElementById('panelBackdrop'), tg = document.getElementById('panelToggle');
        function set(open) { sb.classList.toggle('open', open); bd.classList.toggle('show', open); tg.setAttribute('aria-expanded', open ? 'true' : 'false'); document.body.style.overflow = open ? 'hidden' : ''; }
        tg.addEventListener('click', function () { set(!sb.classList.contains('open')); });
        bd.addEventListener('click', function () { set(false); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') set(false); });
        sb.querySelectorAll('a').forEach(function (a) { a.addEventListener('click', function () { set(false); }); });

        // Wide tables scroll sideways inside their own box instead of stretching the page
        document.querySelectorAll('.panel-content table.table').forEach(function (t) {
            if (t.closest('.table-responsive, .table-wrap')) return;
            var w = document.createElement('div'); w.className = 'table-wrap'; t.parentNode.insertBefore(w, t); w.appendChild(t);
        });
        document.querySelectorAll('.table-responsive').forEach(function (w) { w.classList.add('table-wrap'); });

        // Flash messages fade away
        document.querySelectorAll('.panel-flash .alert-success').forEach(function (a) { setTimeout(function () { bootstrap.Alert.getOrCreateInstance(a).close(); }, 5000); });
    })();
</script>
@include('partials.richtext')
@yield('scripts')
<script>
    // One click, one submit: stops double orders, double payments and duplicate requests from a second tap.
    document.addEventListener('submit', function (e) {
        var f = e.target;
        if (!(f instanceof HTMLFormElement) || (f.method || '').toLowerCase() !== 'post' || f.hasAttribute('data-allow-resubmit')) return;
        if (f.dataset.sending === '1') { e.preventDefault(); return; }
        if (e.defaultPrevented) return;
        f.dataset.sending = '1';
        setTimeout(function () { f.querySelectorAll('button[type=submit], button:not([type]), input[type=submit]').forEach(function (b) { if (!b.disabled) { b.disabled = true; b.dataset.guard = '1'; } }); }, 0);
        // If the page stays (validation message, download), allow trying again.
        setTimeout(function () { f.dataset.sending = ''; f.querySelectorAll('[data-guard="1"]').forEach(function (b) { b.disabled = false; delete b.dataset.guard; }); }, 8000);
    }, false);
    window.addEventListener('pageshow', function (ev) { if (ev.persisted) document.querySelectorAll('form[data-sending="1"]').forEach(function (f) { f.dataset.sending = ''; f.querySelectorAll('[data-guard="1"]').forEach(function (b) { b.disabled = false; delete b.dataset.guard; }); }); });
</script>
@stack('scripts')
</body>
</html>
