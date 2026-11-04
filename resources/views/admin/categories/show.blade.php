@extends('layouts.admin')

@section('title', '')

@section('content')
<div class="container mt-4">
        <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary mb-3">Back</a>
        <div class="card">
            <div class="card-header">Category Details</div>
            <div class="card-body">
                <p><strong>Name:</strong> {{ $category->name }}</p>
                <p><strong>Slug:</strong> {{ $category->slug }}</p>
                <p><strong>Description:</strong> {{ $category->description }}</p>
                <p><strong>Status:</strong> {{ $category->status ? 'Active' : 'Inactive' }}</p>
            </div>
        </div>
    </div>

@endsection

