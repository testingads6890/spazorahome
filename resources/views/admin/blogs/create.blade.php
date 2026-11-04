@extends('layouts.admin')

@section('title', 'Create Blog Post')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Create Blog Post</h2>
        <a href="{{ route('admin.blogs.index') }}" class="btn btn-secondary">Back to List</a>
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
        <div class="card-header">Create New Blog Post</div>
        <div class="card-body">
            <form action="{{ route('admin.blogs.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" id="title" name="title" class="form-control" value="{{ old('title') }}" required>
                    @error('title')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="slug" class="form-label">Slug</label>
                    <input type="text" id="slug" name="slug" class="form-control" value="{{ old('slug') }}" required>
                    @error('slug')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="excerpt" class="form-label">Excerpt</label>
                    <textarea id="excerpt" name="excerpt" class="form-control" rows="2">{{ old('excerpt') }}</textarea>
                    @error('excerpt')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="content" class="form-label">Content</label>
                    <textarea data-size="tall" id="content" name="content" class="form-control js-richtext" rows="10">{{ old('content') }}</textarea>
                    @error('content')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                @include('admin.partials.seo-fields', ['model' => null])


                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="featured_image" class="form-label">Featured Image URL</label>
                        <input type="text" id="featured_image" name="featured_image" class="form-control" value="{{ old('featured_image') }}">
                        @error('featured_image')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="author_name" class="form-label">Author Name</label>
                        <input type="text" id="author_name" name="author_name" class="form-control" value="{{ old('author_name') }}" required>
                        @error('author_name')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="mb-3 form-check">
                            <input type="hidden" name="is_published" value="0">
                            <input type="checkbox" id="is_published" name="is_published" class="form-check-input" value="1" {{ old('is_published') ? 'checked' : '' }}>
                            <label for="is_published" class="form-check-label">Published</label>
                            @error('is_published')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="published_at" class="form-label">Publish Date</label>
                        <input type="datetime-local" id="published_at" name="published_at" class="form-control" value="{{ old('published_at') }}">
                        @error('published_at')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Create Post</button>
            </form>
        </div>
    </div>
</div>
@endsection