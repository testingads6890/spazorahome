@extends('layouts.vendor')

@php $editing = $coupon->exists; $f = fn ($k, $d = null) => old($k, $coupon->{$k} ?? $d); @endphp
@section('title', $editing ? 'Edit coupon' : 'New coupon')

@section('content')
<a href="{{ route('vendor.coupons.index') }}" class="btn btn-outline-secondary btn-sm mb-3"><i class="bi bi-arrow-left"></i> All coupons</a>
@if($errors->any())<div class="alert alert-danger">Please fix the highlighted fields.</div>@endif

<form method="POST" action="{{ $editing ? route('vendor.coupons.update', $coupon) : route('vendor.coupons.store') }}" class="card" style="max-width:760px">
    @csrf @if($editing) @method('PUT') @endif
    <div class="card-body row g-3">
        <div class="col-md-6">
            <label class="form-label" for="code">Code <span class="text-danger">*</span></label>
            <input id="code" name="code" class="form-control text-uppercase @error('code') is-invalid @enderror" value="{{ $f('code') }}" maxlength="40" placeholder="DIWALI10" required>
            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="title">Title <span class="text-muted">(for you)</span></label>
            <input id="title" name="title" class="form-control" value="{{ $f('title') }}" maxlength="255" placeholder="Festive offer">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="type">Type</label>
            <select id="type" name="type" class="form-select">
                <option value="percent" @selected($f('type') === 'percent')>Percent off</option>
                <option value="fixed" @selected($f('type') === 'fixed')>Fixed amount (₹)</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="value">Value <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="1" id="value" name="value" class="form-control @error('value') is-invalid @enderror" value="{{ $f('value') }}" required>
            @error('value')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="max_discount">Maximum discount (₹)</label>
            <input type="number" step="0.01" min="1" id="max_discount" name="max_discount" class="form-control" value="{{ $f('max_discount') }}" placeholder="No limit">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="min_order">Minimum spend on your items (₹)</label>
            <input type="number" step="0.01" min="0" id="min_order" name="min_order" class="form-control" value="{{ $f('min_order', 0) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="usage_limit">Total uses allowed</label>
            <input type="number" min="1" id="usage_limit" name="usage_limit" class="form-control" value="{{ $f('usage_limit') }}" placeholder="Unlimited">
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <div class="form-check form-switch mb-2">
                <input type="hidden" name="is_active" value="0">
                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked($f('is_active', true))>
                <label class="form-check-label" for="is_active">Active</label>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="starts_at">Starts</label>
            <input type="datetime-local" id="starts_at" name="starts_at" class="form-control" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i')) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="ends_at">Ends</label>
            <input type="datetime-local" id="ends_at" name="ends_at" class="form-control @error('ends_at') is-invalid @enderror" value="{{ old('ends_at', $coupon->ends_at?->format('Y-m-d\TH:i')) }}">
            @error('ends_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="card-footer"><button class="btn btn-primary">{{ $editing ? 'Save coupon' : 'Create coupon' }}</button></div>
</form>
@endsection
