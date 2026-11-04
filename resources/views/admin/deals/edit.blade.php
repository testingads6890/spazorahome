@extends('layouts.admin')

@section('title', 'Edit Deal')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Edit Deal</h2>
        <a href="{{ route('admin.deals.index') }}" class="btn btn-secondary">Back to List</a>
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
        <div class="card-header">Edit Deal</div>
        <div class="card-body">
            <form action="{{ route('admin.deals.update', $deal) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="name" class="form-label">Deal Name</label>
                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $deal->name) }}" required>
                    @error('name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" class="form-control js-richtext" rows="3">{{ old('description', $deal->description) }}</textarea>
                    @error('description')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="product_id" class="form-label">Product</label>
                    <select id="product_id" name="product_id" class="form-control" required>
                        <option value="">Select Product</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ old('product_id', $deal->product_id) == $product->id ? 'selected' : '' }}>
                                {{ $product->name }} - ₹{{ number_format($product->price, 2) }}
                            </option>
                        @endforeach
                    </select>
                    @error('product_id')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="original_price" class="form-label">Original Price (₹)</label>
                        <input type="number" step="0.01" id="original_price" name="original_price" class="form-control" value="{{ old('original_price', $deal->original_price) }}" required>
                        @error('original_price')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="deal_price" class="form-label">Deal Price (₹)</label>
                        <input type="number" step="0.01" id="deal_price" name="deal_price" class="form-control" value="{{ old('deal_price', $deal->deal_price) }}" required>
                        @error('deal_price')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="starts_at" class="form-label">Start Date</label>
                        <input type="datetime-local" id="starts_at" name="starts_at" class="form-control" value="{{ old('starts_at', $deal->starts_at ? $deal->starts_at->format('Y-m-d\TH:i') : '') }}" required>
                        @error('starts_at')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="ends_at" class="form-label">End Date</label>
                        <input type="datetime-local" id="ends_at" name="ends_at" class="form-control" value="{{ old('ends_at', $deal->ends_at ? $deal->ends_at->format('Y-m-d\TH:i') : '') }}" required>
                        @error('ends_at')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3 form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="is_active" name="is_active" class="form-check-input" value="1" {{ old('is_active', $deal->is_active) ? 'checked' : '' }}>
                    <label for="is_active" class="form-check-label">Active</label>
                    @error('is_active')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Update Deal</button>
                <a href="{{ route('admin.deals.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection