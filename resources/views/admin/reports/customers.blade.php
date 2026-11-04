@extends('layouts.admin')

@section('title', 'Customers Report')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="mb-0">Customers Report</h1>
    <a href="{{ route('admin.exports.customers') }}" class="btn btn-outline-primary"><i class="bi bi-download"></i> Export CSV</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h5>Total Customers</h5>
                <p class="h3 mb-0">{{ $totalCustomers }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5>New This Month</h5>
                <p class="h3 mb-0">{{ $newCustomers }}</p>
            </div>
        </div>
    </div>
</div>

<table class="table table-bordered">
    <thead>
        <tr><th>Name</th><th>Email</th><th>Orders</th><th>Spent</th><th>Joined</th></tr>
    </thead>
    <tbody>
        @forelse($customers as $customer)
            <tr>
                <td>{{ $customer->name }}</td>
                <td>{{ $customer->email }}</td>
                <td>{{ $customer->orders_count }}</td>
                <td>₹{{ number_format((float) $customer->spent, 2) }}</td>
                <td>{{ $customer->created_at->format('M d, Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted">No customers yet.</td></tr>
        @endforelse
    </tbody>
</table>
{{ $customers->links() }}
@endsection
