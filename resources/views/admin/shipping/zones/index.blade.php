@extends('layouts.admin')

@section('title', 'Shipping Zones')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h4 mb-0">Shipping Zones</h2>
        </div>
        <div class="col-12 text-end">
            <a href="{{ route('admin.shipping.zones.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add New Zone
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
                    <th>Rate</th>
                    <th>Free Shipping</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($zones as $zone)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $zone->name }}</td>
                        <td>₹{{ number_format($zone->rate, 2) }}</td>
                        <td>
                            @if($zone->free_shipping_threshold)
                                ₹{{ number_format($zone->free_shipping_threshold, 2) }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $zone->is_active ? 'success' : 'secondary' }}">
                                {{ $zone->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('admin.shipping.zones.edit', $zone->id) }}" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form action="{{ route('admin.shipping.zones.destroy', $zone->id) }}" 
                                      method="POST" onsubmit="return confirm('Are you sure you want to delete this zone?');">
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

    {{ $zones->links() }}
</div>
@endsection