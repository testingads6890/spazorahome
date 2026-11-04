@extends('layouts.admin')

@section('title', 'Vendor products')

@php
    $tabs = [null => 'All', 'pending' => 'Waiting for review', 'changes_requested' => 'Changes requested', 'approved' => 'Approved', 'rejected' => 'Rejected'];
    $badge = ['approved' => 'success', 'pending' => 'warning', 'changes_requested' => 'info', 'rejected' => 'danger'];
    $label = ['approved' => 'Approved', 'pending' => 'Waiting for review', 'changes_requested' => 'Changes requested', 'rejected' => 'Rejected'];
@endphp

@section('content')
<ul class="nav nav-pills flex-nowrap overflow-auto gap-1 mb-3 pb-1">
    @foreach($tabs as $key => $text)
        <li class="nav-item"><a class="nav-link text-nowrap {{ $status === $key ? 'active' : '' }}" href="{{ route('admin.vendor-products.index', array_filter(['status' => $key, 'vendor_id' => request('vendor_id')])) }}">{{ $text }} <span class="badge {{ $status === $key ? 'text-bg-light' : 'text-bg-secondary' }}">{{ $key ? (int) ($counts[$key] ?? 0) : (int) $counts->sum() }}</span></a></li>
    @endforeach
</ul>

@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="table-responsive">
<table class="table table-bordered align-middle">
    <thead><tr><th style="width:64px"></th><th>Product</th><th>Seller</th><th>Price / GST</th><th>Status</th><th style="min-width:300px">Decision</th></tr></thead>
    <tbody>
        @forelse($vendorProducts as $vp)
            @php $p = $vp->product; $pic = $p->photo(); @endphp
            <tr>
                <td>@if($pic)<img src="{{ $pic['thumb'] }}" alt="" width="48" height="48" style="object-fit:cover;border-radius:8px" loading="lazy">@endif</td>
                <td>
                    <a href="{{ route('admin.products.edit', $p) }}" class="fw-semibold">{{ $p->name }}</a>
                    <div class="small text-muted">{{ $p->sku }} &middot; {{ $p->category?->name }} &middot; {{ $p->images->count() }} {{ \Illuminate\Support\Str::plural('picture', $p->images->count()) }} &middot; stock {{ $p->stock_quantity }}</div>
                    @if($vp->rejection_reason && $vp->status !== 'approved')<div class="small text-danger mt-1"><i class="bi bi-chat-left-text"></i> {{ $vp->rejection_reason }}</div>@endif
                </td>
                <td><a href="{{ route('admin.vendors.show', $vp->vendor) }}">{{ $vp->vendor->business_name }}</a></td>
                <td class="text-nowrap">₹{{ number_format((float) ($p->sale_price ?? $p->price), 2) }}<div class="small text-muted">GST {{ rtrim(rtrim(number_format($p->taxRate(), 2), '0'), '.') }}%{{ $p->tax_rate === null ? ' (default)' : '' }}</div></td>
                <td>
                    <span class="badge text-bg-{{ $badge[$vp->status] ?? 'secondary' }}">{{ $label[$vp->status] ?? ucfirst($vp->status) }}</span>
                    @if($vp->status === 'approved')
                        <div class="small mt-1">{{ $p->is_active ? 'Published' : 'Unpublished' }}@if($p->is_featured) &middot; Featured @endif</div>
                    @endif
                </td>
                <td>
                    @if($vp->status === 'approved')
                        <div class="d-flex flex-wrap gap-1">
                            @if($p->is_active)<a href="{{ route('products.show', $p) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">View</a>@endif
                            <form action="{{ route('admin.vendor-products.publish', $vp) }}" method="POST">@csrf<button class="btn btn-sm btn-outline-primary">{{ $p->is_active ? 'Unpublish' : 'Publish' }}</button></form>
                            <form action="{{ route('admin.vendor-products.featured', $vp) }}" method="POST">@csrf<button class="btn btn-sm btn-outline-primary">{{ $p->is_featured ? 'Remove from featured' : 'Mark featured' }}</button></form>
                        </div>
                    @else
                        <div class="d-flex flex-wrap gap-1 mb-1">
                            <form action="{{ route('admin.vendor-products.approve', $vp) }}" method="POST">@csrf<button class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Approve</button></form>
                        </div>
                        @if($vp->status !== 'rejected')
                            <form method="POST" class="d-flex flex-wrap gap-1">@csrf
                                <input type="text" name="reason" class="form-control form-control-sm" style="flex:1 1 160px" placeholder="Note for the seller" aria-label="Note for the seller about {{ $p->name }}" maxlength="1000" required>
                                <button class="btn btn-sm btn-outline-primary" formaction="{{ route('admin.vendor-products.request-changes', $vp) }}">Request changes</button>
                                <button class="btn btn-sm btn-outline-danger" formaction="{{ route('admin.vendor-products.reject', $vp) }}">Reject</button>
                            </form>
                        @endif
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">Nothing here.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
{{ $vendorProducts->links() }}
@endsection
