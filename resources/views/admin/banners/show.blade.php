@extends('layouts.admin')

@section('title', '')

@section('content')
<div class="container mt-4">
        <a href="{{ route('admin.banners.index') }}" class="btn btn-secondary mb-3">Back</a>
        <div class="card">
            <div class="card-header">Banner Details</div>
            <div class="card-body">
                <p><strong>Title:</strong> {{ $banner->title }}</p>
                <p><strong>Position:</strong> {{ ucfirst($banner->position) }}</p>
                <p><strong>Active:</strong> {{ $banner->is_active ? 'Yes' : 'No' }}</p>
            </div>
        </div>
    </div>

@endsection

