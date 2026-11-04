@extends('layouts.account')

@section('title', __('Wishlist - SpazoraHome'))
@section('heading', __('My wishlist'))
@section('crumb', __('Wishlist'))

@section('account')
    @if($wishlists->isEmpty())
        <div class="empty"><i class="bi bi-heart"></i><h5 class="mt-3">{{ __('Your wishlist is empty') }}</h5><p>{{ __('Tap the heart on any product to save it for later.') }}</p><a href="{{ route('search') }}" class="btn btn-primary">{{ __('Browse products') }}</a></div>
    @else
        <p class="text-muted">{{ __(':count saved items', ['count' => $wishlists->total()]) }}</p>
        <div class="product-grid">
            @foreach($wishlists as $wishlist)
                @if($wishlist->product)
                    <div class="d-flex flex-column gap-2">
                        @include('components.product-card', ['product' => $wishlist->product])
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            @if($wishlist->product->stock_quantity > 0)
                                <form action="{{ route('customer.wishlists.move', $wishlist->product) }}" method="POST">@csrf
                                    <button class="btn btn-primary btn-sm"><i class="bi bi-bag-plus"></i> {{ __('Move to cart') }}</button>
                                </form>
                            @endif
                            <form action="{{ route('customer.wishlists.destroy', $wishlist->product) }}" method="POST">@csrf @method('DELETE')
                                <button class="btn btn-link text-danger p-0 small"><i class="bi bi-heartbreak"></i> {{ __('Remove from wishlist') }}</button>
                            </form>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
        <div class="d-flex justify-content-center mt-4">{{ $wishlists->links() }}</div>
    @endif
@endsection
