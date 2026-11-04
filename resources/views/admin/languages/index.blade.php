@extends('layouts.admin')

@section('title', 'Languages')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h4 mb-0">Languages</h2>
        </div>
        <div class="col-12 text-end">
            <a href="{{ route('admin.languages.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add New Language
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
                    <th>Direction</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($languages as $language)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $language->name }}</td>
                        <td><code>{{ $language->code }}</code></td>
                        <td>
                            <span class="badge bg-{{ $language->direction === 'rtl' ? 'warning' : 'info' }}">
                                {{ strtoupper($language->direction) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $language->is_active ? 'success' : 'secondary' }}">
                                {{ $language->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('admin.languages.edit', $language->id) }}" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form action="{{ route('admin.languages.destroy', $language->id) }}" 
                                      method="POST" onsubmit="return confirm('Are you sure you want to delete this language?');">
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

    {{ $languages->links() }}
</div>
@endsection