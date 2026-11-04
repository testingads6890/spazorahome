@extends('layouts.admin')

@section('title', 'Sliders')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb-3">
        <h2 class="h4 mb-0">Sliders</h2>
        <a href="{{ route('admin.sliders.create') }}" class="btn btn-primary">Add Slider</a>
    </div>
    <p class="text-muted">The first active slider with slides replaces the banner at the top of the home page.</p>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light"><tr><th>Name</th><th>Location</th><th>Slides</th><th>Position</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse($sliders as $slider)
            <tr>
                <td>{{ $slider->name }}</td>
                <td>{{ \App\Models\Slider::LOCATIONS[$slider->location] ?? $slider->location }}</td>
                <td>{{ count($slider->slides ?? []) }}</td>
                <td>{{ $slider->sort_order }}</td>
                <td><span class="badge bg-{{ $slider->is_active ? 'success' : 'secondary' }}">{{ $slider->is_active ? 'Active' : 'Inactive' }}</span></td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="{{ route('admin.sliders.edit', $slider) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form action="{{ route('admin.sliders.destroy', $slider) }}" method="POST" onsubmit="return confirm('Delete this slider?');">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted">No sliders yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    {{ $sliders->links() }}
</div>
@endsection
