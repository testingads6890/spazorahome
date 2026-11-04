{{-- A titled row of product cards that scrolls sideways (swipe on touch, arrows with a mouse). --}}
<section class="section pt-0">
    <div class="container">
        <div class="section-head">
            <div><h2>{{ $title }}</h2><p>{{ $text }}</p></div>
            <a class="link-arrow" href="{{ $moreUrl }}">{{ $more }} <i class="bi bi-arrow-right"></i></a>
        </div>
        @if($items->isEmpty())
            <div class="empty"><i class="bi bi-box2"></i><p>{{ __('pages.home.coming_soon') }}</p></div>
        @else
            <div class="rail" data-rail>
                <button type="button" class="rail-btn prev" data-rail-prev aria-label="{{ __('pages.home.previous') }}" hidden><i class="bi bi-chevron-left"></i></button>
                <div class="rail-track product-rail" data-rail-track>
                    @foreach($items as $product)
                        @include('components.product-card', ['product' => $product])
                    @endforeach
                </div>
                <button type="button" class="rail-btn next" data-rail-next aria-label="{{ __('pages.home.next') }}" hidden><i class="bi bi-chevron-right"></i></button>
            </div>
        @endif
    </div>
</section>
