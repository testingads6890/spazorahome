@extends('layouts.admin')

@section('title', 'Sales Report')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="mb-0">Sales Report</h1>
    <a href="{{ route('admin.exports.sales-by-day', ['from' => $from, 'to' => $to]) }}" class="btn btn-outline-primary"><i class="bi bi-download"></i> Export CSV</a>
</div>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.sales') }}" class="row g-2 align-items-center">
            <div class="col-auto">
                <input type="date" name="from" class="form-control" value="{{ $from }}" aria-label="From date">
            </div>
            <div class="col-auto">
                <input type="date" name="to" class="form-control" value="{{ $to }}" aria-label="To date">
            </div>
            <div class="col-auto">
                <button class="btn btn-primary">Filter</button>
            </div>
            <div class="col-auto text-muted small">Cancelled orders are left out.</div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h5>Total Sales</h5>
                <p class="h3 mb-0">₹{{ number_format($totalSales, 2) }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5>Total Orders</h5>
                <p class="h3 mb-0">{{ $totalOrders }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-secondary">
            <div class="card-body">
                <h5>Average Order</h5>
                <p class="h3 mb-0">₹{{ number_format($totalOrders ? $totalSales / $totalOrders : 0, 2) }}</p>
            </div>
        </div>
    </div>
</div>

@if($sales->isEmpty())
    <div class="card card-body text-muted">No orders in this period.</div>
@else
    <div class="card mb-3">
        <div class="card-body">
            <canvas id="salesChart" height="90"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @foreach($sales->sortByDesc('date') as $day)
                <div class="d-flex justify-content-between py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                    <span>{{ \Illuminate\Support\Carbon::parse($day->date)->format('d M Y') }} <small class="text-muted">&middot; {{ $day->orders }} {{ \Illuminate\Support\Str::plural('order', (int) $day->orders) }}</small></span>
                    <strong>₹{{ number_format($day->total, 2) }}</strong>
                </div>
            @endforeach
        </div>
    </div>
@endif
@endsection

@push('scripts')
@if($sales->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    if (window.Chart) {
        new Chart(document.getElementById('salesChart').getContext('2d'), {
            type: 'line',
            data: { labels: @json($labels), datasets: [{ label: 'Sales (₹)', data: @json($data), borderColor: 'var(--brand)', backgroundColor: 'rgba(31, 61, 54, .12)', fill: true, tension: .25 }] },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    }
</script>
@endif
@endpush
