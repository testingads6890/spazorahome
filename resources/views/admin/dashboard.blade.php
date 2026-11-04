@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
<div class="admin-dashboard">
    <div class="dashboard-header">
        <h1 class="h3 mb-0">Admin Dashboard</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active">Dashboard</li>
            </ol>
        </nav>
    </div>

    <div class="row g-4">
        <!-- Statistics Cards -->
        <div class="col-xl-3 col-md-6">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Total Users</h6>
                            <h3 class="mb-0">{{ number_format($stats['users']) }}</h3>
                        </div>
                        <div class="stats-icon bg-primary text-white">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Total Products</h6>
                            <h3 class="mb-0">{{ number_format($stats['products']) }}</h3>
                        </div>
                        <div class="stats-icon bg-success text-white">
                            <i class="bi bi-box-seam"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Total Orders</h6>
                            <h3 class="mb-0">{{ number_format($stats['orders']) }}</h3>
                        </div>
                        <div class="stats-icon bg-warning text-white">
                            <i class="bi bi-cart"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Revenue</h6>
                            <h3 class="mb-0">₹{{ number_format($stats['revenue'], 2) }}</h3>
                        </div>
                        <div class="stats-icon bg-info text-white">
                            <i class="bi bi-graph-up"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-primary w-100 py-3">
                                <i class="bi bi-tag d-block mb-2 fs-4"></i>
                                Manage Categories
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('admin.banners.index') }}" class="btn btn-outline-success w-100 py-3">
                                <i class="bi bi-image d-block mb-2 fs-4"></i>
                                Manage Banners
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('admin.static-pages.index') }}" class="btn btn-outline-info w-100 py-3">
                                <i class="bi bi-file-text d-block mb-2 fs-4"></i>
                                Static Pages
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('admin.vendors.index') }}" class="btn btn-outline-warning w-100 py-3">
                                <i class="bi bi-shop d-block mb-2 fs-4"></i>
                                Manage Vendors
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('admin.reviews.index') }}" class="btn btn-outline-danger w-100 py-3">
                                <i class="bi bi-star d-block mb-2 fs-4"></i>
                                Reviews
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('admin.support-tickets.index') }}" class="btn btn-outline-dark w-100 py-3">
                                <i class="bi bi-headset d-block mb-2 fs-4"></i>
                                Support Tickets
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <div class="activity-timeline">
                        @forelse($activity as $item)
                            <div class="activity-item">
                                <div class="activity-marker bg-{{ $item['color'] }}"></div>
                                <div class="activity-content">
                                    <h6 class="mb-1">{{ $item['title'] }}</h6>
                                    <p class="text-muted mb-0 small">{{ $item['text'] }}</p>
                                    <span class="text-muted small">{{ $item['time']->diffForHumans() }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No activity yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection