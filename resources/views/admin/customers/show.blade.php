@extends('layouts.admin')

@section('title', '')

@section('content')
<div class="container mt-4">
        <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary mb-3">Back</a>
        <div class="card mb-3">
            <div class="card-header">Customer Details</div>
            <div class="card-body">
                <p><strong>Name:</strong> {{ $customer->name }}</p>
                <p><strong>Email:</strong> {{ $customer->email }}</p>
                <p><strong>Registered:</strong> {{ $customer->created_at->format('Y-m-d') }}</p>
            </div>
        </div>
        <h3>Recent Orders</h3>
        <table class="table table-bordered">
            <thead>
                <tr><th>Order #</th><th>Status</th><th>Total</th><th>Date</th></tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ ucfirst($order->status) }}</td>
                        <td>{{ number_format($order->total, 2) }}</td>
                        <td>{{ $order->created_at->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No orders.</td></tr>
                @endforelse
            </tbody>
        </table>
        <h3>Recent Reviews</h3>
        <table class="table table-bordered">
            <thead>
                <tr><th>Product</th><th>Rating</th><th>Approved</th><th>Date</th></tr>
            </thead>
            <tbody>
                @forelse($reviews as $review)
                    <tr>
                        <td>{{ $review->product->name ?? 'N/A' }}</td>
                        <td>{{ $review->rating }}/5</td>
                        <td>{{ $review->is_approved ? 'Yes' : 'No' }}</td>
                        <td>{{ $review->created_at->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No reviews.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

@endsection

