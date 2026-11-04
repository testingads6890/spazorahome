@extends('layouts.admin')

@section('title', 'Create Discount')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Create Discount</h2>
        <a href="{{ route('admin.discounts.index') }}" class="btn btn-secondary">Back to List</a>
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
        <div class="card-header">Create New Discount</div>
        <div class="card-body">
            <form action="{{ route('admin.discounts.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label">Discount Name</label>
                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="code" class="form-label">Coupon Code (Optional)</label>
                    <input type="text" id="code" name="code" class="form-control" value="{{ old('code') }}">
                    <small class="text-muted">Leave empty for automatic discount</small>
                    @error('code')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="type" class="form-label">Discount Type</label>
                        <select id="type" name="type" class="form-control" required>
                            <option value="">Select Type</option>
                            <option value="percentage" {{ old('type') == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                            <option value="fixed" {{ old('type') == 'fixed' ? 'selected' : '' }}>Fixed Amount (₹)</option>
                        </select>
                        @error('type')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="value" class="form-label">Discount Value</label>
                        <input type="number" step="0.01" id="value" name="value" class="form-control" value="{{ old('value') }}" required>
                        @error('value')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="min_order_amount" class="form-label">Minimum Order Amount (Optional)</label>
                        <input type="number" step="0.01" id="min_order_amount" name="min_order_amount" class="form-control" value="{{ old('min_order_amount') }}">
                        @error('min_order_amount')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="max_discount_amount" class="form-label">Max Discount Amount (Optional)</label>
                        <input type="number" step="0.01" id="max_discount_amount" name="max_discount_amount" class="form-control" value="{{ old('max_discount_amount') }}">
                        @error('max_discount_amount')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="usage_limit" class="form-label">Usage Limit (Optional)</label>
                        <input type="number" id="usage_limit" name="usage_limit" class="form-control" value="{{ old('usage_limit') }}">
                        @error('usage_limit')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="is_active" class="form-label">Status</label>
                        <select id="is_active" name="is_active" class="form-control">
                            <option value="1" {{ old('is_active') ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ !old('is_active') ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('is_active')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="starts_at" class="form-label">Start Date (Optional)</label>
                        <input type="datetime-local" id="starts_at" name="starts_at" class="form-control" value="{{ old('starts_at') }}">
                        @error('starts_at')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="ends_at" class="form-label">End Date (Optional)</label>
                        <input type="datetime-local" id="ends_at" name="ends_at" class="form-control" value="{{ old('ends_at') }}">
                        @error('ends_at')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Create Discount</button>
            </form>
        </div>
    </div>
</div>
@endsection