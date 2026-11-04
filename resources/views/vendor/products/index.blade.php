@extends('layouts.vendor')

@section('title', 'My products')

@php
    $tabs = ['all' => 'All', 'approved' => 'Live', 'pending' => 'Waiting for approval', 'changes_requested' => 'Changes requested', 'rejected' => 'Rejected', 'low_stock' => 'Low stock'];
    $badge = ['approved' => ['success', 'Live'], 'pending' => ['warning', 'Waiting for approval'], 'changes_requested' => ['info', 'Changes requested'], 'rejected' => ['danger', 'Rejected']];
@endphp

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <form method="GET" class="d-flex gap-2" role="search">
        @if($tab !== 'all')<input type="hidden" name="status" value="{{ $tab }}">@endif
        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name or SKU" aria-label="Search products" style="min-width:200px">
        <button class="btn btn-outline-primary">Search</button>
    </form>
    <a href="{{ route('vendor.products.import') }}" class="btn btn-outline-primary"><i class="bi bi-file-earmark-excel"></i> Import from Excel</a>
    <a href="{{ route('vendor.products.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add product</a>
</div>

<ul class="nav nav-pills flex-nowrap overflow-auto gap-1 mb-3 pb-1">
    @foreach($tabs as $key => $label)
        <li class="nav-item"><a class="nav-link text-nowrap {{ $tab === $key ? 'active' : '' }}" href="{{ route('vendor.products.index', $key === 'all' ? [] : ['status' => $key]) }}">{{ $label }} <span class="badge {{ $tab === $key ? 'text-bg-light' : 'text-bg-secondary' }}">{{ $counts[$key] }}</span></a></li>
    @endforeach
</ul>

<div class="table-responsive">
<table class="table table-bordered align-middle">
    <thead><tr><th style="width:64px"></th><th>Product</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr></thead>
    <tbody>
        @forelse($products as $product)
            @php [$tone, $label] = $badge[$product->vendor_status] ?? ['secondary', ucfirst((string) $product->vendor_status)]; $pic = $product->photo(); @endphp
            <tr>
                <td>@if($pic)<img src="{{ $pic['thumb'] }}" alt="" width="48" height="48" style="object-fit:cover;border-radius:8px" loading="lazy">@endif</td>
                <td>
                    <strong>{{ $product->name }}</strong>
                    <div class="small text-muted">{{ $product->sku }} &middot; {{ $product->category?->name }}</div>
                    @if(in_array($product->vendor_status, ['rejected', 'changes_requested'], true) && $product->vendor_rejection_reason)
                        <div class="small text-danger mt-1"><i class="bi bi-chat-left-text"></i> {{ $product->vendor_rejection_reason }}</div>
                    @endif
                </td>
                <td class="text-nowrap">₹{{ number_format((float) ($product->sale_price ?? $product->price), 2) }}@if($product->sale_price)<div class="small text-muted text-decoration-line-through">₹{{ number_format((float) $product->price, 2) }}</div>@endif</td>
                <td>
                    {{ $product->stock_quantity }}
                    @if($product->stock_quantity <= 0)<span class="badge text-bg-danger">Out</span>@elseif($product->stock_quantity <= $product->low_stock_threshold)<span class="badge text-bg-warning">Low</span>@endif
                </td>
                <td>
                    <span class="badge text-bg-{{ $tone }}">{{ $label }}</span>
                    @if($product->vendor_status === 'approved' && ! $product->is_active)<span class="badge text-bg-secondary">Hidden</span>@endif
                </td>
                <td class="text-nowrap">
                    <a href="{{ route('vendor.products.edit', $product) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                    @if($product->vendor_status === 'approved' && $product->is_active)<a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">View</a>@endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No products here yet.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
{{ $products->links() }}
@endsection
