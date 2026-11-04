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
        @foreach(\App\Support\ProductExtraFields::present($product) as $title => $rows)
            <div class="card mt-3">
                <div class="card-header">{{ $title }}</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        @foreach($rows as $label => $value)
                            <dt class="col-sm-4">{{ $label }}</dt><dd class="col-sm-8">{!! nl2br(e($value)) !!}</dd>
                        @endforeach
                    </dl>
                </div>
            </div>
        @endforeach
    </div>

@endsection

