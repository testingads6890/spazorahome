@php
    $hasVariants = $product->variants->isNotEmpty();
    $price = (float) $product->price;
    $sale = $product->sale_price !== null && (float) $product->sale_price < $price ? (float) $product->sale_price : null;
    $off = $sale ? (int) round((1 - $sale / $price) * 100) : 0;
    $out = $hasVariants ? $product->variants->sum('stock_quantity') <= 0 : $product->stock_quantity <= 0;
    $img = $product->photo();
    $from = $hasVariants ? $product->variants->map(fn ($v) => (float) ($v->sale_price ?? $v->price))->min() : null;
@endphp
<div class="row g-0">
    <div class="col-md-6">
        <div class="gallery-main" style="border-radius:18px 0 0 18px; border:0; height:100%; min-height:260px">
            @if($img)<img src="{{ $img['url'] }}" alt="{{ $img['alt'] }}">@else<i class="bi bi-house-heart"></i>@endif
        </div>
    </div>
    <div class="col-md-6 p-4">
        <button type="button" class="btn-close float-end" data-bs-dismiss="modal" aria-label="Close"></button>
        @if($product->category)<span class="small text-uppercase fw-bold text-muted">{{ $product->category->name }}</span>@endif
        <h2 class="h4 mt-1" id="quickViewTitle">{{ $product->name }}</h2>

        @if($reviewCount)
            <div class="stars mb-2">@for($i = 1; $i <= 5; $i++)<i class="bi bi-star{{ $rating >= $i ? '-fill' : ($rating >= $i - .5 ? '-half' : '') }}"></i>@endfor <small>{{ number_format($rating, 1) }} ({{ $reviewCount }})</small></div>
        @endif

        <div class="d-flex align-items-baseline gap-2 flex-wrap mb-1">
            @if($hasVariants)
                <span class="price">{{ __('pages.product.from', ['price' => \App\Support\Money::browse($from)]) }}</span>
            @else
                <span class="price">@price($sale ?? $price)</span>
                @if($sale)<span class="old-price">@price($price)</span><span class="pill">-{{ $off }}%</span>@endif
            @endif
        </div>
        @if(\App\Support\Money::isForeign())<p class="small text-accent mb-1"><i class="bi bi-info-circle"></i> {{ __('pages.product.approx_short') }}</p>@endif
        <p class="small text-muted mb-3">{{ __('pages.product.gst_short', ['rate' => rtrim(rtrim(number_format($product->taxRate(), 2), '0'), '.')]) }}</p>

        @if($product->short_description || $product->description)
            <p class="mb-3">{{ \App\Support\Html::text($product->short_description ?: $product->description, 180) }}</p>
        @endif

        @if($out)
            <div class="text-danger fw-semibold mb-3"><i class="bi bi-x-circle-fill"></i> {{ __('pages.product.out_of_stock') }}</div>
        @else
            <div class="text-success fw-semibold mb-3"><i class="bi bi-check-circle-fill"></i> {{ __('pages.product.in_stock') }}</div>
        @endif

        @if($hasVariants)
            <a href="{{ route('products.show', $product) }}" class="btn btn-primary w-100">{{ __('pages.product.choose_option') }}</a>
        @elseif(! $out && (! auth()->check() || auth()->user()->hasRole('customer')))
            <form action="{{ route('customer.cart.store', $product) }}" method="POST" class="d-flex flex-wrap gap-2">
                @csrf
                <div class="qty" data-qty>
                    <button type="button" data-step="-1" aria-label="{{ __('pages.product.decrease') }}"><i class="bi bi-dash"></i></button>
                    <input type="number" name="quantity" value="1" min="1" max="{{ $product->stock_quantity }}" aria-label="Quantity">
                    <button type="button" data-step="1" aria-label="{{ __('pages.product.increase') }}"><i class="bi bi-plus"></i></button>
                </div>
                <button class="btn btn-primary flex-grow-1"><i class="bi bi-bag-plus"></i> {{ __('pages.product.add_to_cart') }}</button>
            </form>
        @endif
        <a href="{{ route('products.show', $product) }}" class="d-inline-block mt-3 small fw-semibold">{{ __('pages.product.view_full') }} <i class="bi bi-arrow-right"></i></a>
    </div>
</div>
