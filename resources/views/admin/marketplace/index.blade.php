@extends('layouts.admin')

@section('title', 'Marketplace')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h4 mb-0">Marketplace Management</h2>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Commission for sellers</h5></div>
        <div class="card-body">
            <form action="{{ route('admin.marketplace.default-commission.update') }}" method="POST" class="row g-3 align-items-end">
                @csrf
                @method('PUT')
                <div class="col-md-4">
                    <label for="default_commission_rate" class="form-label">Default commission (%)</label>
                    <div class="input-group">
                        <input type="number" id="default_commission_rate" name="default_commission_rate" class="form-control" min="0" max="100" step="0.01" value="{{ old('default_commission_rate', \App\Models\Vendor::defaultCommissionRate()) }}" required>
                        <span class="input-group-text">%</span>
                    </div>
                    @error('default_commission_rate')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-8">
                    <p class="small text-muted mb-2">Applies to every seller who has no rate of their own. Set a different rate for one seller from their page under Sellers. Changes apply to new orders.</p>
                    <button type="submit" class="btn btn-primary">Save default</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Multivendor Status</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.marketplace.toggle') }}" method="POST">
                        @csrf
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Enable Multivendor</span>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="multivendor_enabled" 
                                       {{ $multivendorEnabled ? 'checked' : '' }} 
                                       onchange="this.form.submit()">
                            </div>
                        </div>
                    </form>
                    <p class="text-muted mt-2">
                        {{ $multivendorEnabled ? 'Multivendor is currently enabled. Vendors can register and list products.' : 'Multivendor is currently disabled.' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Total Vendors</h5>
                </div>
                <div class="card-body">
                    <h2 class="display-4">{{ $vendors->total() }}</h2>
                    <a href="{{ route('admin.marketplace.vendors') }}" class="btn btn-outline-primary">View All Vendors</a>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Total Products</h5>
                </div>
                <div class="card-body">
                    <h2 class="display-4">{{ $vendors->sum('products_count') }}</h2>
                    <a href="{{ route('admin.marketplace.products') }}" class="btn btn-outline-primary">View All Products</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Vendors</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Products</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($vendors->take(5) as $vendor)
                                    <tr>
                                        <td>{{ $vendor->company_name ?? $vendor->name }}</td>
                                        <td>{{ $vendor->email }}</td>
                                        <td>{{ $vendor->products_count }}</td>
                                        <td>{{ $vendor->created_at->format('M d, Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <a href="{{ route('admin.marketplace.vendors') }}" class="btn btn-primary w-100 mt-2">View All Vendors</a>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <a href="{{ route('admin.marketplace.vendors') }}" class="btn btn-outline-primary w-100 py-3">
                                <i class="bi bi-people d-block mb-2 fs-4"></i>
                                Manage Vendors
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('admin.marketplace.products') }}" class="btn btn-outline-success w-100 py-3">
                                <i class="bi bi-box-seam d-block mb-2 fs-4"></i>
                                Manage Products
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('admin.marketplace.commissions') }}" class="btn btn-outline-info w-100 py-3">
                                <i class="bi bi-percent d-block mb-2 fs-4"></i>
                                Commissions
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('admin.marketplace.payouts') }}" class="btn btn-outline-warning w-100 py-3">
                                <i class="bi bi-currency-rupee d-block mb-2 fs-4"></i>
                                Payouts
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection