@extends('layouts.vendor')

@php $editing = $member->exists; @endphp
@section('title', $editing ? 'Edit staff member' : 'Add staff member')

@section('content')
<a href="{{ route('vendor.staff.index') }}" class="btn btn-outline-secondary btn-sm mb-3"><i class="bi bi-arrow-left"></i> All staff</a>

@if($errors->any())<div class="alert alert-danger">Please fix the highlighted fields.</div>@endif

<form method="POST" action="{{ $editing ? route('vendor.staff.update', $member) : route('vendor.staff.store') }}" class="card" style="max-width:720px">
    @csrf
    @if($editing) @method('PUT') @endif
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">Full name</label>
                <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $member->user?->name) }}">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label">Email (used to sign in) @unless($editing)<span class="text-danger">*</span>@endunless</label>
                <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $member->user?->email) }}" @if($editing) readonly @else required @endif>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            @unless($editing)
            <div class="col-md-6">
                <label for="password" class="form-label">Password</label>
                <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="password_confirmation" class="form-label">Repeat password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
            </div>
            <div class="col-12 form-text mt-0">For a new email, set a password of at least 8 characters. If the person already has an account here, leave both empty: they keep signing in with their own password.</div>
            @endunless

            <div class="col-12">
                <label class="form-label d-block">Role</label>
                @foreach($roles as $key => $label)
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="role" id="role_{{ $key }}" value="{{ $key }}" @checked(old('role', $member->role) === $key) data-role>
                        <label class="form-check-label" for="role_{{ $key }}">{{ $label }}</label>
                    </div>
                @endforeach
                @error('role')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="col-12" id="areaBox">
                <label class="form-label d-block">Areas this person can use</label>
                @php $picked = old('permissions', $member->permissions ?? []); @endphp
                <div class="form-text mb-1">Nothing ticked: they only see the dashboard.</div>
                <div class="d-flex flex-wrap gap-3">
                    @foreach($areas as $key => $label)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="permissions[]" id="perm_{{ $key }}" value="{{ $key }}" @checked(in_array($key, (array) $picked, true))>
                            <label class="form-check-label" for="perm_{{ $key }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
                @error('permissions')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
    <div class="card-footer d-flex gap-2">
        <button class="btn btn-primary">{{ $editing ? 'Save changes' : 'Add staff member' }}</button>
        <a href="{{ route('vendor.staff.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<script>
    (function () {
        var box = document.getElementById('areaBox');
        function sync() { var r = document.querySelector('[data-role]:checked'); box.hidden = r && r.value === 'manager'; }
        document.querySelectorAll('[data-role]').forEach(function (r) { r.addEventListener('change', sync); });
        sync();
    })();
</script>
@endsection
