@extends('layouts.admin')

@section('title', 'Vendors Report')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="mb-0">Vendors Report</h1>
    <a href="{{ route('admin.exports.vendors') }}" class="btn btn-outline-primary"><i class="bi bi-download"></i> Export CSV</a>
</div>
<p class="text-muted">Order lines and sales leave out cancelled orders.</p>

<table class="table table-bordered">
    <thead>
        <tr><th>Business</th><th>Products</th><th>Order lines</th><th>Sales</th><th>Status</th></tr>
    </thead>
    <tbody>
        @forelse($vendors as $vendor)
            <tr>
                <td>{{ $vendor->business_name }}</td>
                <td>{{ $vendor->products_count }}</td>
                <td>{{ $vendor->orders_count }}</td>
                <td>₹{{ number_format((float) $vendor->revenue, 2) }}</td>
                <td>{{ ucfirst($vendor->status) }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted">No vendors yet.</td></tr>
        @endforelse
    </tbody>
</table>
{{ $vendors->links() }}
@endsection
