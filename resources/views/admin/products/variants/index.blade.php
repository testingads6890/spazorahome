@extends('layouts.admin')

@section('title', 'Product Variants')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h4 mb-0">Variants for {{ $product->name }}</h2>
        </div>
        <div class="col-12 text-end">
            <a href="{{ route('admin.products.variants.create', $product) }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add New Variant
            </a>
            <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Products
            </a>
        </div>
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

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>SKU</th>
                    <th>Price</th>
                    <th>Sale Price</th>
                    <th>Stock</th>
                    <th>Attributes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($variants as $variant)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><code>{{ $variant->sku }}</code></td>
                        <td>₹{{ number_format($variant->price, 2) }}</td>
                        <td>
                            @if($variant->sale_price)
                                <span class="text-success">₹{{ number_format($variant->sale_price, 2) }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $variant->stock_quantity > 0 ? 'success' : 'danger' }}">
                                {{ $variant->stock_quantity }}
                            </span>
                        </td>
                        <td>
                            @if($variant->attributes && count($variant->attributes) > 0)
                                @foreach($variant->attributes as $key => $value)
                                    <span class="badge bg-info me-1">{{ $key }}: {{ $value }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">No attributes</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('admin.products.variants.edit', [$product, $variant]) }}" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form action="{{ route('admin.products.variants.destroy', [$product, $variant]) }}" 
                                      method="POST" onsubmit="return confirm('Are you sure you want to delete this variant?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $variants->links() }}
</div>
@endsection