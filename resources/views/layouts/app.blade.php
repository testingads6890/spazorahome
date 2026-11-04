<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locales::direction() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="{{ \App\Support\Theme::secondary() }}">
    <meta name="description" content="@yield('meta_description', \App\Support\Seo::description())">
    <meta name="keywords" content="@yield('meta_keywords', \App\Support\Seo::keywords())">
    @if($__robots = \App\Support\Seo::robotsMeta())<meta name="robots" content="{{ $__robots }}">@endif
    @if($__g = \App\Support\Seo::verification('google_site_verification'))<meta name="google-site-verification" content="{{ $__g }}">@endif
    @if($__b = \App\Support\Seo::verification('bing_site_verification'))<meta name="msvalidate.01" content="{{ $__b }}">@endif
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', \App\Support\Seo::get('site_title', 'SpazoraHome'))</title>
    <link rel="canonical" href="@yield('canonical', url()->current() . ((int) request('page') > 1 ? '?page=' . (int) request('page') : ''))">
    {{-- How the page looks when shared on WhatsApp, Facebook, LinkedIn or X. --}}
    <meta property="og:site_name" content="{{ config('shop.seller.name') ?: 'SpazoraHome' }}">
    <meta property="og:locale" content="{{ app()->getLocale() === 'hi' ? 'hi_IN' : 'en_IN' }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('title', 'SpazoraHome')">
    <meta property="og:description" content="@yield('meta_description', \App\Support\Seo::description())">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', \App\Support\Seo::get('og_image') ?: \App\Support\StockPhotos::url((string) config('product_photos.home.hero', '1617806118233-18e1de247200'), 1200, 630))">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://images.unsplash.com">
    {{-- The Devanagari font is only fetched when the page is in Hindi. --}}
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800{{ app()->getLocale() === 'hi' ? '&family=Noto+Sans+Devanagari:wght@400;500;600;700' : '' }}&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- Icons are not needed for the first paint, so they load without holding the page back. --}}
    <link rel="preload" as="style" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"></noscript>
    <link rel="stylesheet" href="{{ asset('css/storefront.css') }}?v={{ filemtime(public_path('css/storefront.css')) }}">
    @if($__ga = \App\Support\Seo::analyticsId())
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $__ga }}"></script>
        <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','{{ $__ga }}');</script>
    @endif
    @stack('head')
    @include('partials.theme-vars')
</head>
<body>
    @include('components.header')

    <main id="content">
        @yield('content')
    </main>

    @include('components.footer')

    <div class="modal fade" id="quickView" tabindex="-1" aria-labelledby="quickViewTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable"><div class="modal-content" style="border-radius:18px"><div class="modal-body p-0" id="quickViewBody"></div></div></div>
    </div>

    <a href="#" class="back-to-top" id="backToTop" aria-label="Back to top"><i class="bi bi-arrow-up"></i></a>

    <script>window.MONEY = @json(\App\Support\Money::jsConfig());</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            var top = document.getElementById('backToTop');
            window.addEventListener('scroll', function () { top.classList.toggle('show', window.scrollY > 300); }, { passive: true });
            top.addEventListener('click', function (e) { e.preventDefault(); window.scrollTo({ top: 0, behavior: 'smooth' }); });

            // Quick view popup: fetch the product fragment into a modal
            var qvEl = document.getElementById('quickView'), qvBody = document.getElementById('quickViewBody'), qvModal = null;
            document.addEventListener('click', function (e) {
                var b = e.target.closest('[data-quick-view]'); if (!b) return;
                e.preventDefault();
                qvModal = qvModal || new bootstrap.Modal(qvEl);
                qvBody.innerHTML = '<div class="p-5 text-center"><div class="spinner-border" role="status"></div></div>';
                qvModal.show();
                fetch(b.dataset.quickView, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
                    .then(function (html) { qvBody.innerHTML = html; bindQty(qvBody); })
                    .catch(function () { qvBody.innerHTML = '<div class="p-5 text-center text-muted">Could not load this product. <a href="' + b.closest('.pcard').querySelector('a.name').href + '">Open the product page</a></div>'; });
            });

            // The second product picture is only fetched when a mouse actually hovers a card (never on phones).
            document.addEventListener('pointerover', function (e) {
                if (e.pointerType !== 'mouse') return;
                var card = e.target.closest && e.target.closest('.pcard'); if (!card) return;
                var alt = card.querySelector('img.alt[data-src]'); if (!alt) return;
                alt.src = alt.dataset.src; alt.removeAttribute('data-src');
            }, { passive: true });

            // Quantity steppers (product page and quick view)
            function bindQty(root) { root.querySelectorAll('[data-qty]').forEach(function (box) {
                if (box.dataset.bound) return; box.dataset.bound = '1';
                var input = box.querySelector('input');
                box.querySelectorAll('button').forEach(function (b) {
                    b.addEventListener('click', function () {
                        var v = parseInt(input.value || '1', 10) + parseInt(b.dataset.step, 10);
                        var max = parseInt(input.max || '99', 10);
                        input.value = Math.min(Math.max(v, 1), max);
                    });
                });
            }); }
            bindQty(document);

            // Sticky bars sit right under the header, whatever its height at this width.
            var header = document.querySelector('.site-header');
            function stickyTop() { document.documentElement.style.setProperty('--sticky-top', (header ? header.offsetHeight : 0) + 'px'); }
            stickyTop(); window.addEventListener('resize', stickyTop, { passive: true });

            // Rails: arrows page the row; each arrow shows only when there is more that way.
            document.querySelectorAll('[data-rail]').forEach(function (rail) {
                var track = rail.querySelector('[data-rail-track]'), prev = rail.querySelector('[data-rail-prev]'), next = rail.querySelector('[data-rail-next]');
                if (!track || !prev || !next) return;
                function sync() {
                    var max = track.scrollWidth - track.clientWidth;
                    prev.hidden = track.scrollLeft < 8; next.hidden = track.scrollLeft > max - 8;
                }
                function page(dir) { track.scrollBy({ left: dir * track.clientWidth * .85, behavior: 'smooth' }); }
                prev.addEventListener('click', function () { page(-1); }); next.addEventListener('click', function () { page(1); });
                track.addEventListener('scroll', sync, { passive: true }); window.addEventListener('resize', sync, { passive: true });
                var on = track.querySelector('.active'); if (on) track.scrollLeft = Math.max(0, on.offsetLeft - 40);
                sync();
            });
        })();
    </script>
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
    <script>
        window.SEARCH_I18N = {!! json_encode([
            'recent_searches' => __('Recent searches'),
            'matching' => __('Matching your searches'),
            'clear' => __('Clear'),
            'voice_search' => __('Search by voice'),
            'listening' => __('Listening…'),
            'mic_blocked' => __('Microphone is blocked. Allow it in your browser settings to use voice search.'),
            'no_speech' => __('We could not hear anything. Please try again.'),
            'voice_failed' => __('Voice search is not available right now. Please type your search.'),
        ], JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) !!};
    </script>
    <script src="{{ asset('js/search-assist.js') }}?v={{ filemtime(public_path('js/search-assist.js')) }}" defer></script>
    @include('components.floating-contact')
</body>
</html>
