@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h4 mb-0">System Settings</h2>
        </div>
        <div class="col-12 text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSettingModal">
                <i class="bi bi-plus-lg"></i> Add New Setting
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

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Key</th>
                    <th>Value</th>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($settings as $setting)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><code>{{ $setting->key }}</code></td>
                        <td>
                            @if(is_array($setting->value))
                                <pre class="mb-0">{{ json_encode($setting->value, JSON_PRETTY_PRINT) }}</pre>
                            @elseif(is_bool($setting->value))
                                <span class="badge bg-{{ $setting->value ? 'success' : 'secondary' }}">
                                    {{ $setting->value ? 'true' : 'false' }}
                                </span>
                            @else
                                {{ $setting->value }}
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-info">{{ $setting->type }}</span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editSettingModal{{ $setting->id }}">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <form action="{{ route('admin.settings.destroy', $setting) }}" method="POST" onsubmit="return confirm('Are you sure?');">
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

<!-- Create Setting Modal -->
<div class="modal fade" id="createSettingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Setting</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.settings.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="key" class="form-label">Setting Key</label>
                        <input type="text" id="key" name="key" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="value" class="form-label">Value</label>
                        <textarea id="value" name="value" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label">Type</label>
                        <select id="type" name="type" class="form-control">
                            <option value="string">String</option>
                            <option value="boolean">Boolean</option>
                            <option value="integer">Integer</option>
                            <option value="json">JSON</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create Setting</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection