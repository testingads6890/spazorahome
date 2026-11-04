@extends('layouts.admin')

@section('title', 'Add Role')

@section('content')
@php $selected = old('permissions', isset($role) ? $role->permissions->pluck('id')->all() : []); @endphp
<div class="container mt-4">
    <div class="d-flex justify-content-between mb-4">
        <h2>Add Role</h2>
        <a href="{{ route('admin.permissions.index') }}" class="btn btn-secondary">Back</a>
    </div>
    <div class="card"><div class="card-body">
        <form action="{{ route('admin.permissions.store-role') }}" method="POST">
            @csrf
            
            <div class="mb-3">
                <label for="name" class="form-label">Role name</label>
                <input type="text" id="name" name="name" class="form-control" value="{{ old('name', isset($role) ? $role->name : '') }}" required>
                @error('name')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Permissions</label>
                <div class="row">
                @foreach($permissions as $permission)
                    <div class="col-md-4"><div class="form-check">
                        <input type="checkbox" class="form-check-input" id="p{{ $permission->id }}" name="permissions[]" value="{{ $permission->id }}" {{ in_array($permission->id, $selected) ? 'checked' : '' }}>
                        <label class="form-check-label" for="p{{ $permission->id }}">{{ $permission->name }}</label>
                    </div></div>
                @endforeach
                </div>
            </div>
            <button class="btn btn-primary">Save</button>
        </form>
    </div></div>
</div>
@endsection
