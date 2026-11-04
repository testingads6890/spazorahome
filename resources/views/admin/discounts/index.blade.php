@extends('layouts.admin')

@section('title', 'Discounts')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h4 mb-0">Discounts & Coupons</h2>
        </div>
        <div class="col-12 text-end">
            <a href="{{ route('admin.discounts.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add New Discount
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
                    <th>Code</th>
                    <th>Type</th>
                    <th>Value</th>
                    <th>Status</th>
                    <th>Usage</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($discounts as $discount)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $discount->name }}</td>
                        <td>
                            @if($discount->code)
                                <code>{{ $discount->code }}</code>
                            @else
                                <span class="text-muted">Automatic</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $discount->type === 'percentage' ? 'primary' : 'success' }}">
                                {{ ucfirst($discount->type) }}
                            </span>
                        </td>
                        <td>
                            @if($discount->type === 'percentage')
                                {{ $discount->value }}%
                            @else
                                ₹{{ number_format($discount->value, 2) }}
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $discount->is_active ? 'success' : 'secondary' }}">
                                {{ $discount->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            {{ $discount->used_count }}
                            @if($discount->usage_limit)
                                / {{ $discount->usage_limit }}
                            @endif
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('admin.discounts.edit', $discount->id) }}" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form action="{{ route('admin.discounts.destroy', $discount->id) }}" 
                                      method="POST" onsubmit="return confirm('Are you sure you want to delete this discount?');">
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

    {{ $discounts->links() }}
</div>
@endsection