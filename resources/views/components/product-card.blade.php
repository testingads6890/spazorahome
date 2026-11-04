@php
    /** @var \App\Models\Product $product */
    $price = (float) $product->price;
    $sale = $product->sale_price !== null && (float) $product->sale_price < $price ? (float) $product->sale_price : null;
    $off = $sale ? (int) round((1 - $sale / $price) * 100) : 0;
    $out = $product->stock_quantity <= 0;
    $isNew = $product->created_at && $product->created_at->gt(now()->subDays(14));
    $photos = $product->photos();
    $image = $photos->get(0);
    $hover = $photos->get(1);
    $rating = (float) ($product->reviews_avg_rating ?? 0);
    $reviews = (int) ($product->reviews_count ?? 0);
    $variants = (int) ($product->variants_count ?? 0);
    // One quiet line under the name: what it is made of, else where it belongs.
    $facts = collect([$product->material, $product->color])->filter()->take(2)->implode(' · ') ?: $product->category?->name;
    // The lines under the price (saving, stock, delivery, seller...). Nothing the card already shows is repeated.
    $lines = \App\Support\ProductFacts::for($product);
    // A row of cards on the home page shows three of them; only those are sent.
    if (! empty($factLimit)) { $lines = array_slice($lines, 0, (int) $factLimit); }
@endphp
<article class="pcard{{ $out ? ' is-out' : '' }}">
    <a class="media" href="{{ route('products.show', $product) }}" aria-label="{{ $product->name }}">
        @if($image)
            <img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" width="640" height="800" loading="lazy" decoding="async">
            @if($hover)<img class="alt" data-src="{{ $hover['url'] }}" alt="" decoding="async" aria-hidden="true">@endif
        @else
            <span class="ph"><i class="bi bi-house-heart"></i></span>
        @endif
        <span class="badges">
            @if($out)<span class="pill out">{{ __('shop.card.sold_out') }}</span>@endif
            @if($off > 0 && ! $out)<span class="pill">-{{ $off }}%</span>@endif
            @if($isNew && ! $out)<span class="pill new">{{ __('shop.card.new') }}</span>@endif
        </span>
    </a>
    <div class="quick">
        <form action="{{ route('customer.wishlists.store', $product) }}" method="POST">@csrf
            <button type="submit" class="keep" title="{{ __('shop.card.add_to_wishlist') }}" aria-label="{{ __('shop.card.add_to_wishlist') }}"><i class="bi bi-heart"></i></button>
        </form>
        <button type="button" data-quick-view="{{ route('products.quick-view', $product) }}" title="{{ __('shop.card.quick_view') }}" aria-label="{{ __('shop.card.quick_view') }} {{ $product->name }}"><i class="bi bi-eye"></i></button>
       
    </div>
    <div class="body">
        <a class="name" href="{{ route('products.show', $product) }}">{{ $product->name }}</a>
        @if($facts)<span class="cat">{{ $facts }}</span>@endif
        <p class="desc mb-0">{{ \App\Support\Html::text($product->short_description ?: $product->description, 120) }}</p>
        <div class="meta">
            @if($reviews > 0)
                <span class="stars" title="{{ number_format($rating, 1) }} out of 5">
                    @for($i = 1; $i <= 5; $i++)<i class="bi bi-star{{ $rating >= $i ? '-fill' : ($rating >= $i - .5 ? '-half' : '') }}"></i>@endfor
                    <small>({{ $reviews }})</small>
                </span>
            @endif
            @if($variants > 1)<span class="opts"><i class="bi bi-palette2"></i> {{ trans_choice('shop.card.options', $variants, ['count' => $variants]) }}</span>@endif
        </div>
        <div class="buy">
            <div class="price-row">
                <span class="price">@price($sale ?? $price)</span>
                @if($sale)<span class="old-price">@price($price)</span>@endif
            </div>
            <div class="add">
                @if($out || (auth()->check() && ! auth()->user()->hasRole('customer')))
                    <a class="btn-add ghost" href="{{ route('products.show', $product) }}" title="{{ __('shop.card.view_details') }}"><i class="bi bi-arrow-right"></i><span>{{ __('shop.card.view_details') }}</span></a>
                @elseif($variants > 0)
                    <a class="btn-add ghost" href="{{ route('products.show', $product) }}" title="{{ __('shop.card.select_options') }}"><i class="bi bi-sliders2"></i><span>{{ __('shop.card.select_options') }}</span></a>
                @else
                    <form action="{{ route('customer.cart.store', $product) }}" method="POST">@csrf
                        <button type="submit" class="btn-add" title="{{ __('shop.card.add_to_cart') }}"><i class="bi bi-bag-plus"></i><span>{{ __('shop.card.add_to_cart') }}</span></button>
                    </form>
                @endif
            </div>
        </div>
        @if($lines)
            <ul class="pfacts">
                @foreach($lines as $line)
                    <li @class(['t-' . $line['tone'] => $line['tone'] !== '']) data-fact="{{ $line['key'] }}"><i class="bi {{ $line['icon'] }}" aria-hidden="true"></i>@if($line['url'])<a href="{{ $line['url'] }}">{{ $line['text'] }}</a>@else<span>{{ $line['text'] }}</span>@endif</li>
                @endforeach
            </ul>
        @endif
    </div>
</article>
