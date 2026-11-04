@extends('layouts.admin')

@section('title', 'Product Deals')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h4 mb-0">Product Deals</h2>
        </div>
        <div class="col-12 text-end">
            <a href="{{ route('admin.deals.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add New Deal
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
                    <th>Product</th>
                    <th>Original Price</th>
                    <th>Deal Price</th>
                    <th>Discount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($deals as $deal)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $deal->name }}</td>
                        <td>{{ $deal->product->name ?? 'N/A' }}</td>
                        <td>₹{{ number_format($deal->original_price, 2) }}</td>
                        <td class="text-success">₹{{ number_format($deal->deal_price, 2) }}</td>
                        <td>
                            @php
                                $discount = round((($deal->original_price - $deal->deal_price) / $deal->original_price) * 100);
                            @endphp
                            <span class="badge bg-danger">{{ $discount }}% OFF</span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $deal->is_active ? 'success' : 'secondary' }}">
                                {{ $deal->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('admin.deals.edit', $deal->id) }}" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form action="{{ route('admin.deals.destroy', $deal->id) }}" 
                                      method="POST" onsubmit="return confirm('Are you sure you want to delete this deal?');">
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

    {{ $deals->links() }}
</div>
@endsection