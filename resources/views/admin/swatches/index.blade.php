@extends('layouts.admin')

@section('title', 'Swatches')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h4 mb-0">Swatches for {{ $productAttribute->name }}</h2>
        </div>
        <div class="col-12 text-end">
            <a href="{{ route('admin.swatches.create', $productAttribute) }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add New Swatch
            </a>
            <a href="{{ route('admin.product-attributes.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Attributes
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
                    <th>Value</th>
                    <th>Type</th>
                    <th>Preview</th>
                    <th>Sort Order</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($swatches as $swatch)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $swatch->value }}</td>
                        <td>
                            <span class="badge bg-{{ $swatch->type === 'color' ? 'primary' : ($swatch->type === 'label' ? 'secondary' : 'success') }}">
                                {{ ucfirst($swatch->type) }}
                            </span>
                        </td>
                        <td>
                            @if($swatch->type === 'color' && $swatch->color_code)
                                <div class="color-preview" style="background-color: {{ $swatch->color_code }}; width: 30px; height: 30px; border-radius: 50%;"></div>
                            @elseif($swatch->type === 'image' && $swatch->image)
                                <img src="{{ asset($swatch->image) }}" alt="{{ $swatch->value }}" width="30" height="30">
                            @else
                                <span class="badge bg-light text-dark">{{ $swatch->value }}</span>
                            @endif
                        </td>
                        <td>{{ $swatch->sort_order }}</td>
                        <td>
                            <span class="badge bg-{{ $swatch->is_active ? 'success' : 'secondary' }}">
                                {{ $swatch->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('admin.swatches.edit', [$productAttribute, $swatch]) }}" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form action="{{ route('admin.swatches.destroy', [$productAttribute, $swatch]) }}" 
                                      method="POST" onsubmit="return confirm('Are you sure you want to delete this swatch?');">
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
</div>
@endsection