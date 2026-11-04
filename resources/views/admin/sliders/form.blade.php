@extends('layouts.admin')

@php
    $editing = $slider->exists;
    $slides = old('slides', $slider->slides ?? []);
@endphp

@section('title', $editing ? 'Edit Slider' : 'Add Slider')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>{{ $editing ? 'Edit Slider' : 'Add Slider' }}</h2>
        <a href="{{ route('admin.sliders.index') }}" class="btn btn-secondary">Back to List</a>
    </div>

    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <form action="{{ $editing ? route('admin.sliders.update', $slider) : route('admin.sliders.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @if($editing) @method('PUT') @endif

        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-5 mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $slider->name) }}" required maxlength="255">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="location" class="form-label">Where it appears</label>
                        <select id="location" name="location" class="form-select">
                            @foreach(\App\Models\Slider::LOCATIONS as $key => $label)
                                <option value="{{ $key }}" {{ old('location', $slider->location) === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="sort_order" class="form-label">Position</label>
                        <input type="number" id="sort_order" name="sort_order" class="form-control" min="0" value="{{ old('sort_order', $slider->sort_order ?? 0) }}">
                    </div>
                </div>
                <input type="hidden" name="is_active" value="0">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', $slider->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
        </div>

        <p class="text-muted">Up to {{ \App\Models\Slider::MAX_SLIDES }} slides. Leave a slide's image empty to skip it. Use an image URL/path or upload a JPG, PNG or WEBP file up to 5 MB; wide pictures of about 1600 x 600 work best.</p>

        @for($i = 0; $i < \App\Models\Slider::MAX_SLIDES; $i++)
            <div class="card mb-3">
                <div class="card-header">Slide {{ $i + 1 }}</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="slide-image-{{ $i }}">Image</label>
                            <input type="text" id="slide-image-{{ $i }}" name="slides[{{ $i }}][image]" class="form-control" maxlength="500" value="{{ $slides[$i]['image'] ?? '' }}" placeholder="https://... or /storage/...">
                            <input type="file" id="slide-upload-{{ $i }}" name="slides[{{ $i }}][upload]" class="form-control mt-2" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="slide-title-{{ $i }}">Heading</label>
                            <input type="text" id="slide-title-{{ $i }}" name="slides[{{ $i }}][title]" class="form-control" maxlength="120" value="{{ $slides[$i]['title'] ?? '' }}">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="slide-text-{{ $i }}">Text</label>
                            <input type="text" id="slide-text-{{ $i }}" name="slides[{{ $i }}][text]" class="form-control" maxlength="255" value="{{ $slides[$i]['text'] ?? '' }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="slide-button-{{ $i }}">Button label</label>
                            <input type="text" id="slide-button-{{ $i }}" name="slides[{{ $i }}][button]" class="form-control" maxlength="40" value="{{ $slides[$i]['button'] ?? '' }}" placeholder="Shop now">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label" for="slide-link-{{ $i }}">Button / slide link</label>
                            <input type="text" id="slide-link-{{ $i }}" name="slides[{{ $i }}][link]" class="form-control" maxlength="500" value="{{ $slides[$i]['link'] ?? '' }}" placeholder="/search?category=1">
                        </div>
                    </div>
                </div>
            </div>
        @endfor

        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>
@endsection
