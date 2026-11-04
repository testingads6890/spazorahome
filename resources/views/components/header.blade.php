@php
    // Safe defaults keep the shared header usable across public and customer pages.
    $freeOver = (float) config('shop.free_shipping_over', 0);
    $q = (string) request('q', '');
    $onCategory = request()->routeIs('search')
        ? (string) request()->route('category', '')
        : '';

    $navCategories = $navCategories ?? collect();
    $headerLinks = $headerLinks ?? [];
    $wishlistCount = (int) ($wishlistCount ?? 0);
    $cartCount = (int) ($cartCount ?? 0);
@endphp
<div class="announce">
    <div class="container">
        <div class="promo"><i class="bi bi-truck"></i> {{ __('shop.nav.free_delivery', ['amount' => '₹' . number_format($freeOver)]) }}</div>
        <div class="side">
            @include('components.language-switch', ['id' => 'top', 'class' => 'currency-select'])
            @include('components.currency-switch', ['id' => 'top', 'class' => 'currency-select'])
            <a href="{{ route('faq') }}">{{ __('shop.nav.help') }}</a>
            <a href="{{ route('customer.order-tracking.index') }}">{{ __('shop.nav.track_order') }}</a>
            @if(\App\Support\Stores::enabled())<a href="{{ route('stores.index') }}">{{ __('shop.nav.stores') }}</a>@endif
            <a href="{{ route('bulk-enquiry.create') }}">{{ __('Bulk enquiry') }}</a>
            <a href="{{ route('vendor.register') }}">{{ __('shop.nav.sell_with_us') }}</a>
            @foreach($headerLinks as $link)
                <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
            @endforeach
        </div>
    </div>
</div>

<header class="site-header">
    <div class="container">
        <div class="header-main">
            <button class="icon-btn d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-label="{{ __('shop.nav.open_menu') }}"><i class="bi bi-list"></i></button>
         <a class="brand" href="{{ route('home') }}">
    <img src="{{ \App\Support\Theme::logoUrl() }}"
         alt="{{ \App\Support\Seo::get('site_title', 'SpazoraHome') }} logo"
         class="brand-logo">
</a>
            <!--<a class="brand" href="{{ route('home') }}"><span class="mark"><i class="bi bi-house-heart-fill"></i></span><span>Interior<span style="color:var(--accent)">Furniture</span></span></a>-->

            <form class="header-search" action="{{ route('search') }}" method="GET" role="search">
                <div class="search-box">
                    <select name="category" aria-label="{{ __('shop.nav.category') }}">
                        <option value="">{{ __('shop.nav.all') }}</option>
                        @foreach($navCategories as $c)
                            <option value="{{ $c->urlSlug() }}" {{ $onCategory === $c->urlSlug() ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('shop.nav.search_desktop') }}" aria-label="{{ __('shop.nav.search_products') }}">
                    <button type="submit" aria-label="{{ __('shop.nav.search') }}"><i class="bi bi-search"></i></button>
                </div>
            </form>

            <div class="header-actions">
               
                <a class="icon-btn only-desktop" href="{{ route('customer.wishlists.index') }}" aria-label="{{ __('shop.nav.wishlist') }}" title="{{ __('shop.nav.wishlist') }}"><i class="bi bi-heart"></i>@if($wishlistCount)<span class="count">{{ $wishlistCount }}</span>@endif</a>
                <a class="icon-btn" href="{{ route('customer.cart.index') }}" aria-label="{{ __('shop.nav.cart') }}" title="{{ __('shop.nav.cart') }}"><i class="bi bi-bag"></i>@if($cartCount)<span class="count">{{ $cartCount }}</span>@endif</a>
                @guest
                    <a class="account-link" href="{{ route('login') }}"><i class="bi bi-person-circle fs-5"></i> {{ __('shop.nav.sign_in') }}</a>
                @else
                    <div class="dropdown">
                        <a class="account-link" href="{{ route('dashboard') }}" role="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false"><i class="bi bi-person-circle fs-5"></i> {{ \Illuminate\Support\Str::words(auth()->user()->name, 1, '') }} <i class="bi bi-chevron-down small"></i></a>
                        <div class="dropdown-menu dropdown-menu-end shadow border-0 acc-dropdown">
                            <div class="acc-head"><small>{{ __('Hello') }}</small><strong class="d-block text-truncate">{{ auth()->user()->name }}</strong></div>
                            @include('partials.account-menu', ['class' => 'compact flush'])
                        </div>
                    </div>
                @endguest
            </div>
        </div>

        <form class="mobile-search" action="{{ route('search') }}" method="GET" role="search">
            <div class="search-box">
                <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('shop.nav.search_mobile') }}" aria-label="{{ __('shop.nav.search_products') }}">
                <button type="submit" aria-label="{{ __('shop.nav.search') }}"><i class="bi bi-search"></i></button>
            </div>
        </form>
    </div>

    <nav class="cat-nav" aria-label="{{ __('shop.nav.categories') }}">
        <div class="container">
            <a class="all" href="{{ route('search') }}"><i class="bi bi-grid-3x3-gap"></i> {{ __('shop.nav.all_products') }}</a>
            @foreach($navCategories as $c)
                <a href="{{ $c->shopUrl() }}" class="{{ $onCategory === $c->urlSlug() ? 'active' : '' }}" @if($onCategory === $c->urlSlug()) aria-current="page" @endif>{{ $c->name }}</a>
            @endforeach
        </div>
    </nav>
</header>

<div class="offcanvas offcanvas-start m-menu" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="mobileMenuLabel">{{ __('shop.nav.menu') }}</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="menu-list">
            <a href="{{ route('home') }}">{{ __('shop.nav.home') }}</a>

            <a href="#mm-pages" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="mm-pages">{{ __('shop.nav.pages') }} <i class="bi bi-plus-lg"></i></a>
            <div class="collapse sub" id="mm-pages">
                <a href="{{ route('about-us') }}">{{ __('shop.nav.about_us') }}</a>
                @foreach($headerLinks as $link)
                    <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                @endforeach
            </div>

            <a href="#mm-products" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="mm-products">{{ __('Products') }} <i class="bi bi-plus-lg"></i></a>
            <div class="collapse sub" id="mm-products">
                <a href="{{ route('search') }}">{{ __('shop.nav.all_products') }}</a>
                @foreach($navCategories as $c)
                    <a href="{{ $c->shopUrl() }}">{{ $c->name }}</a>
                @endforeach
            </div>

            @if(\App\Support\Stores::enabled())<a href="{{ route('stores.index') }}">{{ __('shop.nav.stores') }}</a>@endif
            <a href="{{ route('bulk-enquiry.create') }}">{{ __('Bulk enquiry') }}</a>
            <a href="{{ route('blog.index') }}">{{ __('Blog') }}</a>
            <a href="{{ route('faq') }}">{{ __('FAQs') }}</a>
            <a href="{{ route('contact-us') }}">{{ __('shop.nav.contact') }}</a>
            <a href="{{ route('customer.order-tracking.index') }}" class="with-ico"><i class="bi bi-check2-square"></i> {{ __('Track your order') }}</a>
            <a href="{{ route('customer.wishlists.index') }}" class="with-ico"><i class="bi bi-heart"></i> {{ __('shop.nav.wishlist') }}</a>

            @if(\App\Support\Money::switchable())
                <a href="#mm-currency" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="mm-currency">{{ \App\Support\Money::code() }} <i class="bi bi-plus-lg"></i></a>
                <div class="collapse sub" id="mm-currency">
                    @foreach(\App\Support\Money::options() as $c)
                        <form action="{{ route('currency.switch') }}" method="POST">@csrf<input type="hidden" name="currency" value="{{ $c->code }}"><button type="submit" class="{{ \App\Support\Money::code() === $c->code ? 'on' : '' }}">{{ $c->code }} {{ $c->symbol }}</button></form>
                    @endforeach
                </div>
            @endif
            @if(\App\Support\Locales::switchable())
                <a href="#mm-lang" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="mm-lang">{{ collect(\App\Support\Locales::available())->firstWhere('code', \App\Support\Locales::current())->name ?? __('shop.nav.language') }} <i class="bi bi-plus-lg"></i></a>
                <div class="collapse sub" id="mm-lang">
                    @foreach(\App\Support\Locales::available() as $l)
                        <form action="{{ route('language.switch') }}" method="POST">@csrf<input type="hidden" name="language" value="{{ $l->code }}"><button type="submit" class="{{ \App\Support\Locales::current() === $l->code ? 'on' : '' }}">{{ $l->name }}</button></form>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<nav class="bottom-bar" aria-label="{{ __('shop.nav.quick_navigation') }}">
    <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}" @if(request()->routeIs('home')) aria-current="page" @endif><i class="bi bi-house"></i>{{ __('shop.nav.home') }}</a>
    <a href="{{ route('search') }}" class="{{ request()->routeIs('search') ? 'active' : '' }}" @if(request()->routeIs('search')) aria-current="page" @endif><i class="bi bi-grid"></i>{{ __('shop.nav.shop') }}</a>
    <a href="{{ route('customer.cart.index') }}" class="{{ request()->routeIs('customer.cart.*') ? 'active' : '' }}" @if(request()->routeIs('customer.cart.*')) aria-current="page" @endif><i class="bi bi-bag"></i>{{ __('shop.nav.cart') }} @if($cartCount)<span class="count">{{ $cartCount }}</span>@endif</a>
    @auth
        <a href="{{ route('dashboard') }}" data-bs-toggle="offcanvas" data-bs-target="#accountSheet" aria-controls="accountSheet" class="{{ request()->routeIs('customer.*') && ! request()->routeIs('customer.cart.*') ? 'active' : '' }}"><i class="bi bi-person"></i>{{ __('shop.nav.account') }}</a>
    @else
        <a href="{{ route('login') }}" class="{{ request()->routeIs('login') ? 'active' : '' }}"><i class="bi bi-person"></i>{{ __('shop.nav.sign_in') }}</a>
    @endauth
</nav>

@auth
{{-- Mobile: tapping "Account" in the bottom bar opens the account menu, Logout included. --}}
<div class="offcanvas offcanvas-bottom" tabindex="-1" id="accountSheet" aria-labelledby="accountSheetLabel">
    <div class="offcanvas-header acc-user">
        <span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
        <div class="flex-grow-1 min-w-0 ms-2"><small>{{ __('Hello') }}</small><strong id="accountSheetLabel" class="d-block text-truncate">{{ auth()->user()->name }}</strong></div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
    </div>
    <div class="offcanvas-body">
        @include('partials.account-menu', ['class' => 'compact flush'])
    </div>
</div>
@endauth
<style>
    .brand-logo {
    width: 70px;
    height: 70px;
    object-fit: contain;
    display: block;
}
</style>
