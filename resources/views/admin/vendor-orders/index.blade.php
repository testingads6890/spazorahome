@extends('layouts.admin')

@section('title', 'Vendor Orders')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="mb-0">Vendor Orders</h1>
    <a href="{{ route('admin.exports.vendor-orders', request()->only(['vendor_id', 'payout_status'])) }}" class="btn btn-outline-primary"><i class="bi bi-download"></i> Export CSV</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<table class="table table-bordered">
    <thead>
        <tr><th>Order</th><th>Vendor</th><th>Subtotal</th><th>Commission</th><th>TCS</th><th>Payout</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
        @foreach($vendorOrders as $vo)
            <tr>
                <td>{{ $vo->order->order_number }}</td>
                <td>{{ $vo->vendor->business_name }}</td>
                <td>₹{{ number_format($vo->subtotal, 2) }}</td>
                <td>₹{{ number_format($vo->commission, 2) }}</td>
                <td>{{ (float) $vo->tcs_amount > 0 ? '₹' . number_format($vo->tcs_amount, 2) : '-' }}</td>
                <td>₹{{ number_format($vo->payout_amount, 2) }}</td>
                <td>{{ $vo->payout_status }}</td>
                <td>
                    @if($vo->payout_status == 'pending')
                        <form action="{{ route('admin.vendor-orders.markPaid', $vo) }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-success">Mark Paid</button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
{{ $vendorOrders->links() }}
@endsection
