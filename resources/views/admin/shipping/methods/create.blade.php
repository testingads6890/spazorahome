@extends('layouts.admin')

@section('title', 'Create Shipping Method')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Create Shipping Method</h2>
        <a href="{{ route('admin.shipping.methods.index') }}" class="btn btn-secondary">Back to List</a>
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
        <div class="card-header">Create New Shipping Method</div>
        <div class="card-body">
            <form action="{{ route('admin.shipping.methods.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label">Method Name</label>
                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="type" class="form-label">Method Type</label>
                    <select id="type" name="type" class="form-control" required>
                        <option value="">Select Type</option>
                        <option value="flat_rate" {{ old('type') == 'flat_rate' ? 'selected' : '' }}>Flat Rate</option>
                        <option value="free_shipping" {{ old('type') == 'free_shipping' ? 'selected' : '' }}>Free Shipping</option>
                        <option value="local_pickup" {{ old('type') == 'local_pickup' ? 'selected' : '' }}>Local Pickup</option>
                    </select>
                    @error('type')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="rate" class="form-label">Extra charge (₹)</label>
                        <input type="number" step="0.01" min="0" id="rate" name="rate" class="form-control" value="{{ old('rate', 0) }}">
                        <div class="form-text">Flat Rate only. Added to the address's normal shipping fee; 0 for standard delivery.</div>
                        @error('rate')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="min_order" class="form-label">Minimum order value (₹)</label>
                        <input type="number" step="0.01" min="0" id="min_order" name="min_order" class="form-control" value="{{ old('min_order', '') }}">
                        <div class="form-text">Free Shipping only. Leave empty to always offer it.</div>
                        @error('min_order')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="sort_order" class="form-label">Position</label>
                        <input type="number" min="0" id="sort_order" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}">
                        <div class="form-text">Lowest first. The first option is pre-selected at checkout.</div>
                        @error('sort_order')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="delivery_estimate" class="form-label">Delivery time shown to customers</label>
                    <input type="text" id="delivery_estimate" name="delivery_estimate" class="form-control" maxlength="100" value="{{ old('delivery_estimate', '') }}" placeholder="e.g. 5-7 working days">
                    @error('delivery_estimate')<div class="text-danger">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="is_active" name="is_active" class="form-check-input" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                    <label for="is_active" class="form-check-label">Active</label>
                    @error('is_active')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Create Method</button>
            </form>
        </div>
    </div>
</div>
@endsection