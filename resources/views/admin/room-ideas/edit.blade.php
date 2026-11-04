@extends('layouts.admin')

@section('title', '')

@section('content')
<div class="container mt-4">
        <a href="{{ route('admin.room-ideas.index') }}" class="btn btn-secondary mb-3">Back</a>
        <div class="card">
            <div class="card-header">Edit Room Idea</div>
            <div class="card-body">
                <form action="{{ route('admin.room-ideas.update', $roomIdea) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $roomIdea->title) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control" value="{{ old('slug', $roomIdea->slug) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control js-richtext" rows="4">{{ old('description', $roomIdea->description) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image</label>
                        <input type="text" name="image" class="form-control" value="{{ old('image', $roomIdea->image) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Room Type</label>
                        <select name="room_type" class="form-control" required>
                            <option value="living_room" {{ $roomIdea->room_type == 'living_room' ? 'selected' : '' }}>Living Room</option>
                            <option value="bedroom" {{ $roomIdea->room_type == 'bedroom' ? 'selected' : '' }}>Bedroom</option>
                            <option value="kitchen" {{ $roomIdea->room_type == 'kitchen' ? 'selected' : '' }}>Kitchen</option>
                            <option value="bathroom" {{ $roomIdea->room_type == 'bathroom' ? 'selected' : '' }}>Bathroom</option>
                            <option value="office" {{ $roomIdea->room_type == 'office' ? 'selected' : '' }}>Office</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Active</label>
                        <select name="is_active" class="form-control">
                            <option value="1" {{ $roomIdea->is_active ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ !$roomIdea->is_active ? 'selected' : '' }}>No</option>
                        </select>
                    </div>
                    <button class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>
    </div>

@endsection

