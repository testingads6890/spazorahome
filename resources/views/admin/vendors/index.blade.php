@extends('layouts.admin')

@section('title', 'Vendors')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="mb-0">Vendors</h1>
    <a href="{{ route('admin.exports.vendors') }}" class="btn btn-outline-primary"><i class="bi bi-download"></i> Export CSV</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<table class="table table-bordered">
    <thead>
        <tr><th>Business Name</th><th>Email</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
        @foreach($vendors as $vendor)
            <tr>
                <td>{{ $vendor->business_name }}</td>
                <td>{{ $vendor->email }}</td>
                <td>
                    <span class="badge bg-{{ $vendor->status == 'approved' ? 'success' : ($vendor->status == 'pending' ? 'warning' : 'danger') }}">{{ ucfirst($vendor->status) }}</span>
                </td>
                <td>
                    <a href="{{ route('admin.vendors.show', $vendor) }}" class="btn btn-sm btn-info">View</a>
                    @if($vendor->status == 'pending')
                        <form action="{{ route('admin.vendors.approve', $vendor) }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-success">Approve</button>
                        </form>
                        <form action="{{ route('admin.vendors.reject', $vendor) }}" method="POST" class="d-inline">
                            @csrf
                            <input type="text" name="reason" class="form-control form-control-sm d-inline w-auto" placeholder="Reason" required>
                            <button class="btn btn-sm btn-danger">Reject</button>
                        </form>
                    @endif
                    @if($vendor->status == 'approved')
                        <form action="{{ route('admin.vendors.suspend', $vendor) }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-warning">Suspend</button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
{{ $vendors->links() }}
@endsection

