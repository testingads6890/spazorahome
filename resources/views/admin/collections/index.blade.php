@extends('layouts.admin')

@section('title', 'Product Collections')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h4 mb-0">Product Collections</h2>
        </div>
        <div class="col-12 text-end">
            <a href="{{ route('admin.collections.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add New Collection
            </a>
        </div>
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

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th>Featured</th>
                    <th>Products</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($collections as $collection)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $collection->name }}</td>
                        <td><code>{{ $collection->slug }}</code></td>
                        <td>
                            <span class="badge bg-{{ $collection->is_active ? 'success' : 'secondary' }}">
                                {{ $collection->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $collection->is_featured ? 'warning' : 'secondary' }}">
                                {{ $collection->is_featured ? 'Featured' : 'Normal' }}
                            </span>
                        </td>
                        <td>{{ $collection->products()->count() }}</td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('admin.collections.edit', $collection->id) }}" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form action="{{ route('admin.collections.destroy', $collection->id) }}" 
                                      method="POST" onsubmit="return confirm('Are you sure you want to delete this collection?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $collections->links() }}
</div>
@endsection