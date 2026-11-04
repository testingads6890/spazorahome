@extends('layouts.admin')

@section('title', 'Commissions')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb-3">
        <h2 class="h4 mb-0">Commissions</h2>
        <a href="{{ route('admin.marketplace.index') }}" class="btn btn-secondary">Back</a>
    </div>
    <table class="table table-hover align-middle">
        <thead class="table-light"><tr><th>Vendor</th><th>Product</th><th>Commission %</th></tr></thead>
        <tbody>
        @forelse($commissions as $row)
            <tr><td>{{ $row->vendor->business_name ?? "-" }}</td><td>{{ $row->product->name ?? "-" }}</td><td>{{ $row->commission_rate ?? $row->vendor->commission_rate ?? "-" }}</td></tr>
        @empty
            <tr><td colspan="10" class="text-center text-muted">Nothing here yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    
</div>
@endsection
