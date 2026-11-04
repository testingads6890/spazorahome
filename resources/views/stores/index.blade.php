@extends('layouts.app')

@section('title', 'Our sellers - SpazoraHome')
@section('meta_description', 'Browse trusted furniture and decor sellers on SpazoraHome.')

@section('content')
<div class="page-head"><div class="container">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li><li class="breadcrumb-item active">Stores</li></ol></nav>
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
        <div><h1>Our sellers</h1><p class="text-muted mb-0">{{ number_format($stores->total()) }} trusted {{ \Illuminate\Support\Str::plural('store', $stores->total()) }}</p></div>
        <form method="GET" class="d-flex flex-wrap gap-2" role="search">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search store or city" aria-label="Search stores" style="min-width:220px">
            <select name="sort" class="form-select w-auto" onchange="this.form.submit()" aria-label="Sort stores">
                @foreach(['name' => 'Name A-Z', 'products' => 'Most products', 'newest' => 'Newest'] as $k => $l)<option value="{{ $k }}" {{ request('sort', 'name') === $k ? 'selected' : '' }}>{{ $l }}</option>@endforeach
            </select>
            <button class="btn btn-primary">Search</button>
        </form>
    </div>
</div></div>

<div class="container py-4">
    <div class="store-grid">
        @forelse($stores as $store)
            <a class="store-card" href="{{ route('stores.show', $store) }}">
                @if($store->logoUrl())<img src="{{ $store->logoUrl() }}" alt="" class="logo" style="object-fit:cover;padding:0" width="48" height="48" loading="lazy">@else<span class="logo">{{ mb_strtoupper(mb_substr($store->business_name, 0, 1)) }}</span>@endif
                <span class="flex-grow-1 min-w-0">
                    <strong class="d-block text-truncate text-dark">{{ $store->business_name }}</strong>
                    <span class="small text-muted d-block"><i class="bi bi-geo-alt"></i> {{ collect([$store->city, $store->state])->filter()->implode(', ') ?: 'India' }}</span>
                    <span class="small text-muted">{{ number_format($store->products_count) }} {{ \Illuminate\Support\Str::plural('product', $store->products_count) }}</span>
                </span>
                <i class="bi bi-chevron-right text-muted"></i>
            </a>
        @empty
            <div class="empty" style="grid-column:1/-1"><i class="bi bi-shop"></i><h5 class="mt-3">No stores found</h5><p>Try a different search.</p><a href="{{ route('stores.index') }}" class="btn btn-primary">Clear search</a></div>
        @endforelse
    </div>
    <div class="d-flex justify-content-center mt-4">{{ $stores->links() }}</div>

    <div class="card card-body mt-5 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div><h5 class="mb-1">Want to sell on SpazoraHome?</h5><p class="mb-0 text-muted">Apply in minutes. We review every seller before they go live.</p></div>
        <a href="{{ route('vendor.register') }}" class="btn btn-accent">Become a seller</a>
    </div>
</div>
@endsection
