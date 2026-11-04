@extends('layouts.admin')

@section('title', '')

@section('content')
<div class="container mt-4">
        <a href="{{ route('admin.reviews.index') }}" class="btn btn-secondary mb-3">Back</a>
        <div class="card">
            <div class="card-header">Edit Review</div>
            <div class="card-body">
                <form action="{{ route('admin.reviews.update', $review) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Rating</label>
                        <select name="rating" class="form-control" required>
                            @for($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}" {{ $review->rating == $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $review->title) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Comment</label>
                        <textarea name="comment" class="form-control">{{ old('comment', $review->comment) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Approved</label>
                        <select name="is_approved" class="form-control">
                            <option value="1" {{ $review->is_approved ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ !$review->is_approved ? 'selected' : '' }}>No</option>
                        </select>
                    </div>
                    <button class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>
    </div>

@endsection

