@extends('layouts.admin')

@section('title', '')

@section('content')
    
<div class="container mt-4">
        <h1>Customers</h1>
        <form method="GET" class="row g-2 mb-3">
            <div class="col-auto">
                <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
            </div>
            <div class="col-auto">
                <button class="btn btn-secondary">Filter</button>
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('admin.exports.customers') }}" class="btn btn-outline-primary"><i class="bi bi-download"></i> Export CSV</a>
            </div>
        </form>
        <table class="table table-bordered">
            <thead>
                <tr><th>Name</th><th>Email</th><th>Registered</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @foreach($customers as $customer)
                    <tr>
                        <td>{{ $customer->name }}</td>
                        <td>{{ $customer->email }}</td>
                        <td>{{ $customer->created_at->format('Y-m-d') }}</td>
                        <td>
                            <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-sm btn-info">View</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $customers->links() }}
    </div>

@endsection

