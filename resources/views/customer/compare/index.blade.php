@extends(auth()->check() ? 'layouts.account' : 'layouts.app')

@section('title', __('Compare products - SpazoraHome'))
@section('heading', __('Compare products'))
@section('crumb', __('Compare'))

@php
    $rows = [__('Price') => fn ($p) => \App\Support\Money::browse((float) ($p->sale_price ?? $p->price)), __('pages.product.spec_brand') => 'brand', __('pages.product.spec_material') => 'material', __('pages.product.spec_colour') => 'color', __('pages.product.spec_finish') => 'finish', __('pages.product.spec_size') => 'size', __('pages.product.spec_dimensions') => 'dimensions', __('pages.product.spec_weight') => 'weight', __('pages.product.spec_warranty') => 'warranty',
             __('Availability') => fn ($p) => $p->stock_quantity > 0 ? __('pages.product.in_stock') : __('pages.product.out_of_stock')];
@endphp

@section(auth()->check() ? 'account' : 'content')
    @if($compares->isEmpty())
        <div class="empty"><i class="bi bi-arrow-left-right"></i><h5 class="mt-3">{{ __('Nothing to compare yet') }}</h5><p>{{ __('Use the compare button on product pages to line products up side by side.') }}</p><a href="{{ route('search') }}" class="btn btn-primary">{{ __('Browse products') }}</a></div>
    @else
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted">{{ __(':count products', ['count' => $compares->count()]) }}</span>
            <form action="{{ route('customer.compare.clear') }}" method="POST" onsubmit="return confirm(@js(__('Clear the compare list?')))">@csrf @method('DELETE')
                <button class="btn btn-outline-danger btn-sm">{{ __('Clear all') }}</button>
            </form>
        </div>

        <div class="table-responsive compare-wrap">
            <table class="table compare-table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col"></th>
                        @foreach($compares as $c)
                            @php $p = $c->product; $img = $p?->photo(); @endphp
                            <th scope="col">
                                @if($p)
                                    <a href="{{ route('products.show', $p) }}" class="d-block text-decoration-none">
                                        <span class="mini-pic">@if($img)<img src="{{ $img['thumb'] }}" alt="">@else<i class="bi bi-house-heart"></i>@endif</span>
                                        <span class="d-block fw-bold text-dark mt-2">{{ $p->name }}</span>
                                    </a>
                                    <form action="{{ route('customer.compare.destroy', $p) }}" method="POST" class="mt-1">@csrf @method('DELETE')
                                        <button class="btn btn-link text-danger p-0 small">{{ __('Remove') }}</button>
                                    </form>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $label => $get)
                        @php $vals = $compares->map(fn ($c) => $c->product ? (is_callable($get) ? $get($c->product) : ($c->product->{$get} ?: '-')) : '-'); $differs = $vals->unique()->count() > 1; @endphp
                        <tr>
                            <th scope="row" class="text-muted small text-uppercase">{{ $label }}</th>
                            @foreach($vals as $v)<td class="{{ $differs ? 'fw-semibold' : '' }}">{{ $v }}</td>@endforeach
                        </tr>
                    @endforeach
                    <tr>
                        <th scope="row"></th>
                        @foreach($compares as $c)
                            <td>
                                @if($c->product && $c->product->stock_quantity > 0 && ! $c->product->variants()->exists())
                                    <form action="{{ route('customer.cart.store', $c->product) }}" method="POST">@csrf<button class="btn btn-primary btn-sm">{{ __('Add to cart') }}</button></form>
                                @elseif($c->product)
                                    <a href="{{ route('products.show', $c->product) }}" class="btn btn-outline-primary btn-sm">{{ __('View') }}</a>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
    @endif
@endsection
