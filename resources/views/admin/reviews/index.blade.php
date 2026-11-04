@extends('layouts.admin')

@section('title', '')

@section('content')
    
<div class="container mt-4">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Reviews</h1>
        </div>
        <form method="GET" class="row g-2 mb-3">
            <div class="col-auto">
                <select name="is_approved" class="form-select">
                    <option value="">All Status</option>
                    <option value="1" {{ request('is_approved') === '1' ? 'selected' : '' }}>Approved</option>
                    <option value="0" {{ request('is_approved') === '0' ? 'selected' : '' }}>Pending</option>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-secondary">Filter</button>
            </div>
        </form>
        <table class="table table-bordered">
            <thead>
                <tr><th>User</th><th>Product</th><th>Rating</th><th>Approved</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @foreach($reviews as $review)
                    <tr>
                        <td>{{ $review->user->name }}</td>
                        <td>{{ $review->product->name }}</td>
                        <td>{{ $review->rating }}/5</td>
                        <td>{{ $review->is_approved ? 'Yes' : 'No' }}</td>
                        <td>
                            <a href="{{ route('admin.reviews.edit', $review) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $reviews->links() }}
    </div>

@endsection

