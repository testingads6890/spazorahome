{{-- "What our customers say": approved 4 and 5 star reviews. Shows on phone, tablet, laptop and desktop. Hidden when there are none yet. --}}
@php
    $homeReviews = \Illuminate\Support\Facades\Cache::remember('home.reviews', now()->addMinutes(10), fn () => \App\Models\Review::with(['user:id,name', 'product:id,name,slug'])
        ->where('is_approved', true)->where('rating', '>=', 4)->whereHas('product')->latest('id')->limit(6)->get());
@endphp

@if($homeReviews->isNotEmpty())
<section class="hx-section hx-reviews" aria-labelledby="hx-reviews-title">
    <div class="container">
        <span class="hx-kicker">{{ __('What our customers say') }}</span>
        <h2 id="hx-reviews-title">{{ __('Loved by our customers') }}</h2>
        @if(config('shop.google_review_url'))
            <a href="{{ config('shop.google_review_url') }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm mb-3"><i class="bi bi-google" aria-hidden="true"></i> {{ __('Review us on Google') }}</a>
        @endif

        <div class="hx-slider mt-1" data-review-slider>
            <button type="button" class="hx-slider-btn hx-slider-prev" aria-label="{{ __('Previous reviews') }}"><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
            <div class="hx-slider-track" tabindex="0" aria-label="{{ __('Customer reviews') }}">
                @foreach($homeReviews as $review)
                    <div class="hx-slide">
                        <figure class="hx-review h-100 mb-0">
                            <div class="hx-stars" role="img" aria-label="{{ $review->rating }} out of 5">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="bi {{ $i <= $review->rating ? 'bi-star-fill' : 'bi-star' }}" aria-hidden="true"></i>
                                @endfor
                            </div>
                            @if(filled($review->title))<p class="hx-review-title">{{ $review->title }}</p>@endif
                            <blockquote class="hx-review-text mb-3">{{ \Illuminate\Support\Str::limit((string) $review->comment, 170) }}</blockquote>
                            @php
                                $photo = \Illuminate\Support\Facades\Schema::hasColumn('reviews', 'images') ? (is_array($review->images) ? ($review->images[0] ?? null) : null) : null;
                                $who = \Illuminate\Support\Str::before((string) $review->user?->name, ' ') ?: __('Customer');
                            @endphp
                            <div class="hx-review-by d-flex align-items-center gap-2">
                                @if($photo)
                                    <img src="{{ asset('storage/' . ltrim($photo, '/')) }}" alt="{{ $who }}" class="hx-avatar" loading="lazy" width="44" height="44">
                                @else
                                    <span class="hx-avatar hx-avatar-initial" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($who, 0, 1)) }}</span>
                                @endif
                                <span class="d-flex flex-column">
                                <span class="fw-semibold">{{ $who }}</span>
                                @if($review->product)
                                    <span class="text-muted"> · <a href="{{ route('products.show', $review->product->slug) }}">{{ $review->product->name }}</a></span>
                                @endif
                                </span>
                            </div>
                        </figure>
                    </div>
                @endforeach
            </div>
            <button type="button" class="hx-slider-btn hx-slider-next" aria-label="{{ __('Next reviews') }}"><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
        </div>
    </div>
</section>
@endif

@once
<script>
(function () {
    'use strict';
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    document.querySelectorAll('[data-review-slider]').forEach(function (box) {
        var track = box.querySelector('.hx-slider-track');
        var prev = box.querySelector('.hx-slider-prev');
        var next = box.querySelector('.hx-slider-next');
        var timer = null;
        function step() {
            var slide = track.querySelector('.hx-slide');
            return slide ? slide.offsetWidth + parseFloat(getComputedStyle(track).columnGap || 0) : track.clientWidth;
        }
        function go(dir) {
            var max = track.scrollWidth - track.clientWidth - 4;
            if (dir > 0 && track.scrollLeft >= max) { track.scrollTo({ left: 0, behavior: reduce ? 'auto' : 'smooth' }); return; }
            if (dir < 0 && track.scrollLeft <= 4) { track.scrollTo({ left: max, behavior: reduce ? 'auto' : 'smooth' }); return; }
            track.scrollBy({ left: dir * step(), behavior: reduce ? 'auto' : 'smooth' });
        }
        function start() { if (!reduce && !timer) { timer = setInterval(function () { go(1); }, 5000); } }
        function stop() { clearInterval(timer); timer = null; }
        prev.addEventListener('click', function () { stop(); go(-1); });
        next.addEventListener('click', function () { stop(); go(1); });
        box.addEventListener('mouseenter', stop);
        box.addEventListener('mouseleave', start);
        box.addEventListener('focusin', stop);
        box.addEventListener('focusout', start);
        track.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowRight') { stop(); go(1); }
            if (e.key === 'ArrowLeft') { stop(); go(-1); }
        });
        document.addEventListener('visibilitychange', function () { document.hidden ? stop() : start(); });
        start();
    });
})();
</script>
@endonce
