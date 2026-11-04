@extends('layouts.account')

@section('title', __('My reviews - SpazoraHome'))
@section('heading', __('My reviews'))
@section('crumb', __('Reviews'))

@section('account')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-muted mb-0">{{ __('Reviews appear on the product page once approved.') }}</p>
        <a href="{{ route('customer.reviews.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> {{ __('Write a review') }}</a>
    </div>

    @forelse($reviews as $review)
        <div class="card card-body mb-3">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    @if($review->product)<a href="{{ route('products.show', $review->product) }}" class="fw-bold">{{ $review->product->name }}</a>@else<span class="fw-bold">{{ __('Product unavailable') }}</span>@endif
                    <div class="stars my-1">@for($i = 1; $i <= 5; $i++)<i class="bi bi-star{{ $i <= $review->rating ? '-fill' : '' }}"></i>@endfor</div>
                </div>
                <span class="status-badge {{ $review->is_approved ? 'delivered' : 'pending' }}">{{ $review->is_approved ? __('Published') : __('Awaiting approval') }}</span>
            </div>
            @if($review->title)<div class="fw-semibold">{{ $review->title }}</div>@endif
            <p class="mb-2">{{ $review->comment }}</p>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span class="small text-muted">{{ $review->created_at->translatedFormat('d M Y') }}</span>
                <div class="d-flex gap-2">
                    <a href="{{ route('customer.reviews.edit', $review) }}" class="btn btn-outline-primary btn-sm">{{ __('Edit') }}</a>
                    <form action="{{ route('customer.reviews.destroy', $review) }}" method="POST" onsubmit="return confirm(@js(__('Delete this review?')))">@csrf @method('DELETE')
                        <button class="btn btn-outline-danger btn-sm">{{ __('Delete') }}</button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="empty"><i class="bi bi-star"></i><h5 class="mt-3">{{ __('No reviews yet') }}</h5><p>{{ __('Share your experience with products you bought.') }}</p><a href="{{ route('customer.reviews.create') }}" class="btn btn-primary">{{ __('Write a review') }}</a></div>
    @endforelse
    <div class="d-flex justify-content-center">{{ $reviews->links() }}</div>
@endsection
