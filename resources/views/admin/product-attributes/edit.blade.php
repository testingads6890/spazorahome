@extends('layouts.admin')

@section('title', '')

@section('content')
<div class="container mt-4">
        <a href="{{ route('admin.product-attributes.index') }}" class="btn btn-secondary mb-3">Back</a>
        <div class="card">
            <div class="card-header">Edit Product Attribute</div>
            <div class="card-body">
                <form action="{{ route('admin.product-attributes.update', $productAttribute) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $productAttribute->name) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control" value="{{ old('slug', $productAttribute->slug) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-control" required>
                            <option value="text" {{ $productAttribute->type == 'text' ? 'selected' : '' }}>Text</option>
                            <option value="select" {{ $productAttribute->type == 'select' ? 'selected' : '' }}>Select</option>
                            <option value="color" {{ $productAttribute->type == 'color' ? 'selected' : '' }}>Color</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Options (JSON)</label>
                        <textarea name="options" class="form-control">{{ old('options', $productAttribute->options ? json_encode($productAttribute->options) : '') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Required</label>
                        <select name="is_required" class="form-control">
                            <option value="1" {{ $productAttribute->is_required ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ !$productAttribute->is_required ? 'selected' : '' }}>No</option>
                        </select>
                    </div>
                    <button class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>
    </div>

@endsection

