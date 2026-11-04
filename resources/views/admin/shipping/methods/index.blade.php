@extends('layouts.admin')

@section('title', 'Shipping Methods')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h4 mb-0">Shipping Methods</h2>
        </div>
        <div class="col-12 text-end">
            <a href="{{ route('admin.shipping.methods.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add New Method
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
                    <th>Type</th>
                    <th>Charge</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($methods as $method)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $method->name }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $method->type)) }}</td>
                        <td>{{ $method->type === 'flat_rate' ? ((float) $method->rate > 0 ? 'Normal fee + ₹' . number_format($method->rate, 2) : 'Normal fee') : 'Free' . ($method->min_order !== null ? ' from ₹' . number_format($method->min_order, 2) : '') }}</td>
                        <td>
                            <span class="badge bg-{{ $method->is_active ? 'success' : 'secondary' }}">
                                {{ $method->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('admin.shipping.methods.edit', $method->id) }}" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form action="{{ route('admin.shipping.methods.destroy', $method->id) }}" 
                                      method="POST" onsubmit="return confirm('Are you sure you want to delete this method?');">
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

    {{ $methods->links() }}
</div>
@endsection