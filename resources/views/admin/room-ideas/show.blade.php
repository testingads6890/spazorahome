@extends('layouts.admin')

@section('title', '')

@section('content')
    
<div class="container mt-4">
        <a href="{{ route('admin.room-ideas.index') }}" class="btn btn-secondary mb-3">Back</a>
        <div class="card">
            <div class="card-header">Room Idea Details</div>
            <div class="card-body">
                <p><strong>Title:</strong> {{ $roomIdea->title }}</p>
                <p><strong>Room Type:</strong> {{ ucfirst(str_replace('_', ' ', $roomIdea->room_type)) }}</p>
                <p><strong>Active:</strong> {{ $roomIdea->is_active ? 'Yes' : 'No' }}</p>
            </div>
        </div>
    </div>

@endsection

