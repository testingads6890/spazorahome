@extends('layouts.app')

@section('title', __('pages.home.title'))

@php
    $icons = ['bi-lamp', 'bi-house-door', 'bi-brightness-high', 'bi-box2', 'bi-stars', 'bi-tree', 'bi-palette', 'bi-grid-1x2'];
    $freeAmount = '₹' . number_format(config('shop.free_shipping_over'));
    $shopName = config('shop.seller.name') ?: 'SpazoraHome';

    // Category and room pictures may be a full address, a site path or a file in storage.
    $pic = fn (?string $v) => blank($v) ? null : (\Illuminate\Support\Str::startsWith($v, ['http://', 'https://', '/']) ? $v : asset('storage/' . $v));
    // A category with no picture of its own borrows a stock photo (config/product_photos.php).
    $catPic = fn ($c, int $w, int $h) => $pic($c->image) ?: \App\Support\StockPhotos::categoryUrl($c->name, $w, $h);

    // Home page pictures (config/product_photos.php > home). Each is served at the width the screen needs.
    $art = (array) config('product_photos.home', []);
    $src = fn (string $slot, int $w, float $ratio) => \App\Support\StockPhotos::url($art[$slot], $w, (int) round($w * $ratio));
    $set = fn (string $slot, array $widths, float $ratio) => implode(', ', array_map(fn ($w) => $src($slot, $w, $ratio) . ' ' . $w . 'w', $widths));

    $bySlug = $categories->keyBy(fn ($c) => $c->urlSlug());
    $lighting = $bySlug->get('lighting');
    $decor = $bySlug->get('decoration');
    $picks = ($featuredProducts->count() >= 2 ? $featuredProducts : $newArrivals)->take(2);
    $mosaic = $categories->sortByDesc('products_count')->take(5)->values();
    $heroSizes = '(min-width: 1200px) 560px, (min-width: 992px) 45vw, 92vw';
@endphp

@push('head')
    {{-- Who we are and how to search the shop, for search engines. --}}
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            ['@type' => 'Organization', 'name' => $shopName, 'url' => route('home')],
            ['@type' => 'WebSite', 'name' => $shopName, 'url' => route('home'), 'potentialAction' => ['@type' => 'SearchAction', 'target' => route('search') . '?q={search_term_string}', 'query-input' => 'required name=search_term_string']],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;1,500&display=swap" rel="stylesheet">
    @unless($slider)
        <link rel="preload" as="image" href="{{ $src('hero', 800, 1.2) }}" imagesrcset="{{ $set('hero', [480, 640, 800, 1120], 1.2) }}" imagesizes="{{ $heroSizes }}" fetchpriority="high">
    @endunless
    {{-- The home page styles travel with the page itself: one request fewer on a phone. Edit public/css/home.css. --}}
    <style>{!! preg_replace(['~/\*.*?\*/~s', '~\s+~'], ['', ' '], file_get_contents(public_path('css/home.css'))) !!}</style>
@endpush

@section('content')
<div class="hx">
@if($slider)
<section class="home-slider" aria-label="{{ $slider->name }}">
    <div id="homeSlider" class="carousel slide" data-bs-ride="carousel">
        @if(count($slider->slides) > 1)
            <div class="carousel-indicators">
                @foreach($slider->slides as $slide)
                    <button type="button" data-bs-target="#homeSlider" data-bs-slide-to="{{ $loop->index }}" class="{{ $loop->first ? 'active' : '' }}" aria-label="{{ $loop->iteration }}"></button>
                @endforeach
            </div>
        @endif
        <div class="carousel-inner">
            @foreach($slider->slides as $slide)
                <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                    <img src="{{ $slide['image'] }}" alt="{{ $slide['title'] ?? '' }}" @unless($loop->first) loading="lazy" @endunless>
                    @if(! empty($slide['link']) && empty($slide['button']))<a class="slide-link" href="{{ $slide['link'] }}" aria-label="{{ $slide['title'] ?? $slider->name }}"></a>@endif
                    @if(! empty($slide['title']) || ! empty($slide['text']) || ! empty($slide['button']))
                        <div class="slide-text">
                            @if(! empty($slide['title']))<h2>{{ $slide['title'] }}</h2>@endif
                            @if(! empty($slide['text']))<p>{{ $slide['text'] }}</p>@endif
                            @if(! empty($slide['button']) && ! empty($slide['link']))<a href="{{ $slide['link'] }}" class="btn btn-accent btn-lg">{{ $slide['button'] }} <i class="bi bi-arrow-right"></i></a>@endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        @if(count($slider->slides) > 1)
            <button class="carousel-control-prev" type="button" data-bs-target="#homeSlider" data-bs-slide="prev" aria-label="Previous"><span class="carousel-control-prev-icon" aria-hidden="true"></span></button>
            <button class="carousel-control-next" type="button" data-bs-target="#homeSlider" data-bs-slide="next" aria-label="Next"><span class="carousel-control-next-icon" aria-hidden="true"></span></button>
        @endif
    </div>
</section>
@else
{{-- 1. Opening: what the shop stands for, and one strong picture --}}
<section class="hx-hero">
    <div class="container">
        <div class="hx-hero-copy">
            <span class="hx-kicker">{{ __('pages.home.h_kicker') }}</span>
            <h1>{!! __('pages.home.h_title', ['word' => '<em>' . e(__('pages.home.h_title_word')) . '</em>']) !!}</h1>
            <p>{{ __('pages.home.h_text') }}</p>
            <div class="hx-cta">
                <a href="{{ route('search') }}" class="hx-btn">{{ __('pages.home.h_cta') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                <a href="{{ route('search', ['sort' => 'newest']) }}" class="hx-btn ghost">{{ __('pages.home.h_cta2') }}</a>
            </div>
            <dl class="hx-facts">
                <div><dt>{{ number_format($stats['products']) }}</dt><dd>{{ __('pages.home.h_fact_pieces') }}</dd></div>
                <div><dt>{{ $stats['categories'] }}</dt><dd>{{ __('pages.home.stat_categories') }}</dd></div>
                <div><dt>{{ $freeAmount }}</dt><dd>{{ __('pages.home.h_fact_delivery') }}</dd></div>
            </dl>
        </div>
        <div class="hx-hero-art">
            <img src="{{ $src('hero', 800, 1.2) }}" srcset="{{ $set('hero', [480, 640, 800, 1120], 1.2) }}" sizes="{{ $heroSizes }}" width="800" height="960" alt="" fetchpriority="high" decoding="async">
        </div>
    </div>
</section>
@endif

{{-- 2. Two doors into the collection --}}
<section class="hx-duo">
    <div class="container">
        <a class="hx-tile" href="{{ route('search', ['sort' => 'newest']) }}">
            <img src="{{ $src('tile_new', 640, .75) }}" srcset="{{ $set('tile_new', [400, 640, 900], .75) }}" sizes="(min-width: 768px) 46vw, 92vw" width="640" height="480" alt="" loading="lazy" decoding="async">
            <span class="hx-tile-text"><small>{{ __('pages.home.h_tile_new_kicker') }}</small><strong>{{ __('pages.home.h_tile_new') }}</strong><span class="hx-go">{{ __('pages.home.h_cta2') }} <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span></span>
        </a>
        <a class="hx-tile" href="{{ $decor ? $decor->shopUrl() : route('search') }}">
            <img src="{{ $src('tile_decor', 640, .75) }}" srcset="{{ $set('tile_decor', [400, 640, 900], .75) }}" sizes="(min-width: 768px) 46vw, 92vw" width="640" height="480" alt="" loading="lazy" decoding="async">
            <span class="hx-tile-text"><small>{{ __('pages.home.h_tile_decor_kicker') }}</small><strong>{{ __('pages.home.h_tile_decor') }}</strong><span class="hx-go">{{ __('pages.home.explore') }} <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span></span>
        </a>
    </div>
</section>

{{-- 3. Rooms --}}
@if($categories->isNotEmpty())
<section class="hx-section">
    <div class="container">
        <header class="hx-head">
            <div><span class="hx-kicker">{{ __('pages.home.h_rooms_kicker') }}</span><h2>{{ __('pages.home.h_rooms') }}</h2><p>{{ __('pages.home.h_rooms_text') }}</p></div>
            <a class="hx-link" href="{{ route('search') }}">{{ __('pages.home.view_all') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </header>
        <div class="rail" data-rail>
            <button type="button" class="rail-btn prev" data-rail-prev aria-label="{{ __('pages.home.previous') }}" hidden><i class="bi bi-chevron-left"></i></button>
            <div class="rail-track hx-rooms" data-rail-track>
                @foreach($categories as $c)
                    <a class="hx-room" href="{{ $c->shopUrl() }}">
                        <span class="hx-room-pic">@if($catPic($c, 480, 600))<img src="{{ $catPic($c, 480, 600) }}" width="480" height="600" alt="" loading="lazy" decoding="async">@else<i class="bi {{ $icons[$loop->index % count($icons)] }}" aria-hidden="true"></i>@endif</span>
                        <strong>{{ $c->name }}</strong>
                        <small>{{ trans_choice('pages.home.h_pieces', (int) $c->products_count, ['count' => number_format((int) $c->products_count)]) }}</small>
                    </a>
                @endforeach
            </div>
            <button type="button" class="rail-btn next" data-rail-next aria-label="{{ __('pages.home.next') }}" hidden><i class="bi bi-chevron-right"></i></button>
        </div>
    </div>
</section>
@endif

{{-- 4. The edit: a short note and two pieces --}}
@if($picks->count() >= 2)
<section class="hx-section hx-edit">
    <div class="container">
        <div class="hx-edit-note">
            <span class="hx-kicker">{{ __('pages.home.h_edit_kicker') }}</span>
            <h2>{{ __('pages.home.h_edit') }}</h2>
            <p>{{ __('pages.home.h_edit_text') }}</p>
            <a class="hx-link" href="{{ route('search') }}">{{ __('pages.home.h_edit_link') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="hx-picks">
            @foreach($picks as $product)
                @include('components.product-card', ['product' => $product, 'factLimit' => 3])
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- 5. Happening now --}}
<section class="hx-section hx-tint">
    <div class="container">
        <header class="hx-head">
            <div><span class="hx-kicker">{{ __('pages.home.h_now_kicker') }}</span><h2>{{ __('pages.home.h_now') }}</h2></div>
        </header>
        <div class="rail" data-rail>
            <button type="button" class="rail-btn prev" data-rail-prev aria-label="{{ __('pages.home.previous') }}" hidden><i class="bi bi-chevron-left"></i></button>
            <div class="rail-track hx-now" data-rail-track>
                @if($deal && $deal->product)
                    <a class="hx-card is-deal" href="{{ route('products.show', $deal->product) }}">
                        <small>{{ __('pages.home.h_now_deal') }}</small>
                        <strong>{{ $deal->name }}</strong>
                        <span class="hx-price"><b>@price($deal->deal_price)</b> <s>@price($deal->original_price)</s></span>
                        <span class="hx-count" data-ends="{{ $deal->ends_at->toIso8601String() }}" role="timer" aria-label="{{ __('pages.home.time_left') }}"><b data-u="d">00</b>{{ __('pages.home.days') }} <b data-u="h">00</b>{{ __('pages.home.hours') }} <b data-u="m">00</b>{{ __('pages.home.minutes') }} <b data-u="s">00</b>{{ __('pages.home.seconds') }}</span>
                        <span class="hx-go">{{ __('pages.home.h_now_deal_cta') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                    </a>
                @endif
                <a class="hx-card" href="{{ route('shipping') }}"><i class="bi bi-truck" aria-hidden="true"></i><strong>{{ __('pages.home.h_now_delivery', ['amount' => $freeAmount]) }}</strong><p>{{ __('pages.home.h_now_delivery_text') }}</p><span class="hx-go">{{ __('pages.home.h_now_delivery_cta') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></span></a>
                <a class="hx-card dark" href="{{ route('contact-us') }}"><i class="bi bi-rulers" aria-hidden="true"></i><strong>{{ __('pages.home.h_now_trade') }}</strong><p>{{ __('pages.home.h_now_trade_text') }}</p><span class="hx-go">{{ __('pages.home.h_now_trade_cta') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></span></a>
                <a class="hx-card" href="{{ route('vendor.register') }}"><i class="bi bi-shop" aria-hidden="true"></i><strong>{{ __('pages.home.h_now_sell') }}</strong><p>{{ __('pages.home.h_now_sell_text') }}</p><span class="hx-go">{{ __('pages.home.h_now_sell_cta') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></span></a>
                <a class="hx-card brass" href="#newsletter"><i class="bi bi-envelope-paper" aria-hidden="true"></i><strong>{{ __('pages.home.h_now_news') }}</strong><p>{{ __('pages.home.h_now_news_text') }}</p><span class="hx-go">{{ __('pages.home.h_now_news_cta') }} <i class="bi bi-arrow-down" aria-hidden="true"></i></span></a>
            </div>
            <button type="button" class="rail-btn next" data-rail-next aria-label="{{ __('pages.home.next') }}" hidden><i class="bi bi-chevron-right"></i></button>
        </div>
    </div>
</section>

{{-- 6. Light, layered: a mood picture and the pieces that make it --}}
@if($moodProducts->count() >= 4)
<section class="hx-section">
    <div class="container">
        <div class="hx-mood">
            <div class="hx-mood-pic"><img src="{{ $src('mood', 640, 1.1) }}" srcset="{{ $set('mood', [400, 640, 900], 1.1) }}" sizes="(min-width: 992px) 38vw, 92vw" width="640" height="704" alt="" loading="lazy" decoding="async"></div>
            <div class="hx-mood-note">
                <span class="hx-kicker">{{ __('pages.home.h_mood_kicker') }}</span>
                <h2>{{ __('pages.home.h_mood') }}</h2>
                <p>{{ __('pages.home.h_mood_text') }}</p>
                <a class="hx-link" href="{{ $lighting ? $lighting->shopUrl() : route('search') }}">{{ __('pages.home.h_mood_link') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
        </div>
        <div class="rail" data-rail>
            <button type="button" class="rail-btn prev" data-rail-prev aria-label="{{ __('pages.home.previous') }}" hidden><i class="bi bi-chevron-left"></i></button>
            <div class="rail-track product-rail" data-rail-track>
                @foreach($moodProducts as $product)
                    @include('components.product-card', ['product' => $product, 'factLimit' => 3])
                @endforeach
            </div>
            <button type="button" class="rail-btn next" data-rail-next aria-label="{{ __('pages.home.next') }}" hidden><i class="bi bi-chevron-right"></i></button>
        </div>
    </div>
</section>
@endif

{{-- 7. New arrivals, then the collections --}}
@if($newArrivals->isNotEmpty())
<section class="hx-section">
    <div class="container">
        <header class="hx-head">
            <div><span class="hx-kicker">{{ __('pages.home.h_new_kicker') }}</span><h2>{{ __('pages.home.h_new') }}</h2></div>
            <a class="hx-link" href="{{ route('search', ['sort' => 'newest']) }}">{{ __('pages.home.see_more') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </header>
        <div class="rail" data-rail>
            <button type="button" class="rail-btn prev" data-rail-prev aria-label="{{ __('pages.home.previous') }}" hidden><i class="bi bi-chevron-left"></i></button>
            <div class="rail-track product-rail" data-rail-track>
                @foreach($newArrivals as $product)
                    @include('components.product-card', ['product' => $product, 'factLimit' => 3])
                @endforeach
            </div>
            <button type="button" class="rail-btn next" data-rail-next aria-label="{{ __('pages.home.next') }}" hidden><i class="bi bi-chevron-right"></i></button>
        </div>

        @if($mosaic->count() >= 3)
            <h3 class="hx-sub">{{ __('pages.home.h_collections') }}</h3>
            <div class="mosaic n{{ $mosaic->count() }}">
                @foreach($mosaic as $c)
                    <a class="tile t{{ $loop->iteration }}" href="{{ $c->shopUrl() }}">
                        @if($catPic($c, 720, 540))<img src="{{ $catPic($c, 720, 540) }}" width="720" height="540" alt="" loading="lazy" decoding="async">@else<i class="bi {{ $icons[$loop->index % count($icons)] }} art" aria-hidden="true"></i>@endif
                        <span class="cap"><strong>{{ $c->name }}</strong><span class="go">{{ __('pages.home.explore') }} <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span></span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endif

{{-- 8. What every order comes with --}}
<div class="container">
    <ul class="assure hx-assure list-unstyled">
        <li><i class="bi bi-truck"></i><span><strong>{{ __('pages.home.trust_free') }}</strong><small>{{ __('pages.home.trust_free_text', ['amount' => $freeAmount]) }}</small></span></li>
        <li><i class="bi bi-arrow-repeat"></i><span><strong>{{ __('pages.home.trust_returns') }}</strong><small>{{ __('pages.home.trust_returns_text') }}</small></span></li>
        <li><i class="bi bi-shield-check"></i><span><strong>{{ __('pages.home.trust_secure') }}</strong><small>{{ __('pages.home.trust_secure_text') }}</small></span></li>
        <li><i class="bi bi-receipt"></i><span><strong>{{ __('pages.home.trust_gst') }}</strong><small>{{ __('pages.home.trust_gst_text') }}</small></span></li>
    </ul>
</div>

{{-- 9. Offers --}}
<section class="hx-section">
    <div class="container">
        <header class="hx-head">
            <div><span class="hx-kicker">{{ __('pages.home.h_offers_kicker') }}</span><h2>{{ __('pages.home.h_offers') }}</h2><p>{{ __('pages.home.h_offers_text') }}</p></div>
        </header>
        <div class="hx-offers">
            <a href="{{ route('search', ['on_sale' => 1]) }}"><span>01</span><strong>{{ __('pages.home.h_offer_sale') }}</strong><small>{{ __('pages.home.h_offer_sale_text') }}</small><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
            <a href="{{ route('search', ['sort' => 'rating']) }}"><span>02</span><strong>{{ __('pages.home.h_offer_loved') }}</strong><small>{{ __('pages.home.h_offer_loved_text') }}</small><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
            <a href="{{ route('search', ['sort' => 'newest']) }}"><span>03</span><strong>{{ __('pages.home.h_offer_new') }}</strong><small>{{ __('pages.home.h_offer_new_text') }}</small><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
            <a href="{{ route('search', ['min_price' => (int) config('shop.free_shipping_over')]) }}"><span>04</span><strong>{{ __('pages.home.h_offer_free') }}</strong><small>{{ __('pages.home.h_offer_free_text', ['amount' => $freeAmount]) }}</small><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
        </div>
        @php $offerRail = $onSale->count() >= 4 ? $onSale : $bestSellers; @endphp
        @if($offerRail->count() >= 4)
            <div class="rail mt-4" data-rail>
                <button type="button" class="rail-btn prev" data-rail-prev aria-label="{{ __('pages.home.previous') }}" hidden><i class="bi bi-chevron-left"></i></button>
                <div class="rail-track product-rail" data-rail-track>
                    @foreach($offerRail as $product)
                        @include('components.product-card', ['product' => $product, 'factLimit' => 3])
                    @endforeach
                </div>
                <button type="button" class="rail-btn next" data-rail-next aria-label="{{ __('pages.home.next') }}" hidden><i class="bi bi-chevron-right"></i></button>
            </div>
        @endif
    </div>
</section>

{{-- 10. Services --}}
<section class="hx-section hx-tint">
    <div class="container">
        <header class="hx-head">
            <div><span class="hx-kicker">{{ __('pages.home.h_services_kicker') }}</span><h2>{{ __('pages.home.h_services') }}</h2></div>
        </header>
        <div class="hx-services">
            <a href="{{ route('shipping') }}"><i class="bi bi-box-seam" aria-hidden="true"></i><strong>{{ __('pages.home.svc_delivery') }}</strong><span>{{ __('pages.home.svc_delivery_text') }}</span></a>
            <a href="{{ route('returns') }}"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i><strong>{{ __('pages.home.svc_returns') }}</strong><span>{{ __('pages.home.svc_returns_text') }}</span></a>
            <a href="{{ route('customer.order-tracking.index') }}"><i class="bi bi-geo-alt" aria-hidden="true"></i><strong>{{ __('pages.home.svc_track') }}</strong><span>{{ __('pages.home.svc_track_text') }}</span></a>
            <a href="{{ route('faq') }}"><i class="bi bi-question-circle" aria-hidden="true"></i><strong>{{ __('pages.home.h_svc_faq') }}</strong><span>{{ __('pages.home.h_svc_faq_text') }}</span></a>
            <a href="{{ route('customer.support-tickets.index') }}"><i class="bi bi-headset" aria-hidden="true"></i><strong>{{ __('pages.home.h_svc_support') }}</strong><span>{{ __('pages.home.h_svc_support_text') }}</span></a>
            @if(\App\Support\Stores::enabled())<a href="{{ route('stores.index') }}"><i class="bi bi-people" aria-hidden="true"></i><strong>{{ __('pages.home.h_svc_stores') }}</strong><span>{{ __('pages.home.h_svc_stores_text') }}</span></a>@endif
        </div>
    </div>
</section>

{{-- 11. Ideas: stylist rooms when there are some, otherwise a guide per room --}}
<section class="hx-section">
    <div class="container">
        <header class="hx-head">
            <div><span class="hx-kicker">{{ __('pages.home.h_ideas_kicker') }}</span><h2>{{ __('pages.home.h_ideas') }}</h2></div>
        </header>
        <div class="hx-ideas">
            @if($ideas->isNotEmpty())
                @foreach($ideas as $idea)
                    <a class="hx-idea" href="{{ route('search', ['q' => $idea->room_type]) }}">
                        <span class="hx-idea-pic">@if($pic($idea->image))<img src="{{ $pic($idea->image) }}" alt="" loading="lazy" decoding="async">@else<i class="bi bi-lamp" aria-hidden="true"></i>@endif</span>
                        @if($idea->room_type)<small>{{ $idea->room_type }}</small>@endif
                        <strong>{{ $idea->title }}</strong>
                        @if($idea->description)<span>{{ \App\Support\Html::text($idea->description, 90) }}</span>@endif
                    </a>
                @endforeach
            @else
                @foreach($categories->sortByDesc('products_count')->slice(5, 4)->whenEmpty(fn () => $categories->take(4)) as $c)
                    <a class="hx-idea" href="{{ $c->shopUrl() }}">
                        <span class="hx-idea-pic">@if($catPic($c, 480, 600))<img src="{{ \App\Support\StockPhotos::enabled() && blank($c->image) && count(\App\Support\StockPhotos::pool($c->name)) > 1 ? \App\Support\StockPhotos::url(\App\Support\StockPhotos::pool($c->name)[1], 480, 600) : $catPic($c, 480, 600) }}" width="480" height="600" alt="" loading="lazy" decoding="async">@else<i class="bi bi-lamp" aria-hidden="true"></i>@endif</span>
                        <small>{{ $c->name }}</small>
                        <strong>{{ __('pages.home.h_ideas_room', ['room' => $c->name]) }}</strong>
                        <span>{{ __('pages.home.h_ideas_room_text', ['room' => mb_strtolower($c->name)]) }}</span>
                    </a>
                @endforeach
            @endif
        </div>
    </div>
</section>

{{-- 12. Who we are, in words a search engine can read too --}}
<section class="hx-section hx-about">
    <div class="container">
        <span class="hx-kicker">{{ __('pages.home.h_about_kicker') }}</span>
        <h2>{{ __('pages.home.h_about') }}</h2>
        <p>{{ __('pages.home.h_about_1', ['name' => $shopName]) }}</p>
        <p>{!! __('pages.home.h_about_2', ['amount' => e($freeAmount), 'links' => $categories->take(6)->map(fn ($c) => '<a href="' . e($c->shopUrl()) . '">' . e($c->name) . '</a>')->implode(', ')]) !!}</p>
        <a class="hx-link" href="{{ route('about-us') }}">{{ __('pages.home.h_about_link') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </div>
</section>

{{-- 12b. What our customers say (approved reviews) --}}
@include('partials.home-reviews')

{{-- 13. Newsletter --}}
<section class="hx-section pt-0">
    <div class="container">
        <div class="newsletter hx-news" id="newsletter">
            <div>
                <h3>{{ __('pages.home.news_title') }}</h3>
                <p>{{ __('pages.home.news_text') }}</p>
            </div>
            <div>
                <form action="{{ route('newsletter.subscribe') }}" method="POST">
                    @csrf
                    <input type="email" name="email" required placeholder="{{ __('pages.home.news_placeholder') }}" aria-label="{{ __('pages.home.news_placeholder') }}" autocomplete="email">
                    <button class="btn btn-accent">{{ __('pages.home.subscribe') }}</button>
                </form>
                @if(session('newsletter'))<p class="mt-2 mb-0 small text-white">{{ session('newsletter') }}</p>@endif
                @error('email')<p class="mt-2 mb-0 small text-warning">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>
</section>
</div>

@if($deal && $deal->product)
@push('scripts')
<script>
    (function () {
        var el = document.querySelector('.hx-count'); if (!el) return;
        var end = new Date(el.dataset.ends).getTime();
        function tick() {
            var s = Math.max(0, Math.floor((end - Date.now()) / 1000));
            var v = { d: Math.floor(s / 86400), h: Math.floor(s % 86400 / 3600), m: Math.floor(s % 3600 / 60), s: s % 60 };
            Object.keys(v).forEach(function (k) { el.querySelector('[data-u=' + k + ']').textContent = String(v[k]).padStart(2, '0'); });
        }
        tick(); setInterval(tick, 1000);
    })();
</script>
@endpush
@endif
@endsection
