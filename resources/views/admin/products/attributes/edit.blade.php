@extends('layouts.admin')

@section('title', 'Edit Product Attribute')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Edit Product Attribute</h2>
        <a href="{{ route('admin.product-attributes.index') }}" class="btn btn-secondary">Back to List</a>
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
        <div class="card-header">Edit Product Attribute</div>
        <div class="card-body">
            <form action="{{ route('admin.product-attributes.update', $productAttribute) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="name" class="form-label">Attribute Name</label>
                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $productAttribute->name) }}" required>
                    @error('name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="slug" class="form-label">Slug</label>
                    <input type="text" id="slug" name="slug" class="form-control" value="{{ old('slug', $productAttribute->slug) }}" required>
                    @error('slug')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="type" class="form-label">Attribute Type</label>
                    <select id="type" name="type" class="form-control" required>
                        <option value="">Select Type</option>
                        <option value="text" {{ old('type', $productAttribute->type) == 'text' ? 'selected' : '' }}>Text</option>
                        <option value="select" {{ old('type', $productAttribute->type) == 'select' ? 'selected' : '' }}>Select (Dropdown)</option>
                        <option value="color" {{ old('type', $productAttribute->type) == 'color' ? 'selected' : '' }}>Color</option>
                        <option value="image" {{ old('type', $productAttribute->type) == 'image' ? 'selected' : '' }}>Image</option>
                        <option value="label" {{ old('type', $productAttribute->type) == 'label' ? 'selected' : '' }}>Label</option>
                    </select>
                    @error('type')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3" id="options-container" style="display: {{ in_array($productAttribute->type, ['select', 'color', 'image', 'label']) ? 'block' : 'none' }};">
                    <label for="options" class="form-label">Options (one per line)</label>
                    <textarea id="options" name="options[]" class="form-control" rows="5" placeholder="Option 1&#10;Option 2&#10;Option 3">{{ old('options', $productAttribute->options) ? implode("\n", old('options', $productAttribute->options)) : '' }}</textarea>
                    <small class="text-muted">Enter one option per line. Only applicable for select, color, image, and label types.</small>
                    @error('options')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 form-check">
                    <input type="hidden" name="is_required" value="0">
                    <input type="checkbox" id="is_required" name="is_required" class="form-check-input" value="1" {{ old('is_required', $productAttribute->is_required) ? 'checked' : '' }}>
                    <label for="is_required" class="form-check-label">Required</label>
                    @error('is_required')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Update Attribute</button>
                <a href="{{ route('admin.product-attributes.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('type').addEventListener('change', function() {
    const optionsContainer = document.getElementById('options-container');
    const selectedType = this.value;
    
    if (['select', 'color', 'image', 'label'].includes(selectedType)) {
        optionsContainer.style.display = 'block';
    } else {
        optionsContainer.style.display = 'none';
    }
});
</script>
@endsection