@extends('layouts.admin')

@section('title', 'Permissions')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h4 mb-0">Permission Management</h2>
        </div>
        <div class="col-12 text-end">
            <a href="{{ route('admin.permissions.create-role') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add New Role
            </a>
            <a href="{{ route('admin.permissions.create-permission') }}" class="btn btn-success">
                <i class="bi bi-plus-lg"></i> Add New Permission
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

    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Add a staff member</h5></div>
        <div class="card-body">
            <p class="text-muted small">Staff sign in at <a href="{{ route('admin.login') }}">{{ route('admin.login') }}</a> and only see the areas their role covers.</p>
            <div class="row g-2 small mb-3">
                @foreach(\App\Support\AdminArea::ROLES as $key => [$label, $desc])
                    <div class="col-md-6 col-xl-4"><div class="border rounded p-2 h-100"><strong>{{ $label }}</strong> <code>{{ $key }}</code><div class="text-muted">{{ $desc }}</div></div></div>
                @endforeach
            </div>
            <form method="POST" action="{{ route('admin.permissions.store-staff') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3"><label class="form-label small" for="staff_name">Name</label><input id="staff_name" name="name" value="{{ old('name') }}" class="form-control" required></div>
                <div class="col-md-3"><label class="form-label small" for="staff_email">Email</label><input id="staff_email" type="email" name="email" value="{{ old('email') }}" class="form-control" required></div>
                <div class="col-md-2"><label class="form-label small" for="staff_pw">Password</label><input id="staff_pw" type="password" name="password" class="form-control" autocomplete="new-password" required></div>
                <div class="col-md-2"><label class="form-label small" for="staff_pw2">Confirm</label><input id="staff_pw2" type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required></div>
                <div class="col-md-2"><label class="form-label small" for="staff_role">Role</label>
                    <select id="staff_role" name="role" class="form-select" required>
                        @foreach(\App\Support\AdminArea::ROLES as $key => [$label])<option value="{{ $key }}" @selected(old('role') === $key)>{{ $label }}</option>@endforeach
                        <option value="admin" @selected(old('role') === 'admin')>Administrator (full access)</option>
                    </select>
                </div>
                <div class="col-12"><button class="btn btn-primary"><i class="bi bi-person-plus"></i> Create staff account</button></div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <!-- Roles -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Roles</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Role Name</th>
                                    <th>Permissions</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($roles as $role)
                                    <tr>
                                        <td>{{ $role->name }}</td>
                                        <td>
                                            @foreach($role->permissions->take(3) as $permission)
                                                <span class="badge bg-secondary">{{ $permission->name }}</span>
                                            @endforeach
                                            @if($role->permissions->count() > 3)
                                                <span class="badge bg-light text-dark">+{{ $role->permissions->count() - 3 }} more</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.permissions.edit-role', $role) }}" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            @if($role->name !== 'admin' && $role->name !== 'vendor' && $role->name !== 'customer')
                                                <form action="{{ route('admin.permissions.destroy-role', $role) }}" 
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('Are you sure?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Permissions -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Permissions</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Permission Name</th>
                                    <th>Guard</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($permissions as $permission)
                                    <tr>
                                        <td>{{ $permission->name }}</td>
                                        <td>{{ $permission->guard_name }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Users with Roles -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Users & Roles</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Current Roles</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                    <tr>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>
                                            @foreach($user->roles as $role)
                                                <span class="badge bg-primary">{{ $role->name }}</span>
                                            @endforeach
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editUserRoleModal{{ $user->id }}">
                                                <i class="bi bi-pencil"></i> Edit Roles
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Role Modals -->
@foreach($users as $user)
    <div class="modal fade" id="editUserRoleModal{{ $user->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Roles - {{ $user->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.permissions.update-user-role', $user) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        @foreach($roles as $role)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="roles[]" 
                                       value="{{ $role->id }}" id="role{{ $role->id }}{{ $user->id }}"
                                       {{ $user->hasRole($role->name) ? 'checked' : '' }}>
                                <label class="form-check-label" for="role{{ $role->id }}{{ $user->id }}">
                                    {{ $role->name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Roles</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection