@extends('layouts.admin')

@section('title', 'Currencies')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h4 mb-0">Currencies</h2>
        </div>
        <div class="col-12 text-end">
            <a href="{{ route('admin.currencies.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add New Currency
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
                    <th>Symbol</th>
                    <th>Exchange Rate</th>
                    <th>Position</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($currencies as $currency)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $currency->name }}</td>
                        <td><code>{{ $currency->code }}</code></td>
                        <td>{{ $currency->symbol }}</td>
                        <td>{{ number_format($currency->exchange_rate, 4) }}</td>
                        <td>
                            <span class="badge bg-info">{{ ucfirst($currency->position) }}</span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $currency->is_active ? 'success' : 'secondary' }}">
                                {{ $currency->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('admin.currencies.edit', $currency->id) }}" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form action="{{ route('admin.currencies.destroy', $currency->id) }}" 
                                      method="POST" onsubmit="return confirm('Are you sure you want to delete this currency?');">
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

    {{ $currencies->links() }}
</div>
@endsection