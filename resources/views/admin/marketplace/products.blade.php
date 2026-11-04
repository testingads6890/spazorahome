@extends('layouts.admin')

@section('title', 'Marketplace Products')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb-3">
        <h2 class="h4 mb-0">Marketplace Products</h2>
        <a href="{{ route('admin.marketplace.index') }}" class="btn btn-secondary">Back</a>
    </div>
    <table class="table table-hover align-middle">
        <thead class="table-light"><tr><th>Product</th><th>Vendor</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($products as $row)
            <tr><td>{{ $row->product->name ?? "-" }}</td><td>{{ $row->vendor->business_name ?? "-" }}</td><td>{{ ucfirst($row->status) }}</td></tr>
        @empty
            <tr><td colspan="10" class="text-center text-muted">Nothing here yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $products->links() }}
</div>
@endsection
