@extends('layouts.admin')

@php $editing = $menu->exists; @endphp

@section('title', $editing ? 'Edit Menu' : 'Add Menu')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>{{ $editing ? 'Edit Menu' : 'Add Menu' }}</h2>
        <a href="{{ route('admin.menus.index') }}" class="btn btn-secondary">Back to List</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ $editing ? route('admin.menus.update', $menu) : route('admin.menus.store') }}" method="POST">
                @csrf
                @if($editing) @method('PUT') @endif

                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $menu->name) }}" required maxlength="255">
                    <div class="form-text">Only for your own reference.</div>
                    @error('name')<div class="text-danger">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="location" class="form-label">Where it appears</label>
                    <select id="location" name="location" class="form-select">
                        @foreach(\App\Models\Menu::LOCATIONS as $key => $label)
                            <option value="{{ $key }}" {{ old('location', $menu->location) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('location')<div class="text-danger">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="links" class="form-label">Links</label>
                    <textarea id="links" name="links" rows="6" class="form-control" placeholder="Sale | /search?sort=price_asc&#10;Blog | https://example.com/blog">{{ old('links', $menu->linksText()) }}</textarea>
                    <div class="form-text">One link per line: the label, a <code>|</code>, then a full address (<code>https://...</code>) or a path on this site (<code>/about-us</code>). Up to 12 links.</div>
                    @error('links')<div class="text-danger">{{ $message }}</div>@enderror
                </div>

                <input type="hidden" name="is_active" value="0">
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', $menu->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>

                <button type="submit" class="btn btn-primary">Save</button>
            </form>
        </div>
    </div>
</div>
@endsection
