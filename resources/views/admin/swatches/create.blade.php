@extends('layouts.admin')

@section('title', 'Create Swatch')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Create Swatch for {{ $productAttribute->name }}</h2>
        <a href="{{ route('admin.swatches.index', $productAttribute) }}" class="btn btn-secondary">Back to Swatches</a>
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
        <div class="card-header">Create New Swatch</div>
        <div class="card-body">
            <form action="{{ route('admin.swatches.store', $productAttribute) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="value" class="form-label">Value</label>
                    <input type="text" id="value" name="value" class="form-control" value="{{ old('value') }}" required>
                    @error('value')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="type" class="form-label">Swatch Type</label>
                    <select id="type" name="type" class="form-control" required>
                        <option value="">Select Type</option>
                        <option value="color" {{ old('type') == 'color' ? 'selected' : '' }}>Color</option>
                        <option value="label" {{ old('type') == 'label' ? 'selected' : '' }}>Label</option>
                        <option value="image" {{ old('type') == 'image' ? 'selected' : '' }}>Image</option>
                    </select>
                    @error('type')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3" id="color-picker" style="display: none;">
                    <label for="color_code" class="form-label">Color Code</label>
                    <input type="color" id="color_code" name="color_code" class="form-control" value="{{ old('color_code', '#000000') }}">
                    @error('color_code')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3" id="image-picker" style="display: none;">
                    <label for="image" class="form-label">Image URL</label>
                    <input type="text" id="image" name="image" class="form-control" value="{{ old('image') }}">
                    @error('image')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="sort_order" class="form-label">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0">
                    @error('sort_order')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="is_active" name="is_active" class="form-check-input" value="1" {{ old('is_active') ? 'checked' : '' }}>
                    <label for="is_active" class="form-check-label">Active</label>
                    @error('is_active')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Create Swatch</button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('type').addEventListener('change', function() {
    const colorPicker = document.getElementById('color-picker');
    const imagePicker = document.getElementById('image-picker');
    const selectedType = this.value;
    
    if (selectedType === 'color') {
        colorPicker.style.display = 'block';
        imagePicker.style.display = 'none';
    } else if (selectedType === 'image') {
        colorPicker.style.display = 'none';
        imagePicker.style.display = 'block';
    } else {
        colorPicker.style.display = 'none';
        imagePicker.style.display = 'none';
    }
});
</script>
@endsection