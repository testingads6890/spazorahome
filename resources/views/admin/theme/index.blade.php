@extends('layouts.admin')

@section('title', 'Theme Settings')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h4 mb-0">Theme Settings</h2>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header">Logo</div>
        <div class="card-body">
            <p class="text-muted small mb-3">This logo is shown in the header and footer of the storefront. Use PNG, JPG or WEBP, up to 2 MB. Save the theme form below to apply.</p>
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="border rounded p-2 bg-light" style="min-width:160px;text-align:center">
                    <img src="{{ $logoUrl }}" alt="Current logo" style="max-height:70px;max-width:200px;object-fit:contain">
                </div>
                <div class="small text-muted">{{ $hasCustomLogo ? 'Custom logo in use' : 'Default logo in use' }}</div>
            </div>
            @if ($hasCustomLogo)
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="remove_logo" form="theme-form">
                    <label class="form-check-label" for="remove_logo">Remove custom logo and go back to the default</label>
                </div>
            @endif
            <div class="mb-1">
                <label for="logo" class="form-label">Upload new logo</label>
                <input type="file" id="logo" name="logo" form="theme-form" class="form-control @error('logo') is-invalid @enderror" accept=".png,.jpg,.jpeg,.webp">
                @error('logo')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Color Schemes</div>
        <div class="card-body">
            <form id="theme-form" action="{{ route('admin.theme.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="primary_color" class="form-label">Primary Color</label>
                        <input type="color" id="primary_color" name="primary_color" class="form-control" 
                               value="{{ old('primary_color', $settings['primary_color']) }}">
                        @error('primary_color')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="secondary_color" class="form-label">Secondary Color</label>
                        <input type="color" id="secondary_color" name="secondary_color" class="form-control" 
                               value="{{ old('secondary_color', $settings['secondary_color']) }}">
                        @error('secondary_color')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="success_color" class="form-label">Success Color</label>
                        <input type="color" id="success_color" name="success_color" class="form-control" 
                               value="{{ old('success_color', $settings['success_color']) }}">
                        @error('success_color')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="danger_color" class="form-label">Danger Color</label>
                        <input type="color" id="danger_color" name="danger_color" class="form-control" 
                               value="{{ old('danger_color', $settings['danger_color']) }}">
                        @error('danger_color')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="warning_color" class="form-label">Warning Color</label>
                        <input type="color" id="warning_color" name="warning_color" class="form-control" 
                               value="{{ old('warning_color', $settings['warning_color']) }}">
                        @error('warning_color')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="info_color" class="form-label">Info Color</label>
                        <input type="color" id="info_color" name="info_color" class="form-control" 
                               value="{{ old('info_color', $settings['info_color']) }}">
                        @error('info_color')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <hr>
                <h5>Layout Styles</h5>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="sidebar_style" class="form-label">Sidebar Style</label>
                        <select id="sidebar_style" name="sidebar_style" class="form-select">
                            <option value="dark" {{ old('sidebar_style', $settings['sidebar_style']) == 'dark' ? 'selected' : '' }}>Dark</option>
                            <option value="light" {{ old('sidebar_style', $settings['sidebar_style']) == 'light' ? 'selected' : '' }}>Light</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="navbar_style" class="form-label">Navbar Style</label>
                        <select id="navbar_style" name="navbar_style" class="form-select">
                            <option value="dark" {{ old('navbar_style', $settings['navbar_style']) == 'dark' ? 'selected' : '' }}>Dark</option>
                            <option value="light" {{ old('navbar_style', $settings['navbar_style']) == 'light' ? 'selected' : '' }}>Light</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="footer_style" class="form-label">Footer Style</label>
                        <select id="footer_style" name="footer_style" class="form-select">
                            <option value="dark" {{ old('footer_style', $settings['footer_style']) == 'dark' ? 'selected' : '' }}>Dark</option>
                            <option value="light" {{ old('footer_style', $settings['footer_style']) == 'light' ? 'selected' : '' }}>Light</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Update Theme</button>
            </form>
        </div>
    </div>
</div>
@endsection