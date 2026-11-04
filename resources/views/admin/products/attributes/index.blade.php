@extends('layouts.admin')

@section('title', 'Product Attributes')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h4 mb-0">Product Attributes</h2>
        </div>
        <div class="col-12 text-end">
            <a href="{{ route('admin.product-attributes.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add New Attribute
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
                    <th>Type</th>
                    <th>Required</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($attributes as $attribute)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $attribute->name }}</td>
                        <td><code>{{ $attribute->slug }}</code></td>
                        <td>
                            <span class="badge bg-{{ $attribute->type === 'select' ? 'primary' : ($attribute->type === 'color' ? 'danger' : ($attribute->type === 'image' ? 'success' : ($attribute->type === 'label' ? 'warning' : 'info'))) }}">
                                {{ ucfirst($attribute->type) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $attribute->is_required ? 'success' : 'secondary' }}">
                                {{ $attribute->is_required ? 'Required' : 'Optional' }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('admin.product-attributes.edit', $attribute->id) }}" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form action="{{ route('admin.product-attributes.destroy', $attribute->id) }}" 
                                      method="POST" onsubmit="return confirm('Are you sure you want to delete this attribute?');">
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

    {{ $attributes->links() }}
</div>
@endsection