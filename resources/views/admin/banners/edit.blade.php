@extends('layouts.admin')

@section('title', '')

@section('content')
<div class="container mt-4">
        <a href="{{ route('admin.banners.index') }}" class="btn btn-secondary mb-3">Back</a>
        <div class="card">
            <div class="card-header">Edit Banner</div>
            <div class="card-body">
                <form action="{{ route('admin.banners.update', $banner) }}" method="POST" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $banner->title) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subtitle</label>
                        <input type="text" name="subtitle" class="form-control" value="{{ old('subtitle', $banner->subtitle) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image</label>
                        <input type="text" name="image" class="form-control mb-2" value="{{ old('image', $banner->image) }}" placeholder="https://... or /storage/...">
                        <input type="file" name="image_file" class="form-control" accept="image/jpeg,image/png,image/webp">
                        <div class="form-text">Upload JPG, PNG or WEBP up to 5 MB, or enter an existing image URL/path above.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Link</label>
                        <input type="text" name="link" class="form-control" value="{{ old('link', $banner->link) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Position</label>
                        <select name="position" class="form-control" required>
                            <option value="homepage" {{ $banner->position == 'homepage' ? 'selected' : '' }}>Homepage</option>
                            <option value="category" {{ $banner->position == 'category' ? 'selected' : '' }}>Category</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $banner->sort_order) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Active</label>
                        <select name="is_active" class="form-control">
                            <option value="1" {{ $banner->is_active ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ !$banner->is_active ? 'selected' : '' }}>No</option>
                        </select>
                    </div>
                    <button class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>
    </div>

@endsection

