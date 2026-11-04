@extends('layouts.admin')

@section('title', '')

@section('content')
    
<div class="container mt-4">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Banners</h1>
            <a href="{{ route('admin.banners.create') }}" class="btn btn-primary">Add Banner</a>
        </div>
        <table class="table table-bordered">
            <thead>
                <tr><th>Title</th><th>Position</th><th>Sort Order</th><th>Active</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @foreach($banners as $banner)
                    <tr>
                        <td>{{ $banner->title }}</td>
                        <td>{{ ucfirst($banner->position) }}</td>
                        <td>{{ $banner->sort_order }}</td>
                        <td>{{ $banner->is_active ? 'Yes' : 'No' }}</td>
                        <td>
                            <a href="{{ route('admin.banners.edit', $banner) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $banners->links() }}
    </div>

@endsection

