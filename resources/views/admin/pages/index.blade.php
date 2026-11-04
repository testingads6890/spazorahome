@extends('layouts.admin')

@section('title', 'Pages')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb-3">
        <h2 class="h4 mb-0">Pages</h2>
        <a href="{{ route('admin.pages.create') }}" class="btn btn-primary">Add Page</a>
    </div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <table class="table table-hover align-middle">
        <thead class="table-light"><tr><th>Title</th><th>Slug</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse($pages as $page)
            <tr>
                <td>{{ $page->title }}</td>
                <td><code>{{ $page->slug }}</code></td>
                <td><span class="badge bg-{{ $page->is_active ? 'success' : 'secondary' }}">{{ $page->is_active ? 'Active' : 'Inactive' }}</span></td>
                <td class="d-flex gap-1">
                    <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                    <form action="{{ route('admin.pages.destroy', $page) }}" method="POST" onsubmit="return confirm('Delete this page?');">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-muted">No pages yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    @if(method_exists($pages, 'links')){{ $pages->links() }}@endif
</div>
@endsection
