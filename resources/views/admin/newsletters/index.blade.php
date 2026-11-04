@extends('layouts.admin')

@section('title', 'Newsletter')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
        <div>
            <h2 class="h4 mb-0">Newsletter subscribers</h2>
            <span class="text-muted">{{ $activeCount }} active of {{ $totalCount }}</span>
        </div>
        <a href="{{ route('admin.newsletters.export', request()->only(['q', 'status'])) }}" class="btn btn-outline-primary align-self-start"><i class="bi bi-download"></i> Export CSV</a>
    </div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="row g-2 mb-3">
        <div class="col-lg-7">
            <form method="GET" action="{{ route('admin.newsletters.index') }}" class="d-flex flex-wrap gap-2">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control w-auto flex-grow-1" placeholder="Search email">
                <select name="status" class="form-select w-auto">
                    <option value="">All</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Unsubscribed</option>
                </select>
                <button class="btn btn-secondary">Filter</button>
            </form>
        </div>
        <div class="col-lg-5">
            <form method="POST" action="{{ route('admin.newsletters.store') }}" class="d-flex gap-2">
                @csrf
                <input type="email" name="email" class="form-control" placeholder="Add an email address" required maxlength="255">
                <button class="btn btn-primary text-nowrap">Add</button>
            </form>
        </div>
    </div>

    <div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light"><tr><th>Email</th><th>Status</th><th>Subscribed</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse($subscribers as $subscriber)
            <tr>
                <td>{{ $subscriber->email }}</td>
                <td><span class="badge bg-{{ $subscriber->is_active ? 'success' : 'secondary' }}">{{ $subscriber->is_active ? 'Active' : 'Unsubscribed' }}</span></td>
                <td>{{ optional($subscriber->subscribed_at)->format('d M Y') ?? '-' }}</td>
                <td>
                    <div class="d-flex gap-1">
                        <form action="{{ route('admin.newsletters.update', $subscriber) }}" method="POST">
                            @csrf @method('PUT')
                            <button class="btn btn-sm btn-outline-primary">{{ $subscriber->is_active ? 'Unsubscribe' : 'Re-activate' }}</button>
                        </form>
                        <form action="{{ route('admin.newsletters.destroy', $subscriber) }}" method="POST" onsubmit="return confirm('Delete this subscriber?');">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-muted">No subscribers found.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    {{ $subscribers->links() }}
</div>
@endsection
