@extends('layouts.admin')

@section('title', '')

@section('content')
<div class="container mt-4">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Product Attributes</h1>
            <a href="{{ route('admin.product-attributes.create') }}" class="btn btn-primary">Add Attribute</a>
        </div>
        <table class="table table-bordered">
            <thead>
                <tr><th>Name</th><th>Slug</th><th>Type</th><th>Required</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @foreach($attributes as $attr)
                    <tr>
                        <td>{{ $attr->name }}</td>
                        <td>{{ $attr->slug }}</td>
                        <td>{{ ucfirst($attr->type) }}</td>
                        <td>{{ $attr->is_required ? 'Yes' : 'No' }}</td>
                        <td>
                            <a href="{{ route('admin.product-attributes.edit', $attr) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('admin.product-attributes.destroy', $attr) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $attributes->links() }}
    </div>

@endsection

