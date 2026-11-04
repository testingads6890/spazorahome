@extends('layouts.app')

@section('title', $vendor->business_name . ' - SpazoraHome')
@section('meta_description', \App\Support\Html::text($vendor->description, 155) ?: 'Shop ' . $vendor->business_name . ' on SpazoraHome.')

@php $place = collect([$vendor->city, $vendor->state])->filter()->implode(', '); @endphp

@section('content')
<section class="store-hero" @if($vendor->coverUrl()) style="background:linear-gradient(rgba(255,255,255,.82),rgba(255,255,255,.9)),url('{{ $vendor->coverUrl() }}') center/cover" @endif>
    <div class="container">
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-3"><li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li><li class="breadcrumb-item"><a href="{{ route('stores.index') }}">Stores</a></li><li class="breadcrumb-item active">{{ $vendor->business_name }}</li></ol></nav>
        <div class="d-flex flex-wrap align-items-center gap-3">
            @if($vendor->logoUrl())
                <img src="{{ $vendor->logoUrl() }}" alt="{{ $vendor->business_name }} logo" class="logo lg" style="object-fit:cover;padding:0" width="72" height="72">
            @else
                <span class="logo lg">{{ mb_strtoupper(mb_substr($vendor->business_name, 0, 1)) }}</span>
            @endif
            <div>
                <h1 class="mb-1">{{ $vendor->business_name }}</h1>
                <div class="text-muted small d-flex flex-wrap gap-3">
                    @if($place)<span><i class="bi bi-geo-alt"></i> {{ $place }}</span>@endif
                    <span><i class="bi bi-box-seam"></i> {{ number_format($total) }} {{ \Illuminate\Support\Str::plural('product', $total) }}</span>
                    <span><i class="bi bi-calendar3"></i> Selling since {{ ($vendor->approved_at ?? $vendor->created_at)->format('M Y') }}</span>
                    @php $storeRating = \App\Models\Review::where('is_approved', true)->whereHas('product', fn ($q) => $q->where('vendor_id', $vendor->id)); $rc = (clone $storeRating)->count(); @endphp
                    @if($rc)<span><i class="bi bi-star-fill text-warning"></i> {{ number_format((float) (clone $storeRating)->avg('rating'), 1) }} ({{ $rc }} {{ \Illuminate\Support\Str::plural('review', $rc) }})</span>@endif
                </div>
                @if($vendor->socialLinks())
                    <div class="d-flex gap-2 mt-2">
                        @foreach($vendor->socialLinks() as $net => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener nofollow" class="btn btn-sm btn-outline-secondary" aria-label="{{ \App\Models\Vendor::SOCIAL[$net][0] }}"><i class="bi {{ \App\Models\Vendor::SOCIAL[$net][1] }}"></i></a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

<div class="container py-4">
    @if($vendor->description)<div class="text-muted mb-4 rich-content" style="max-width:820px">{!! \App\Support\Html::render($vendor->description) !!}</div>@endif
    <div class="toolbar">
        <span class="count">{{ number_format($products->total()) }} products</span>
        <form method="GET" class="ms-auto">
            <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Sort products">
                @foreach(['' => 'Newest first', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'name_asc' => 'Name: A to Z'] as $k => $l)
                    <option value="{{ $k }}" {{ (string) request('sort') === (string) $k ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if($products->isNotEmpty())
        <div class="product-grid cols-4">
            @foreach($products as $product)@include('components.product-card', ['product' => $product])@endforeach
        </div>
        <div class="d-flex justify-content-center mt-4">{{ $products->links() }}</div>
    @else
        <div class="empty"><i class="bi bi-box2"></i><h5 class="mt-3">No products yet</h5><p>This store has not listed anything that is live right now.</p><a href="{{ route('stores.index') }}" class="btn btn-primary">Browse other stores</a></div>
    @endif
</div>
@endsection
