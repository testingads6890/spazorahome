@extends('layouts.app')

@php
    $view = request('view') === 'list' ? 'list' : 'grid';
    $title = request('q') ? __('pages.search.results_for', ['q' => request('q')]) : ($activeCategory?->name ?? __('pages.search.title'));
    $chips = collect([
        'q' => request('q') ? __('pages.search.search_prefix', ['q' => request('q')]) : null,
        'category' => $activeCategory?->name,
        'brand' => request('brand'), 'material' => request('material'), 'color' => request('color'),
        'size' => request('size'), 'style' => request('style'), 'type' => request('type'),
        'seller' => request('seller') ? $sellers->firstWhere('slug', request('seller'))?->business_name ?? request('seller') : null,
        'in_stock' => request('in_stock') ? __('pages.search.in_stock') : null,
        'featured' => request('featured') ? __('pages.search.featured') : null,
        'new' => request('new') ? __('pages.search.new_arrivals') : null,
        'min_price' => request('min_price') ? __('pages.search.min_chip', ['v' => request('min_price')]) : null,
        'max_price' => request('max_price') ? __('pages.search.max_chip', ['v' => request('max_price')]) : null,
        'on_sale' => request('on_sale') ? __('pages.search.on_sale') : null,
    ])->filter();
@endphp

@section('title', $title . ' - SpazoraHome')
@section('meta_description', $activeCategory ? ($activeCategory->meta_description ?: \Illuminate\Support\Str::limit(($activeCategory->description ? \App\Support\Html::text($activeCategory->description) . '. ' : '') . 'Shop ' . $activeCategory->name . ' furniture and decor online at SpazoraHome, delivered across India.', 158)) : 'Shop furniture and home decor online: sofas, beds, dining, lighting, rugs and more, delivered across India with a GST invoice.')
@if(request('q') || request('page') > 1 || collect(request()->query())->except(['page', 'view'])->isNotEmpty())
    {{-- Filtered, sorted and searched listings are views of the same page: keep only the plain listing in search engines. --}}
    @push('head')<meta name="robots" content="noindex,follow">@endpush
@endif
@if($activeCategory)
    @push('head')
    <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => collect([[__('pages.product.home'), route('home')], [__('shop.nav.shop'), route('search')]])->when($parentCategory, fn ($c) => $c->push([$parentCategory->name, $parentCategory->shopUrl()]))->push([$activeCategory->name, $activeCategory->shopUrl()])->values()->map(fn ($c, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => $c[1]])->all()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endpush
@endif

@section('content')
@php
    // Address of the listing for a category (or all products) with the current filters, changed by $set. A null value drops a filter.
    $shop = fn (?\App\Models\Category $cat, array $set = []) => route('search', array_filter(['category' => $cat?->urlSlug()] + $set + ['page' => null] + request()->except('category'), fn ($v) => $v !== null && $v !== ''));
    $q = fn (array $set) => $shop($activeCategory, $set);
    $sorts = ['' => __('pages.search.sort_featured'), 'newest' => __('pages.search.sort_newest'), 'price_asc' => __('pages.search.sort_price_asc'), 'price_desc' => __('pages.search.sort_price_desc'), 'name_asc' => __('pages.search.sort_name'), 'rating' => __('pages.search.sort_rating')];
    $bands = [[null, 1000], [1000, 5000], [5000, 15000], [15000, 40000], [40000, null]];
    $bandLabel = fn ($b) => $b[0] === null ? __('pages.search.under', ['v' => '₹' . number_format($b[1])]) : ($b[1] === null ? __('pages.search.over', ['v' => '₹' . number_format($b[0])]) : '₹' . number_format($b[0]) . ' - ₹' . number_format($b[1]));
    $priceOn = request('min_price') || request('max_price');
    $facets = collect(['type' => [__('pages.search.type'), $types], 'material' => [__('pages.search.material'), $materials], 'color' => [__('pages.search.colour'), $colors], 'size' => [__('pages.search.size'), $sizes], 'style' => [__('pages.search.style'), $styles], 'brand' => [__('pages.search.brand'), $brands]])->filter(fn ($f) => $f[1]->isNotEmpty());
    $shown = $products->count() ? $products->lastItem() : 0;
@endphp
<div class="page-head list-head">
    <div class="container">
        <nav aria-label="breadcrumb"><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('pages.product.home') }}</a></li>
            @if($activeCategory)<li class="breadcrumb-item"><a href="{{ route('search') }}">{{ __('shop.nav.shop') }}</a></li>@if($parentCategory)<li class="breadcrumb-item"><a href="{{ $shop($parentCategory) }}">{{ $parentCategory->name }}</a></li>@endif<li class="breadcrumb-item active">{{ $activeCategory->name }}</li>
            @else<li class="breadcrumb-item active">{{ __('shop.nav.shop') }}</li>@endif
        </ol></nav>
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-2">
            <h1>{{ $title }}</h1>
            <span class="count">{{ __('pages.search.products_count', ['count' => number_format($products->total())]) }}</span>
        </div>
        @if($activeCategory?->description)<p class="intro">{{ \App\Support\Html::text($activeCategory->description, 220) }}</p>@endif
        <div class="rail mt-3" data-rail>
            <button type="button" class="rail-btn prev sm" data-rail-prev aria-label="{{ __('pages.home.previous') }}" hidden><i class="bi bi-chevron-left"></i></button>
            <div class="rail-track cat-chips" data-rail-track>
                <a class="cat-chip {{ $activeCategory ? '' : 'active' }}" href="{{ $shop(null) }}">{{ __('pages.search.all_categories') }}</a>
                @foreach($categories as $cat)
                    <a class="cat-chip {{ $activeCategory?->id === $cat->id ? 'active' : '' }}" href="{{ $shop($cat) }}" @if($activeCategory?->id === $cat->id) aria-current="true" @endif>{{ $cat->name }}</a>
                @endforeach
            </div>
            <button type="button" class="rail-btn next sm" data-rail-next aria-label="{{ __('pages.home.next') }}" hidden><i class="bi bi-chevron-right"></i></button>
        </div>
        @if($subcategories->isNotEmpty())
            <div class="d-flex flex-wrap align-items-center gap-2 mt-2" aria-label="{{ __('pages.search.subcategories') }}">
                <span class="small text-muted fw-semibold">{{ __('pages.search.subcategories') }}:</span>
                @foreach($subcategories as $sub)
                    <a class="chip {{ $activeCategory?->id === $sub->id ? 'active' : '' }}" href="{{ $shop($sub) }}" @if($activeCategory?->id === $sub->id) aria-current="true" @endif>{{ $sub->name }}</a>
                @endforeach
            </div>
        @endif
    </div>
</div>

<div class="filter-bar">
    <div class="container">
        <div class="bar">
            <button class="fpill solid" type="button" data-bs-toggle="offcanvas" data-bs-target="#filters" aria-controls="filters">
                <i class="bi bi-sliders"></i> {{ __('pages.search.filters') }} @if($chips->count())<span class="n">{{ $chips->count() }}</span>@endif
            </button>
            <div class="dropdown">
                <button class="fpill" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">{{ __('pages.search.sort_by') }}: <strong>{{ $sorts[(string) request('sort')] ?? $sorts[''] }}</strong> <i class="bi bi-chevron-down"></i></button>
                <ul class="dropdown-menu">
                    @foreach($sorts as $k => $l)<li><a class="dropdown-item {{ (string) request('sort') === (string) $k ? 'active' : '' }}" href="{{ $q(['sort' => $k ?: null]) }}">{{ $l }}</a></li>@endforeach
                </ul>
            </div>
            <div class="dropdown">
                <button class="fpill {{ $priceOn ? 'on' : '' }}" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">{{ __('pages.search.price') }} <i class="bi bi-chevron-down"></i></button>
                <ul class="dropdown-menu">
                    @foreach($bands as $b)<li><a class="dropdown-item {{ (string) request('min_price') === (string) $b[0] && (string) request('max_price') === (string) $b[1] ? 'active' : '' }}" href="{{ $q(['min_price' => $b[0], 'max_price' => $b[1]]) }}">{{ $bandLabel($b) }}</a></li>@endforeach
                    @if($priceOn)<li><hr class="dropdown-divider"></li><li><a class="dropdown-item" href="{{ $q(['min_price' => null, 'max_price' => null]) }}">{{ __('pages.search.any') }}</a></li>@endif
                </ul>
            </div>
            @foreach($facets as $field => [$label, $options])
                <div class="dropdown d-none d-md-block">
                    <button class="fpill {{ request($field) ? 'on' : '' }}" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">{{ $label }}@if(request($field)): <strong>{{ request($field) }}</strong>@endif <i class="bi bi-chevron-down"></i></button>
                    <ul class="dropdown-menu scroll">
                        <li><a class="dropdown-item {{ request($field) ? '' : 'active' }}" href="{{ $q([$field => null]) }}">{{ __('pages.search.any') }}</a></li>
                        @foreach($options as $opt)<li><a class="dropdown-item {{ request($field) === $opt ? 'active' : '' }}" href="{{ $q([$field => $opt]) }}">{{ $opt }}</a></li>@endforeach
                    </ul>
                </div>
            @endforeach
            <a class="fpill {{ request('on_sale') ? 'on' : '' }}" href="{{ $q(['on_sale' => request('on_sale') ? null : 1]) }}" @if(request('on_sale')) aria-pressed="true" @endif><i class="bi bi-tag"></i> {{ __('pages.search.on_sale') }}</a>
            <a class="fpill {{ request('in_stock') ? 'on' : '' }}" href="{{ $q(['in_stock' => request('in_stock') ? null : 1]) }}" @if(request('in_stock')) aria-pressed="true" @endif><i class="bi bi-check2-circle"></i> {{ __('pages.search.in_stock') }}</a>
            <a class="fpill d-none d-sm-inline-flex {{ request('new') ? 'on' : '' }}" href="{{ $q(['new' => request('new') ? null : 1]) }}" @if(request('new')) aria-pressed="true" @endif><i class="bi bi-stars"></i> {{ __('pages.search.new_arrivals') }}</a>
            <a class="fpill d-none d-sm-inline-flex {{ request('featured') ? 'on' : '' }}" href="{{ $q(['featured' => request('featured') ? null : 1]) }}" @if(request('featured')) aria-pressed="true" @endif><i class="bi bi-award"></i> {{ __('pages.search.featured') }}</a>
            <div class="view-toggle ms-auto" role="group" aria-label="{{ __('pages.search.grid_view') }}">
                <a href="{{ $q(['view' => 'grid']) }}" class="{{ $view === 'grid' ? 'active' : '' }}" aria-label="{{ __('pages.search.grid_view') }}"><i class="bi bi-grid-3x3-gap"></i></a>
                <a href="{{ $q(['view' => 'list']) }}" class="{{ $view === 'list' ? 'active' : '' }}" aria-label="{{ __('pages.search.list_view') }}"><i class="bi bi-list-ul"></i></a>
            </div>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-start" tabindex="-1" id="filters" aria-labelledby="filtersLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="filtersLabel">{{ __('pages.search.filters') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('pages.search.filters') }}"></button>
    </div>
    <div class="offcanvas-body">
        <form method="GET" action="{{ route('search') }}" class="filters">
                        <input type="hidden" name="view" value="{{ $view }}">
                        @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif

                        <div class="filter-block pt-0">
                            <h6>{{ __('pages.search.keyword') }}</h6>
                            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="{{ __('pages.search.keyword_placeholder') }}">
                        </div>

                        <div class="filter-block">
                            <h6>{{ __('pages.search.category') }}</h6>
                            <label><input type="radio" name="category" value="" {{ $activeCategory ? '' : 'checked' }}> {{ __('pages.search.all_categories') }}</label>
                            @foreach($categories as $cat)
                                <label><input type="radio" name="category" value="{{ $cat->urlSlug() }}" {{ $activeCategory?->id === $cat->id ? 'checked' : '' }}> {{ $cat->name }}</label>
                                @if($subcategories->isNotEmpty() && $subcategories->first()->parent_id === $cat->id)
                                    @foreach($subcategories as $sub)
                                        <label class="ps-4"><input type="radio" name="category" value="{{ $sub->urlSlug() }}" {{ $activeCategory?->id === $sub->id ? 'checked' : '' }}> {{ $sub->name }}</label>
                                    @endforeach
                                @endif
                            @endforeach
                        </div>

                        <div class="filter-block">
                            <h6>{{ __('pages.search.price') }}</h6>
                            <div class="d-flex gap-2">
                                <input type="number" min="0" name="min_price" value="{{ request('min_price') }}" class="form-control" placeholder="{{ __('pages.search.min') }}" aria-label="{{ __('pages.search.min_price') }}">
                                <input type="number" min="0" name="max_price" value="{{ request('max_price') }}" class="form-control" placeholder="{{ __('pages.search.max') }}" aria-label="{{ __('pages.search.max_price') }}">
                            </div>
                            <label class="mt-2"><input type="checkbox" name="on_sale" value="1" {{ request('on_sale') ? 'checked' : '' }}> {{ __('pages.search.on_sale_only') }}</label>
                        </div>

                        <div class="filter-block">
                            <h6>{{ __('pages.search.availability') }}</h6>
                            <label><input type="checkbox" name="in_stock" value="1" {{ request('in_stock') ? 'checked' : '' }}> {{ __('pages.search.in_stock') }}</label>
                            <label><input type="checkbox" name="new" value="1" {{ request('new') ? 'checked' : '' }}> {{ __('pages.search.new_arrivals') }}</label>
                            <label><input type="checkbox" name="featured" value="1" {{ request('featured') ? 'checked' : '' }}> {{ __('pages.search.featured') }}</label>
                        </div>

                        @if($sellers->count() > 1 || request('seller'))
                            <div class="filter-block">
                                <h6>{{ __('pages.search.seller') }}</h6>
                                <select name="seller" class="form-select">
                                    <option value="">{{ __('pages.search.any') }}</option>
                                    @foreach($sellers as $s)<option value="{{ $s->slug }}" {{ request('seller') === $s->slug ? 'selected' : '' }}>{{ $s->business_name }}</option>@endforeach
                                </select>
                            </div>
                        @endif

                        @foreach(['type' => [__('pages.search.type'), $types], 'brand' => [__('pages.search.brand'), $brands], 'material' => [__('pages.search.material'), $materials], 'color' => [__('pages.search.colour'), $colors], 'size' => [__('pages.search.size'), $sizes], 'style' => [__('pages.search.style'), $styles]] as $field => [$label, $options])
                            @if($options->isNotEmpty())
                                <div class="filter-block">
                                    <h6>{{ $label }}</h6>
                                    <select name="{{ $field }}" class="form-select">
                                        <option value="">{{ __('pages.search.any') }}</option>
                                        @foreach($options as $opt)
                                            <option value="{{ $opt }}" {{ request($field) === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        @endforeach

                        <div class="d-grid gap-2 mt-2">
                            <button class="btn btn-primary">{{ __('pages.search.apply') }}</button>
                            <a href="{{ route('search') }}" class="btn btn-outline-primary">{{ __('pages.search.clear_all') }}</a>
                        </div>
                    </form>
    </div>
</div>

<div class="container py-4">
    @if($chips->isNotEmpty())
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            @foreach($chips as $key => $label)
                <a class="chip" href="{{ $key === 'category' ? $shop(null) : $q([$key => null]) }}">{{ $label }} <i class="bi bi-x-lg"></i></a>
            @endforeach
            <a class="small fw-semibold ms-1" href="{{ route('search') }}">{{ __('pages.search.clear_all') }}</a>
        </div>
    @endif

    @if($products->count() > 0)
        <div class="product-grid wide {{ $view === 'list' ? 'list-view' : '' }}">
            @foreach($products as $product)
                @include('components.product-card', ['product' => $product])
            @endforeach
        </div>
        <div class="list-foot">
            <p class="mb-2">{{ __('pages.search.showing', ['shown' => number_format($shown), 'total' => number_format($products->total())]) }}</p>
            <div class="progress" role="progressbar" aria-valuenow="{{ $shown }}" aria-valuemin="0" aria-valuemax="{{ $products->total() }}"><div class="progress-bar" style="width: {{ $products->total() ? round($shown / $products->total() * 100) : 0 }}%"></div></div>
            @if($products->hasMorePages())<a class="btn btn-primary mt-3" href="{{ $products->nextPageUrl() }}">{{ __('pages.search.next_page') }} <i class="bi bi-arrow-right"></i></a>@endif
        </div>
        @if($products->lastPage() > 1)<div class="pager">{{ $products->onEachSide(1)->links() }}</div>@endif
    @else
        <div class="empty">
            <i class="bi bi-search"></i>
            <h4 class="mt-3">{{ __('pages.search.no_results') }}</h4>
            <p>{{ __('pages.search.no_results_text') }}</p>
            <a href="{{ route('search') }}" class="btn btn-primary">{{ __('pages.search.clear_filters') }}</a>
        </div>
    @endif
</div>
@endsection
