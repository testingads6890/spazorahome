@extends('layouts.admin')

@section('title', 'Marketplace Vendors')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb-3">
        <h2 class="h4 mb-0">Marketplace Vendors</h2>
        <a href="{{ route('admin.marketplace.index') }}" class="btn btn-secondary">Back</a>
    </div>
    <table class="table table-hover align-middle">
        <thead class="table-light"><tr><th>Business</th><th>Email</th><th>Status</th><th>Products</th></tr></thead>
        <tbody>
        @forelse($vendors as $row)
            <tr><td>{{ $row->business_name }}</td><td>{{ $row->email }}</td><td>{{ ucfirst($row->status) }}</td><td>{{ $row->products_count }}</td></tr>
        @empty
            <tr><td colspan="10" class="text-center text-muted">Nothing here yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $vendors->links() }}
</div>
@endsection
