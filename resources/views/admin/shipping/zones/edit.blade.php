@extends('layouts.admin')

@section('title', 'Edit Shipping Zone')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Edit Shipping Zone</h2>
        <a href="{{ route('admin.shipping.zones.index') }}" class="btn btn-secondary">Back to List</a>
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
        <div class="card-header">Edit Shipping Zone</div>
        <div class="card-body">
            <form action="{{ route('admin.shipping.zones.update', $shippingZone) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="name" class="form-label">Zone Name</label>
                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $shippingZone->name) }}" required>
                    @error('name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="rate" class="form-label">Shipping Rate (₹)</label>
                    <input type="number" step="0.01" id="rate" name="rate" class="form-control" value="{{ old('rate', $shippingZone->rate) }}" required>
                    @error('rate')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="rate_per_kg" class="form-label">Extra per kg (₹, optional)</label>
                    <input type="number" step="0.01" min="0" id="rate_per_kg" name="rate_per_kg" class="form-control" value="{{ old('rate_per_kg', $shippingZone->rate_per_kg) }}">
                    <small class="text-muted">Added for each kg of the order's packed weight (set on the product). Meant for international zones; leave empty for a flat rate.</small>
                    @error('rate_per_kg')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="free_shipping_threshold" class="form-label">Free Shipping Threshold (₹)</label>
                    <input type="number" step="0.01" id="free_shipping_threshold" name="free_shipping_threshold" class="form-control" value="{{ old('free_shipping_threshold', $shippingZone->free_shipping_threshold) }}">
                    <small class="text-muted">Leave empty if no free shipping</small>
                    @error('free_shipping_threshold')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="countries" class="form-label">Countries (comma separated)</label>
                    <input type="text" id="countries" name="countries" class="form-control" placeholder="India" value="{{ old('countries', isset($shippingZone) ? implode(', ', $shippingZone->countries ?? []) : '') }}">
                    <small class="text-muted">Leave empty to match any countrie.</small>
                    @error('countries')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="states" class="form-label">States (comma separated)</label>
                    <input type="text" id="states" name="states" class="form-control" placeholder="Maharashtra, Karnataka" value="{{ old('states', isset($shippingZone) ? implode(', ', $shippingZone->states ?? []) : '') }}">
                    <small class="text-muted">Leave empty to match any state.</small>
                    @error('states')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="cities" class="form-label">Cities (comma separated)</label>
                    <input type="text" id="cities" name="cities" class="form-control" placeholder="Mumbai, Pune" value="{{ old('cities', isset($shippingZone) ? implode(', ', $shippingZone->cities ?? []) : '') }}">
                    <small class="text-muted">Leave empty to match any city.</small>
                    @error('cities')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="is_active" name="is_active" class="form-check-input" value="1" {{ old('is_active', $shippingZone->is_active) ? 'checked' : '' }}>
                    <label for="is_active" class="form-check-label">Active</label>
                    @error('is_active')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Update Zone</button>
                <a href="{{ route('admin.shipping.zones.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection