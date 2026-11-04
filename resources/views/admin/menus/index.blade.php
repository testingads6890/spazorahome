@extends('layouts.admin')

@section('title', 'Menus')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb-3">
        <h2 class="h4 mb-0">Menus</h2>
        <a href="{{ route('admin.menus.create') }}" class="btn btn-primary">Add Menu</a>
    </div>
    <p class="text-muted">Extra links shown in the storefront's top bar or footer.</p>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light"><tr><th>Name</th><th>Location</th><th>Links</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse($menus as $menu)
            <tr>
                <td>{{ $menu->name }}</td>
                <td>{{ \App\Models\Menu::LOCATIONS[$menu->location] ?? $menu->location }}</td>
                <td>{{ collect($menu->items ?? [])->pluck('label')->implode(', ') ?: '-' }}</td>
                <td><span class="badge bg-{{ $menu->is_active ? 'success' : 'secondary' }}">{{ $menu->is_active ? 'Active' : 'Inactive' }}</span></td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="{{ route('admin.menus.edit', $menu) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form action="{{ route('admin.menus.destroy', $menu) }}" method="POST" onsubmit="return confirm('Delete this menu?');">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted">No menus yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    {{ $menus->links() }}
</div>
@endsection
