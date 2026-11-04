@extends('layouts.admin')

@section('title', 'Payment Methods')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h4 mb-0">Payment Methods</h2>
        </div>
        <div class="col-12 text-end">
            <a href="{{ route('admin.payments.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add New Payment Method
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
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($paymentMethods as $method)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $method->name }}</td>
                        <td><code>{{ $method->slug }}</code></td>
                        <td>
                            <span class="badge bg-{{ $method->type === 'automatic' ? 'primary' : 'secondary' }}">
                                {{ ucfirst($method->type) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $method->is_active ? 'success' : 'secondary' }}">
                                {{ $method->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('admin.payments.edit', $method->id) }}" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form action="{{ route('admin.payments.destroy', $method->id) }}" 
                                      method="POST" onsubmit="return confirm('Are you sure you want to delete this payment method?');">
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

    {{ $paymentMethods->links() }}
</div>
@endsection