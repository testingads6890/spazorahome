@extends('layouts.app')

@php
    $variants = $product->variants;
    $hasVariants = $variants->isNotEmpty();
    $inr = fn ($v) => \App\Support\Money::browse($v);
    $base = (float) $product->price;
    $sale = $product->sale_price !== null && (float) $product->sale_price < $base ? (float) $product->sale_price : null;
    $now = $sale ?? $base;
    $off = $sale ? (int) round((1 - $sale / $base) * 100) : 0;
    $gst = rtrim(rtrim(number_format($product->taxRate(), 2), '0'), '.');
    $images = $product->photos();
    $inStock = $hasVariants ? $variants->sum('stock_quantity') > 0 : $product->stock_quantity > 0;
    $specs = array_filter([
        __('pages.product.spec_brand') => $product->brand, __('pages.product.spec_material') => $product->material, __('pages.product.spec_colour') => $product->color, __('pages.product.spec_finish') => $product->finish,
        __('pages.product.spec_size') => $product->size, __('pages.product.spec_style') => $product->style, __('pages.product.spec_type') => $product->product_type, __('pages.product.spec_dimensions') => $product->dimensions,
        __('pages.product.spec_weight') => $product->weight, __('pages.product.spec_warranty') => $product->warranty, __('pages.product.spec_sku') => $product->sku, __('pages.product.spec_hsn') => $product->hsn_code,
    ]);
    $firstVariant = $hasVariants ? ($variants->firstWhere(fn ($v) => $v->stock_quantity > 0) ?? $variants->first()) : null;
    // "Seat height: 45 cm" lines written by the seller join the table of details.
    foreach (preg_split('/\r\n|\r|\n/', (string) $product->specifications) as $line) {
        if (str_contains($line, ':')) {
            [$k, $v] = array_map('trim', explode(':', $line, 2));
            if ($k !== '' && $v !== '') { $specs[$k] = $v; }
        }
    }
    // Details the seller filled in (material grade, lead time, assembly, packing, product-type specifics...).
    $extra = \App\Support\ProductExtraFields::present($product, true);
    $extraSpecs = array_diff_key($extra, array_flip(['Warranty and returns', 'Unit, lead time and customisation', 'Assembly and installation', "What's included and FAQs"]));
    $extraShip = array_intersect_key($extra, array_flip(['Warranty and returns', 'Unit, lead time and customisation', 'Assembly and installation']));
    $minQty = max(1, (int) $product->min_order_qty);
    // Sellers, admins and staff browse the store but do not buy from it.
    $canShop = ! auth()->check() || auth()->user()->hasRole('customer');
    $bulkTiers = $product->bulkTiers();
    $unitStart = $firstVariant ? (float) ($firstVariant->sale_price ?? $firstVariant->price) : $now;
    if ($minQty > 1) { $specs[__('pages.product.spec_moq')] = $minQty; }
    $parentCat = $product->category?->parent_id ? \App\Models\Category::find($product->category->parent_id) : null;

    // Video: YouTube or Vimeo links become an embedded player (privacy-friendly YouTube domain).
    $video = null;
    if ($product->video_url) {
        if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([\w-]{11})~', $product->video_url, $m)) {
            $video = 'https://www.youtube-nocookie.com/embed/' . $m[1];
        } elseif (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $product->video_url, $m)) {
            $video = 'https://player.vimeo.com/video/' . $m[1];
        }
    }

    // Structured data for search engines: the product with its offer and rating, and the breadcrumb trail.
    $schemaPrice = $firstVariant ? (float) ($firstVariant->sale_price ?? $firstVariant->price) : $now;
    $productSchema = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'description' => \App\Support\Html::text($product->short_description ?: $product->description, 500),
        'sku' => $product->sku,
        'image' => $images->pluck('large')->take(6)->values()->all(),
        'brand' => $product->brand ? ['@type' => 'Brand', 'name' => $product->brand] : null,
        'material' => $product->material,
        'color' => $product->color,
        'category' => $product->category?->name,
        'url' => route('products.show', $product),
        'offers' => [
            '@type' => 'Offer',
            'url' => route('products.show', $product),
            'priceCurrency' => 'INR',
            'price' => number_format($schemaPrice, 2, '.', ''),
            'availability' => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'itemCondition' => 'https://schema.org/NewCondition',
            'seller' => ['@type' => 'Organization', 'name' => $product->vendor?->business_name ?: (config('shop.seller.name') ?: 'SpazoraHome')],
        ],
        'aggregateRating' => $reviewCount ? ['@type' => 'AggregateRating', 'ratingValue' => $rating, 'reviewCount' => $reviewCount] : null,
    ]);
    $crumbs = collect([[__('pages.product.home'), route('home')]])
        ->when($parentCat, fn ($c) => $c->push([$parentCat->name, $parentCat->shopUrl()]))
        ->when($product->category, fn ($c) => $c->push([$product->category->name, $product->category->shopUrl()]))
        ->push([$product->name, route('products.show', $product)]);
    $breadcrumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $crumbs->values()->map(fn ($c, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => $c[1]])->all(),
    ];
@endphp

@section('title', ($product->meta_title ?: $product->name) . ' - SpazoraHome')
@section('meta_description', \App\Support\Html::text($product->meta_description ?: ($product->short_description ?: $product->description), 155))
@section('og_type', 'product')
@section('og_image', $images->first()['large'] ?? '')

@push('head')
<script type="application/ld+json">{!! json_encode($productSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
<script type="application/ld+json">{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
<div class="page-head">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('pages.product.home') }}</a></li>
                @if($parentCat)<li class="breadcrumb-item"><a href="{{ $parentCat->shopUrl() }}">{{ $parentCat->name }}</a></li>@endif
                @if($product->category)<li class="breadcrumb-item"><a href="{{ $product->category->shopUrl() }}">{{ $product->category->name }}</a></li>@endif
                <li class="breadcrumb-item active">{{ \Illuminate\Support\Str::limit($product->name, 40) }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-4">
    @foreach(['success' => 'success', 'error' => 'danger'] as $key => $cls)
        @if(session($key))<div class="alert alert-{{ $cls }}">{{ session($key) }}</div>@endif
    @endforeach

    <div class="row g-4 g-lg-5">
        <div class="col-lg-6">
            <div class="gallery-wrap {{ $images->count() > 1 ? 'has-thumbs' : '' }}">
            <div class="gallery-main" id="galleryMain">
                @if($images->isNotEmpty())
                    <img id="mainImage" src="{{ $images->first()['large'] }}" alt="{{ $images->first()['alt'] }}">
                @else
                    <i class="bi bi-house-heart"></i>
                @endif
            </div>
            @if($images->count() > 1)
                <div class="thumbs" role="tablist" aria-label="{{ __('pages.product.product_images') }}">
                    @foreach($images as $img)
                        <button type="button" class="{{ $loop->first ? 'active' : '' }}" data-src="{{ $img['large'] }}" aria-label="{{ __('pages.product.image_n', ['n' => $loop->iteration]) }}">
                            <img src="{{ $img['thumb'] }}" alt="" loading="lazy">
                        </button>
                    @endforeach
                </div>
            @endif
            </div>
        </div>

        <div class="col-lg-6">
            <div class="buybox">
                @if($product->category)<a class="text-uppercase small fw-bold text-muted" href="{{ $product->category->shopUrl() }}">{{ $product->category->name }}</a>@endif
                <h1 class="mt-1">{{ $product->name }}</h1>

                <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                    @if($reviewCount)
                        <a href="#reviews" class="stars text-decoration-none" data-tab="reviews-tab">
                            @for($i = 1; $i <= 5; $i++)<i class="bi bi-star{{ $rating >= $i ? '-fill' : ($rating >= $i - .5 ? '-half' : '') }}"></i>@endfor
                            <small class="ms-1">{{ number_format($rating, 1) }} ({{ trans_choice('pages.product.reviews_count', $reviewCount, ['count' => $reviewCount]) }})</small>
                        </a>
                    @else
                        <span class="text-muted small">{{ __('pages.product.no_reviews') }}</span>
                    @endif
                    @if($product->vendor)<span class="small text-muted"><i class="bi bi-shop"></i> {{ __('pages.product.sold_by') }}
                        @if(\App\Support\Stores::enabled())<a href="{{ route('stores.show', $product->vendor) }}"><strong>{{ $product->vendor->business_name }}</strong></a>@else<strong>{{ $product->vendor->business_name }}</strong>@endif
                        @if($product->vendor->city), {{ $product->vendor->city }}@endif</span>@endif
                </div>

                <div class="d-flex align-items-baseline flex-wrap gap-2">
                    <span class="price" id="priceNow">{{ $inr($firstVariant ? (float) ($firstVariant->sale_price ?? $firstVariant->price) : $now) }}</span>
                    <span class="old-price" id="priceOld" @if(! $sale && ! ($firstVariant && $firstVariant->sale_price)) hidden @endif>{{ $inr($firstVariant ? (float) $firstVariant->price : $base) }}</span>
                    <span class="pill" id="priceOff" @if(! $off) hidden @endif>-{{ $off }}%</span>
                </div>
                @if(\App\Support\Money::isForeign())
                    <p class="small mb-2 text-accent"><i class="bi bi-info-circle"></i> {{ __('pages.product.approx', ['code' => \App\Support\Money::code(), 'price' => '₹' . number_format($firstVariant ? (float) ($firstVariant->sale_price ?? $firstVariant->price) : $now)]) }}</p>
                @endif
                @if($bulkTiers)
                    <p class="bulk-teaser mb-1"><i class="bi bi-boxes"></i>
                        @foreach($bulkTiers as $bMin => $bPct){{ __('pages.product.bulk_teaser', ['count' => $bMin, 'pct' => rtrim(rtrim(number_format($bPct, 2), '0'), '.')]) }}@if(! $loop->last) &middot; @endif @endforeach
                    </p>
                @endif
                <p class="text-muted small mb-3">{{ __('pages.product.gst_note', ['rate' => $gst]) }}</p>
                @if(config('shop.export.enabled') && $product->export_available)
                    @php
                        $shipsTo = \App\Models\ShippingZone::where('is_active', true)->get()->flatMap(fn ($z) => $z->countries ?? [])
                            ->filter(fn ($c) => ! \App\Support\Countries::isIndia($c) && $product->exportableTo($c))->unique()->sort()->values();
                        $enquiryOpen = $errors->exportEnquiry->any();
                    @endphp
                    <div class="export-box mb-3">
                        <p class="mb-1"><i class="bi bi-globe2 text-accent"></i> <strong>{{ __('pages.product.ships_abroad') }}</strong></p>
                        <ul class="list-unstyled small text-muted mb-2">
                            @if($shipsTo->isNotEmpty())<li>{{ __('pages.product.ships_to', ['countries' => $shipsTo->take(8)->implode(', ') . ($shipsTo->count() > 8 ? ' +' . ($shipsTo->count() - 8) : '')]) }}</li>@endif
                            @if($product->export_min_qty > 1)<li>{{ __('pages.product.export_min', ['min' => $product->export_min_qty]) }}</li>@endif
                            @if((float) $product->export_weight_kg > 0)<li>{{ __('pages.product.export_weight', ['kg' => rtrim(rtrim(number_format((float) $product->export_weight_kg, 2), '0'), '.')]) }}</li>@endif
                            <li>{{ __('pages.product.export_origin', ['country' => $product->country_of_origin ?: 'India']) }}</li>
                        </ul>
                        <details @if($enquiryOpen) open @endif>
                            <summary class="small fw-semibold">{{ __('pages.product.enquiry_title') }}</summary>
                            <form action="{{ route('products.export-enquiry', $product) }}" method="POST" class="row g-2 mt-1">
                                @csrf
                                <div class="col-sm-6"><input type="text" name="name" class="form-control form-control-sm" placeholder="{{ __('pages.product.enquiry_name') }}" aria-label="{{ __('pages.product.enquiry_name') }}" value="{{ old('name', auth()->user()?->name) }}" required></div>
                                <div class="col-sm-6"><input type="email" name="email" class="form-control form-control-sm" placeholder="{{ __('pages.product.enquiry_email') }}" aria-label="{{ __('pages.product.enquiry_email') }}" value="{{ old('email', auth()->user()?->email) }}" required></div>
                                <div class="col-sm-6"><input type="text" name="company" class="form-control form-control-sm" placeholder="{{ __('pages.product.enquiry_company') }}" aria-label="{{ __('pages.product.enquiry_company') }}" value="{{ old('company') }}"></div>
                                <div class="col-sm-6"><input type="text" name="phone" class="form-control form-control-sm" placeholder="{{ __('pages.product.enquiry_phone') }}" aria-label="{{ __('pages.product.enquiry_phone') }}" value="{{ old('phone') }}"></div>
                                <div class="col-sm-8">
                                    <select name="country" class="form-select form-select-sm" aria-label="{{ __('pages.product.enquiry_country') }}" required>
                                        <option value="">{{ __('pages.product.enquiry_country') }}</option>
                                        @foreach(\App\Support\Countries::all() as $country)
                                            @continue(\App\Support\Countries::isIndia($country))
                                            <option value="{{ $country }}" @selected(old('country') === $country)>{{ $country }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-4"><input type="number" name="quantity" min="1" class="form-control form-control-sm" placeholder="{{ __('pages.product.enquiry_qty') }}" aria-label="{{ __('pages.product.enquiry_qty') }}" value="{{ old('quantity', max(1, (int) $product->export_min_qty)) }}" required></div>
                                <div class="col-12"><textarea name="message" rows="2" class="form-control form-control-sm" placeholder="{{ __('pages.product.enquiry_message') }}" aria-label="{{ __('pages.product.enquiry_message') }}">{{ old('message') }}</textarea></div>
                                @if($enquiryOpen)<div class="col-12 text-danger small">{{ $errors->exportEnquiry->first() }}</div>@endif
                                <div class="col-12"><button class="btn btn-sm btn-outline-dark">{{ __('pages.product.enquiry_send') }}</button></div>
                            </form>
                        </details>
                    </div>
                @endif

                @if($product->short_description)<div class="mb-3 rich-content">{!! \App\Support\Html::render($product->short_description) !!}</div>@endif

                @include('partials.delivery-check')
                <form id="buyForm" action="{{ route('customer.cart.store', array_filter([$product, $firstVariant])) }}" method="POST">
                    @csrf
                    @if($hasVariants)
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase text-muted">{{ __('pages.product.choose_option') }}</label>
                            <div class="d-flex flex-wrap gap-2" id="variantPicker">
                                @foreach($variants as $v)
                                    @php $vs = (float) ($v->sale_price ?? $v->price); @endphp
                                    <input type="radio" class="btn-check" name="variant_pick" id="v{{ $v->id }}" autocomplete="off"
                                           data-action="{{ route('customer.cart.store', [$product, $v]) }}" data-price="{{ $vs }}"
                                           data-old="{{ $v->sale_price !== null && (float) $v->sale_price < (float) $v->price ? (float) $v->price : '' }}"
                                           data-stock="{{ $v->stock_quantity }}" {{ $firstVariant && $v->id === $firstVariant->id ? 'checked' : '' }} {{ $v->stock_quantity <= 0 ? 'disabled' : '' }}>
                                    <label class="btn btn-outline-primary btn-sm" for="v{{ $v->id }}">{{ $v->label }}</label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="mb-3" id="stockLine">
                        @if($inStock)
                            <span class="text-success fw-semibold"><i class="bi bi-check-circle-fill"></i> {{ __('pages.product.in_stock') }}</span>
                            <span class="text-muted small" id="stockLeft"></span>
                        @else
                            <span class="text-danger fw-semibold"><i class="bi bi-x-circle-fill"></i> {{ __('pages.product.out_of_stock') }}</span>
                        @endif
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <div class="qty" data-qty>
                            <button type="button" data-step="-1" aria-label="{{ __('pages.product.decrease') }}"><i class="bi bi-dash"></i></button>
                            <input type="number" name="quantity" value="{{ $minQty }}" min="{{ $minQty }}" max="{{ $hasVariants ? ($firstVariant?->stock_quantity ?? 1) : max($minQty, $product->stock_quantity) }}" id="qtyInput" aria-label="{{ __('pages.product.quantity') }}">
                            <button type="button" data-step="1" aria-label="{{ __('pages.product.increase') }}"><i class="bi bi-plus"></i></button>
                        </div>
                        @if($canShop)
                            <button type="submit" class="btn btn-primary btn-lg flex-grow-1 flex-sm-grow-0" id="addBtn" {{ $inStock ? '' : 'disabled' }}><i class="bi bi-bag-plus"></i> {{ __('pages.product.add_to_cart') }}</button>
                            <button type="submit" name="buy_now" value="1" class="btn btn-accent btn-lg flex-grow-1 flex-sm-grow-0" id="buyNowBtn" {{ $inStock ? '' : 'disabled' }}><i class="bi bi-lightning-charge"></i> {{ __('pages.product.buy_now') }}</button>
                        @endif
                    </div>
                    @unless($canShop)
                        <div class="alert alert-light border small mt-2 mb-0"><i class="bi bi-info-circle"></i> {{ __('pages.product.not_shopper') }}
                            <a href="{{ route('dashboard') }}" class="fw-semibold">{{ __('pages.product.back_to_panel') }}</a></div>
                    @endunless
                    @if($bulkTiers)
                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="bulkToggle" data-bs-toggle="collapse" data-bs-target="#bulkPanel" aria-expanded="false" aria-controls="bulkPanel" {{ $inStock ? '' : 'disabled' }}><i class="bi bi-boxes"></i> {{ __('pages.product.bulk_order') }}</button>
                        <div class="collapse" id="bulkPanel">
                            <div class="bulk-box mt-2">
                                <div class="fw-semibold mb-2">{{ __('pages.product.bulk_title') }} <span class="text-muted small fw-normal">&middot; {{ __('pages.product.bulk_choose') }}</span></div>
                                <div class="bulk-tiers" role="group" aria-label="{{ __('pages.product.bulk_title') }}">
                                    <button type="button" class="bulk-tier" data-min="{{ $minQty }}" data-off="0">
                                        <span class="bt-qty">{{ $minQty > 1 ? __('pages.product.bulk_units', ['count' => $minQty]) : __('pages.product.bulk_one') }}</span>
                                        <span class="bt-price" data-each="0">{{ $inr($unitStart) }}</span>
                                        <span class="bt-save">&nbsp;</span>
                                    </button>
                                    @foreach($bulkTiers as $bMin => $bPct)
                                        @continue($bMin <= $minQty)
                                        <button type="button" class="bulk-tier" data-min="{{ $bMin }}" data-off="{{ $bPct }}">
                                            <span class="bt-qty">{{ __('pages.product.bulk_units', ['count' => $bMin]) }}</span>
                                            <span class="bt-price" data-each="{{ $bPct }}">{{ $inr(round($unitStart * (100 - $bPct) / 100, 2)) }}</span>
                                            <span class="bt-save">{{ __('pages.product.bulk_save', ['pct' => rtrim(rtrim(number_format($bPct, 2), '0'), '.')]) }}</span>
                                        </button>
                                    @endforeach
                                </div>
                                <p class="small text-muted mt-2 mb-0">{{ __('pages.product.bulk_note') }}</p>
                            </div>
                        </div>
                    @endif
                    <div class="bulk-live small mt-2" id="bulkLive" aria-live="polite"></div>
                    @if($minQty > 1)<p class="small text-muted mt-2 mb-0"><i class="bi bi-boxes"></i> {{ __('pages.product.min_order', ['count' => $minQty]) }}</p>@endif
                </form>

                <div class="d-flex gap-2 mt-3">
                    @if($canShop)
                    <form action="{{ route('customer.wishlists.store', $product) }}" method="POST">@csrf
                        <button class="btn btn-outline-primary btn-sm"><i class="bi bi-heart"></i> {{ __('pages.product.wishlist') }}</button>
                    </form>
                    {{-- Compare: hidden for now. Remove these markers to show it again. --}}
                    {{--<form action="{{ route('customer.compare.store', $product) }}" method="POST">@csrf
                        <button class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left-right"></i> {{ __('pages.product.compare') }}</button>
                    </form>--}}
                    @endif
                    <button type="button" class="btn btn-outline-primary btn-sm" id="shareBtn"><i class="bi bi-share"></i> {{ __('pages.product.share') }}</button>
                </div>

                <ul class="list-unstyled mt-4 mb-0 small d-grid gap-2">
                    <li><i class="bi bi-truck text-accent"></i> {{ __('pages.product.free_delivery', ['amount' => $inr(config('shop.free_shipping_over'))]) }}</li>
                    <li><i class="bi bi-arrow-repeat text-accent"></i> <a href="{{ route('returns') }}">{{ __('pages.product.easy_returns') }}</a> {{ __('pages.product.easy_returns_text') }}</li>
                    <li><i class="bi bi-shield-check text-accent"></i> {{ __('pages.product.secure') }}</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="mt-5" id="details">
        <ul class="nav tab-nav border-bottom" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-desc" type="button" role="tab">{{ __('pages.product.tab_description') }}</button></li>
            @if($specs || $extraSpecs)<li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-specs" type="button" role="tab">{{ __('pages.product.tab_specs') }}</button></li>@endif
            <li class="nav-item"><button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#tab-reviews" type="button" role="tab">{{ __('pages.product.tab_reviews', ['count' => $reviewCount]) }}</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-ship" type="button" role="tab">{{ __('pages.product.tab_shipping') }}</button></li>
        </ul>
        <div class="tab-content py-4">
            <div class="tab-pane fade show active" id="tab-desc" role="tabpanel">
                <div class="rich-content" style="max-width: 820px">{!! \App\Support\Html::render($product->description) !!}</div>
                @if($product->customization_info)
                    <div class="mt-4" style="max-width: 820px"><h6>{{ __('pages.product.customization') }}</h6><p class="mb-0" style="white-space: pre-line">{{ $product->customization_info }}</p></div>
                @endif
                @if($product->whats_included || $product->whats_not_included)
                    <div class="row g-4 mt-1" style="max-width: 820px">
                        @foreach([["What's included", $product->whats_included, 'bi-check-circle text-success'], ["What's not included", $product->whats_not_included, 'bi-x-circle text-danger']] as [$title, $text, $icon])
                            @if($text)<div class="col-md-6"><h6>{{ $title }}</h6><ul class="list-unstyled mb-0">@foreach(preg_split('/\r\n|\r|\n/', $text) as $line)@if(trim($line) !== '')<li><i class="bi {{ $icon }}"></i> {{ trim($line) }}</li>@endif @endforeach</ul></div>@endif
                        @endforeach
                    </div>
                @endif
                @php $faqs = collect([1, 2, 3])->filter(fn ($i) => $product->{"faq_{$i}_question"} && $product->{"faq_{$i}_answer"}); @endphp
                @if($faqs->isNotEmpty())
                    <div class="mt-4" style="max-width: 820px">
                        <h6>Questions buyers ask</h6>
                        @foreach($faqs as $i)<details class="mb-2"><summary class="fw-semibold">{{ $product->{"faq_{$i}_question"} }}</summary><p class="mb-0 mt-1" style="white-space: pre-line">{{ $product->{"faq_{$i}_answer"} }}</p></details>@endforeach
                    </div>
                @endif
                @if($video)
                    <div class="mt-4" style="max-width: 820px">
                        <h6>{{ __('pages.product.video') }}</h6>
                        <div class="ratio ratio-16x9 rounded overflow-hidden"><iframe src="{{ $video }}" title="{{ __('pages.product.video') }}: {{ $product->name }}" loading="lazy" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div>
                    </div>
                @endif
                @if(! empty($product->tags))
                    <div class="mt-4 d-flex flex-wrap gap-2">@foreach((array) $product->tags as $tag)<a class="chip" href="{{ route('search', ['q' => $tag]) }}">#{{ $tag }}</a>@endforeach</div>
                @endif
            </div>
            @if($specs || $extraSpecs)
            <div class="tab-pane fade" id="tab-specs" role="tabpanel">
                <dl class="spec-list" style="max-width: 720px">
                    @foreach($specs as $k => $v)<dt>{{ $k }}</dt><dd>{{ $v }}</dd>@endforeach
                    @foreach($extraSpecs as $title => $rows)
                        @foreach($rows as $k => $v)<dt>{{ $k }}</dt><dd>{!! nl2br(e($v)) !!}</dd>@endforeach
                    @endforeach
                </dl>
            </div>
            @endif
            <div class="tab-pane fade" id="tab-reviews" role="tabpanel">
                <div id="reviews" class="row g-4">
                    <div class="col-md-4">
                        <div class="card card-body text-center">
                            <div class="display-5 fw-bold">{{ $reviewCount ? number_format($rating, 1) : '-' }}</div>
                            <div class="stars justify-content-center mb-2">@for($i = 1; $i <= 5; $i++)<i class="bi bi-star{{ $rating >= $i ? '-fill' : ($rating >= $i - .5 ? '-half' : '') }}"></i>@endfor</div>
                            <div class="text-muted small mb-3">{{ trans_choice('pages.product.reviews_count', $reviewCount, ['count' => $reviewCount]) }}</div>
                            @foreach([5, 4, 3, 2, 1] as $star)
                                @php $c = (int) ($ratingCounts[$star] ?? 0); $pc = $reviewCount ? round($c / $reviewCount * 100) : 0; @endphp
                                <div class="d-flex align-items-center gap-2 small mb-1">
                                    <span style="width: 2.2rem">{{ $star }} <i class="bi bi-star-fill" style="color:var(--star)"></i></span>
                                    <div class="progress flex-grow-1" style="height: 8px"><div class="progress-bar" style="width: {{ $pc }}%; background: var(--star)"></div></div>
                                    <span class="text-muted" style="width: 2rem">{{ $c }}</span>
                                </div>
                            @endforeach
                            @auth
                                <form action="{{ route('products.review.store', $product) }}" method="POST" class="mt-3 text-start">
                                    @csrf
                                    <h3 class="h6 mb-2">{{ __('Write a review') }}</h3>
                                    <div class="mb-2">
                                        <label for="review-rating" class="form-label small mb-1">{{ __('Your rating') }}</label>
                                        <select id="review-rating" name="rating" class="form-select form-select-sm @error('rating') is-invalid @enderror" required>
                                            @for($r = 5; $r >= 1; $r--)<option value="{{ $r }}" @selected(old('rating') == $r)>{{ $r }} ★</option>@endfor
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label for="review-title" class="form-label small mb-1">{{ __('Title') }}</label>
                                        <input id="review-title" type="text" name="title" maxlength="150" class="form-control form-control-sm" value="{{ old('title') }}">
                                    </div>
                                    <div class="mb-2">
                                        <label for="review-comment" class="form-label small mb-1">{{ __('Your review') }}</label>
                                        <textarea id="review-comment" name="comment" rows="3" minlength="10" maxlength="2000" required class="form-control form-control-sm @error('comment') is-invalid @enderror">{{ old('comment') }}</textarea>
                                        @error('comment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <button class="btn btn-primary btn-sm">{{ __('Submit review') }}</button>
                                    <p class="small text-muted mt-2 mb-0">{{ $canReview ? __('You bought this product, so your review shows as verified.') : __('Reviews appear after our team checks them.') }}</p>
                                </form>
                            @else
                                <p class="small text-muted mt-3 mb-0"><a href="{{ route('login') }}">{{ __('Sign in') }}</a> {{ __('to write a review.') }}</p>
                            @endauth
                            @if(config('shop.google_review_url'))
                                <a href="{{ config('shop.google_review_url') }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm mt-3 ms-md-2"><i class="bi bi-google" aria-hidden="true"></i> {{ __('Review us on Google') }}</a>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-8">
                        @forelse($reviews as $review)
                            <div class="card card-body mb-3">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <strong>{{ $review->user?->name ?? 'Customer' }}</strong>
                                        <span class="badge bg-success-subtle text-success-emphasis ms-1">{{ __('pages.product.verified') }}</span>
                                    </div>
                                    <span class="text-muted small">{{ $review->created_at->format('d M Y') }}</span>
                                </div>
                                <div class="stars my-1">@for($i = 1; $i <= 5; $i++)<i class="bi bi-star{{ $i <= $review->rating ? '-fill' : '' }}"></i>@endfor</div>
                                @if($review->title)<div class="fw-semibold">{{ $review->title }}</div>@endif
                                <p class="mb-0">{{ $review->comment }}</p>
                                @if(! empty($review->vendor_reply))
                                    <div class="mt-2 p-2 rounded small" style="background:#f6f3ee;border-left:3px solid var(--bs-primary,var(--brand))">
                                        <strong><i class="bi bi-shop"></i> {{ __('Reply from the seller') }}</strong>
                                        <div style="white-space:pre-line">{{ $review->vendor_reply }}</div>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="empty"><i class="bi bi-chat-square-text"></i><p class="mt-2 mb-0">{{ __('pages.product.no_reviews_long') }}</p></div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="tab-ship" role="tabpanel">
                <div style="max-width: 820px">
                    @if($product->shipping_info)<h6>{{ __('pages.product.shipping') }}</h6><p>{{ $product->shipping_info }}</p>@endif
                    @if($product->care_instructions)<h6>{{ __('pages.product.care') }}</h6><p>{{ $product->care_instructions }}</p>@endif
                    @if($product->warranty)<h6>{{ __('pages.product.warranty') }}</h6><p>{{ $product->warranty }}</p>@endif
                    @foreach($extraShip as $title => $rows)
                        <h6>{{ $title }}</h6>
                        <dl class="spec-list">@foreach($rows as $k => $v)<dt>{{ $k }}</dt><dd>{!! nl2br(e($v)) !!}</dd>@endforeach</dl>
                    @endforeach
                    <p class="text-muted mb-0">{!! __('pages.product.delivery_note', ['ship' => '<a href="' . route('shipping') . '">' . e(__('pages.product.shipping_policy')) . '</a>', 'returns' => '<a href="' . route('returns') . '">' . e(__('pages.product.returns_policy')) . '</a>']) !!}</p>
                </div>
            </div>
        </div>
    </div>

    @if($related->isNotEmpty())
        <section class="mt-4">
            <div class="section-head"><div><h2>{{ __('pages.product.related') }}</h2></div></div>
            <div class="product-grid cols-4">@foreach($related as $p)@include('components.product-card', ['product' => $p])@endforeach</div>
        </section>
    @endif

    @if($recent->isNotEmpty())
        <section class="mt-5">
            <div class="section-head"><div><h2>{{ __('pages.product.recent') }}</h2></div></div>
            <div class="product-grid cols-4">@foreach($recent as $p)@include('components.product-card', ['product' => $p])@endforeach</div>
        </section>
    @endif
</div>

@if($canShop)
<div class="sticky-buy" id="stickyBuy">
    <div class="flex-grow-1 lh-sm">
        <div class="small text-muted text-truncate" style="max-width: 46vw">{{ $product->name }}</div>
        <strong class="price" id="stickyPrice" style="font-size:1.15rem">{{ $inr($firstVariant ? (float) ($firstVariant->sale_price ?? $firstVariant->price) : $now) }}</strong>
    </div>
    <button type="submit" form="buyForm" class="btn btn-outline-primary" {{ $inStock ? '' : 'disabled' }} aria-label="{{ __('pages.product.add_to_cart') }}"><i class="bi bi-bag-plus"></i></button>
    <button type="submit" form="buyForm" name="buy_now" value="1" class="btn btn-accent" {{ $inStock ? '' : 'disabled' }}><i class="bi bi-lightning-charge"></i> {{ __('pages.product.buy_now') }}</button>
</div>
@endif
@endsection

@push('scripts')
<script>
    (function () {
        // Same formatting as the server: rupees, or an approximate amount in the display currency.
        var M = window.MONEY || { foreign: false, symbol: '₹', after: false, rate: 1 };
        var T = { onlyLeft: @json(__('pages.product.only_left', ['count' => ':count'])), copied: @json(__('pages.product.link_copied')),
            total: @json(__('pages.product.bulk_total', ['count' => ':count', 'total' => ':total'])), each: @json(__('pages.product.bulk_each', ['price' => ':price'])),
            saving: @json(__('pages.product.bulk_saving', ['amount' => ':amount'])), next: @json(__('pages.product.bulk_next', ['more' => ':more', 'pct' => ':pct'])) };
        // Bulk pricing tiers (minimum quantity => percent off each unit), same rule the cart and checkout use.
        var TIERS = @json((object) $bulkTiers), unitPrice = {{ json_encode(round($unitStart, 2)) }};
        var fmt = function (n) {
            if (!M.foreign) return '₹' + Math.round(n).toLocaleString('en-IN');
            var v = n * M.rate, s = v.toLocaleString('en-US', { maximumFractionDigits: v >= 100 ? 0 : 2, minimumFractionDigits: v >= 100 ? 0 : 2 });
            return '≈ ' + (M.after ? s + ' ' + M.symbol : M.symbol + s);
        };

        // Gallery
        var main = document.getElementById('mainImage');
        document.querySelectorAll('.thumbs button').forEach(function (b) {
            b.addEventListener('click', function () {
                document.querySelectorAll('.thumbs button').forEach(function (x) { x.classList.remove('active'); });
                b.classList.add('active'); if (main) main.src = b.dataset.src;
            });
        });

        // Variants: swap price, stock, max quantity and the form action
        var form = document.getElementById('buyForm'), qty = document.getElementById('qtyInput');
        function apply(r) {
            form.action = r.dataset.action;
            var price = parseFloat(r.dataset.price), old = r.dataset.old ? parseFloat(r.dataset.old) : null, stock = parseInt(r.dataset.stock, 10);
            unitPrice = price;
            document.getElementById('priceNow').textContent = fmt(price);
            var sp = document.getElementById('stickyPrice'); if (sp) sp.textContent = fmt(price);
            var oldEl = document.getElementById('priceOld'), offEl = document.getElementById('priceOff');
            oldEl.hidden = offEl.hidden = !old;
            if (old) { oldEl.textContent = fmt(old); offEl.textContent = '-' + Math.round((1 - price / old) * 100) + '%'; }
            var minQ = parseInt(qty.min, 10) || 1;
            qty.max = Math.max(stock, minQ); if (parseInt(qty.value, 10) > stock) qty.value = Math.max(stock, minQ);
            document.getElementById('stockLeft').textContent = stock > 0 && stock <= 5 ? T.onlyLeft.replace(':count', stock) : '';
            bulk();
        }

        // Bulk pricing: live unit price and total for the chosen quantity, and a nudge towards the next tier.
        function offFor(q) { var off = 0; Object.keys(TIERS).forEach(function (m) { if (q >= parseInt(m, 10)) off = parseFloat(TIERS[m]); }); return off; }
        function bulk() {
            var live = document.getElementById('bulkLive'); if (!live || !qty) return;
            var q = Math.max(1, parseInt(qty.value, 10) || 1), off = offFor(q), each = Math.round(unitPrice * (100 - off)) / 100;
            var html = '';
            if (Object.keys(TIERS).length) {
                html = '<strong>' + T.total.replace(':count', q).replace(':total', fmt(each * q)) + '</strong> <span class="text-muted">(' + T.each.replace(':price', fmt(each)) + ')</span>';
                if (off > 0) html += '<br><span class="text-success"><i class="bi bi-tag"></i> ' + T.saving.replace(':amount', fmt((unitPrice - each) * q)) + '</span>';
                var nextMin = Object.keys(TIERS).map(Number).filter(function (m) { return m > q; }).sort(function (a, b) { return a - b; })[0];
                if (nextMin && nextMin <= (parseInt(qty.max, 10) || nextMin)) html += '<br><span class="text-accent"><i class="bi bi-plus-circle"></i> ' + T.next.replace(':more', nextMin - q).replace(':pct', TIERS[nextMin]) + '</span>';
            }
            live.innerHTML = html;
            document.querySelectorAll('.bulk-tier').forEach(function (b) {
                var m = parseInt(b.dataset.min, 10), o = parseFloat(b.dataset.off);
                var tierOff = offFor(q);
                b.classList.toggle('active', o === tierOff && q >= m);
                b.disabled = m > (parseInt(qty.max, 10) || m);
                var p = b.querySelector('.bt-price'); if (p) p.textContent = fmt(Math.round(unitPrice * (100 - o)) / 100);
            });
        }
        document.querySelectorAll('.bulk-tier').forEach(function (b) {
            b.addEventListener('click', function () {
                var m = parseInt(b.dataset.min, 10), max = parseInt(qty.max, 10) || m;
                qty.value = Math.min(Math.max(m, parseInt(qty.min, 10) || 1), max);
                qty.dispatchEvent(new Event('change', { bubbles: true }));
                bulk();
            });
        });
        if (qty) {
            ['input', 'change'].forEach(function (ev) { qty.addEventListener(ev, bulk); });
            // The +/- buttons change the value from another script, so re-read it right after their click.
            document.querySelectorAll('[data-qty] [data-step]').forEach(function (b) { b.addEventListener('click', function () { setTimeout(bulk, 0); }); });
        }
        bulk();
        var picked = document.querySelector('#variantPicker input:checked');
        document.querySelectorAll('#variantPicker input').forEach(function (r) { r.addEventListener('change', function () { apply(r); }); });
        if (picked) apply(picked);

        // Jump to reviews tab from the rating link
        document.querySelectorAll('[data-tab]').forEach(function (a) {
            a.addEventListener('click', function () { var t = document.getElementById(a.dataset.tab); if (t) new bootstrap.Tab(t).show(); });
        });

        // Share
        var share = document.getElementById('shareBtn');
        share.addEventListener('click', function () {
            var data = { title: document.title, url: location.href };
            if (navigator.share) { navigator.share(data).catch(function () {}); }
            else if (navigator.clipboard) { navigator.clipboard.writeText(location.href).then(function () { share.innerHTML = '<i class="bi bi-check2"></i> ' + T.copied; }); }
        });
    })();
</script>
@endpush
