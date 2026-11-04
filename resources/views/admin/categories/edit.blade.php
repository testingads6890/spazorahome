@extends('layouts.admin')

@section('title', '')

@section('content')
<div class="container mt-4">
        <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary mb-3">Back</a>
        <div class="card">
            <div class="card-header">Edit Category</div>
            <div class="card-body">
                <form action="{{ route('admin.categories.update', $category) }}" method="POST" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $category->name) }}" required>
                        @error('name')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control" value="{{ old('slug', $category->slug) }}" required>
                        @error('slug')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control js-richtext">{{ old('description', $category->description) }}</textarea>
                    </div>
@include('admin.partials.seo-fields', ['model' => $category])

                    <div class="mb-3">
                        <label class="form-label">Parent Category</label>
                        <select name="parent_id" class="form-control">
                            <option value="">None</option>
                            @foreach($parents as $parent)
                                <option value="{{ $parent->id }}" {{ old('parent_id', $category->parent_id) == $parent->id ? 'selected' : '' }}>{{ $parent->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="image_file" class="form-label">Image</label>
                        @if($category->imageUrl())
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <img src="{{ $category->imageUrl() }}" alt="{{ $category->name }}" width="72" height="72" style="object-fit:cover;border-radius:8px">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="remove_image" @checked(old('remove_image'))>
                                    <label class="form-check-label" for="remove_image">Remove this image</label>
                                </div>
                            </div>
                        @endif
                        <input type="file" id="image_file" name="image_file" accept=".jpg,.jpeg,.png,.webp" class="form-control @error('image_file') is-invalid @enderror">
                        <div class="form-text">JPG, PNG or WEBP, up to 2 MB. Choose a new file to replace the current one.</div>
                        @error('image_file')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="1" {{ $category->status ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ !$category->status ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <button class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>
    </div>

@endsection

