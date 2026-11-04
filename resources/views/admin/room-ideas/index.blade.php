@extends('layouts.admin')

@section('title', '')

@section('content')
    
<div class="container mt-4">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Room Ideas</h1>
            <a href="{{ route('admin.room-ideas.create') }}" class="btn btn-primary">Add Room Idea</a>
        </div>
        <form method="GET" class="row g-2 mb-3">
            <div class="col-auto">
                <select name="room_type" class="form-select">
                    <option value="">All Types</option>
                    <option value="living_room" {{ request('room_type') == 'living_room' ? 'selected' : '' }}>Living Room</option>
                    <option value="bedroom" {{ request('room_type') == 'bedroom' ? 'selected' : '' }}>Bedroom</option>
                    <option value="kitchen" {{ request('room_type') == 'kitchen' ? 'selected' : '' }}>Kitchen</option>
                    <option value="bathroom" {{ request('room_type') == 'bathroom' ? 'selected' : '' }}>Bathroom</option>
                    <option value="office" {{ request('room_type') == 'office' ? 'selected' : '' }}>Office</option>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-secondary">Filter</button>
            </div>
        </form>
        <table class="table table-bordered">
            <thead>
                <tr><th>Title</th><th>Room Type</th><th>Active</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @foreach($roomIdeas as $idea)
                    <tr>
                        <td>{{ $idea->title }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $idea->room_type)) }}</td>
                        <td>{{ $idea->is_active ? 'Yes' : 'No' }}</td>
                        <td>
                            <a href="{{ route('admin.room-ideas.edit', $idea) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('admin.room-ideas.destroy', $idea) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $roomIdeas->links() }}
    </div>

@endsection

