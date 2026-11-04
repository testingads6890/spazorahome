@extends('layouts.admin')

@section('title', '')

@section('content')
<div class="container mt-4">
        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary mb-3">Back</a>
        <div class="card">
            <div class="card-header">Product Details</div>
            <div class="card-body">
                <p><strong>Name:</strong> {{ $product->name }}</p>
                <p><strong>SKU:</strong> {{ $product->sku }}</p>
                <p><strong>Price:</strong> {{ number_format($product->price, 2) }}</p>
                <p><strong>Stock:</strong> {{ $product->stock_quantity }}</p>
                <p><strong>Status:</strong> {{ $product->is_active ? 'Active' : 'Inactive' }}</p>
            </div>
        </div>
    </div>

@endsection

