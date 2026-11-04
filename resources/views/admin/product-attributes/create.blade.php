@extends('layouts.admin')

@section('title', '')

@section('content')
<div class="container mt-4">
        <a href="{{ route('admin.product-attributes.index') }}" class="btn btn-secondary mb-3">Back</a>
        <div class="card">
            <div class="card-header">Create Product Attribute</div>
            <div class="card-body">
                <form action="{{ route('admin.product-attributes.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control" value="{{ old('slug') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-control" required>
                            <option value="text">Text</option>
                            <option value="select">Select</option>
                            <option value="color">Color</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Options (JSON)</label>
                        <textarea name="options" class="form-control" placeholder='["Red","Blue"]'>{{ old('options') }}</textarea>
                        <small class="text-muted">Enter as JSON array for select/color types.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Required</label>
                        <select name="is_required" class="form-control">
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <button class="btn btn-primary">Create</button>
                </form>
            </div>
        </div>
    </div>

@endsection

