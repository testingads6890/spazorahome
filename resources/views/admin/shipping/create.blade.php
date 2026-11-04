@extends('layouts.admin')

@section('title', 'Create Shipping')

@section('content')
<h1>Create Shipment for Order #{{ $order->order_number }}</h1>

<form method="POST" action="{{ route('admin.orders.shipping.store', $order) }}">
    @csrf

    <div class="mb-3">
        <label class="form-label">Carrier</label>
        <input type="text" name="carrier" class="form-control" required>
        @error('carrier')<div class="text-danger">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label class="form-label">Tracking Number</label>
        <input type="text" name="tracking_number" class="form-control" required>
        @error('tracking_number')<div class="text-danger">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            <option value="pending">Pending</option>
            <option value="shipped">Shipped</option>
            <option value="delivered">Delivered</option>
        </select>
        @error('status')<div class="text-danger">{{ $message }}</div>@enderror
    </div>

    <button type="submit" class="btn btn-primary">Create Shipment</button>
    <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-secondary">Cancel</a>
</form>
@endsection
