@extends('layouts.admin')

@section('title', 'Translations')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h4 mb-0">Translation Management</h2>
        </div>
        <div class="col-12 text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTranslationModal">
                <i class="bi bi-plus-lg"></i> Add Translation
            </button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bulkImportModal">
                <i class="bi bi-upload"></i> Bulk Import
            </button>
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

    <!-- Language Selector -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.translations.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="language" class="form-label">Select Language</label>
                    <select name="language" id="language" class="form-select" onchange="this.form.submit()">
                        @foreach($languages as $language)
                            <option value="{{ $language->code }}" {{ $selectedLanguage->code == $language->code ? 'selected' : '' }}>
                                {{ $language->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" name="search" id="search" class="form-control" 
                           placeholder="Search translations..." value="{{ $search }}">
                </div>
                <div class="col-md-2">
                    <label for="group" class="form-label">Group</label>
                    <select name="group" id="group" class="form-select" onchange="this.form.submit()">
                        <option value="">All Groups</option>
                        @foreach($groups as $group)
                            <option value="{{ $group }}" {{ $group == request('group') ? 'selected' : '' }}>
                                {{ $group }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Translations Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Key</th>
                            <th>Value</th>
                            <th>Group</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($translations as $translation)
                            <tr>
                                <td><code>{{ $translation->key }}</code></td>
                                <td>{{ $translation->value }}</td>
                                <td>
                                    <span class="badge bg-secondary">{{ $translation->group ?? 'General' }}</span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editTranslationModal{{ $translation->id }}">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <form action="{{ route('admin.translations.destroy', $translation) }}" 
                                          method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Are you sure?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $translations->links() }}
        </div>
    </div>
</div>

<!-- Add Translation Modal -->
<div class="modal fade" id="addTranslationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Translation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.translations.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="language_id" value="{{ $selectedLanguage->id }}">
                    <div class="mb-3">
                        <label for="group" class="form-label">Group</label>
                        <input type="text" id="group" name="group" class="form-control" placeholder="e.g., auth, products, messages">
                    </div>
                    <div class="mb-3">
                        <label for="key" class="form-label">Key</label>
                        <input type="text" id="key" name="key" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="value" class="form-label">Value</label>
                        <textarea id="value" name="value" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Translation</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection