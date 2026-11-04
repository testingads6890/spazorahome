@extends('layouts.admin')

@section('title', 'Edit Product Variant')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Edit Variant for {{ $product->name }}</h2>
        <a href="{{ route('admin.products.variants.index', $product) }}" class="btn btn-secondary">Back to Variants</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">Edit Product Variant</div>
        <div class="card-body">
            <form action="{{ route('admin.products.variants.update', [$product, $variant]) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="sku" class="form-label">SKU</label>
                    <input type="text" id="sku" name="sku" class="form-control" value="{{ old('sku', $variant->sku) }}" required>
                    @error('sku')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="price" class="form-label">Price</label>
                        <input type="number" step="0.01" id="price" name="price" class="form-control" value="{{ old('price', $variant->price) }}" required>
                        @error('price')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="sale_price" class="form-label">Sale Price (Optional)</label>
                        <input type="number" step="0.01" id="sale_price" name="sale_price" class="form-control" value="{{ old('sale_price', $variant->sale_price) }}">
                        @error('sale_price')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="stock_quantity" class="form-label">Stock Quantity</label>
                    <input type="number" id="stock_quantity" name="stock_quantity" class="form-control" value="{{ old('stock_quantity', $variant->stock_quantity) }}" required>
                    @error('stock_quantity')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="attributes">Attributes</label>
                    <textarea name="attributes" id="attributes" class="form-control" rows="4" maxlength="2000" placeholder="Colour: Red&#10;Size: Large">{{ old('attributes', collect($variant->getAttribute('attributes') ?? [])->map(fn ($v, $k) => $k . ': ' . (is_array($v) ? implode(', ', $v) : $v))->implode("\n")) }}</textarea>
                    <small class="text-muted">One per line as <code>Name: value</code>. Customers see the values joined together, e.g. "Red / Large".</small>
                    @error('attributes')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Update Variant</button>
                <a href="{{ route('admin.products.variants.index', $product) }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection