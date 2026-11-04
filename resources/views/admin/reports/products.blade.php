@extends('layouts.admin')

@section('title', 'Products Report')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="mb-0">Products Report</h1>
    <a href="{{ route('admin.exports.products') }}" class="btn btn-outline-primary"><i class="bi bi-download"></i> Export catalogue CSV</a>
</div>
<p class="text-muted">Best sellers by units sold. Cancelled orders are left out.</p>

@if($products->isEmpty())
    <div class="card card-body text-muted">Nothing has been sold yet.</div>
@else
    <div class="card mb-3">
        <div class="card-body">
            <canvas id="productsChart" height="90"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @foreach($products as $row)
                <div class="d-flex justify-content-between gap-3 py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                    <span>{{ $loop->iteration }}. {{ $row->product_name }}</span>
                    <span class="text-nowrap">{{ (int) $row->units }} {{ \Illuminate\Support\Str::plural('unit', (int) $row->units) }} &middot; <strong>₹{{ number_format($row->revenue, 2) }}</strong></span>
                </div>
            @endforeach
        </div>
    </div>
@endif
@endsection

@push('scripts')
@if($products->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    if (window.Chart) {
        new Chart(document.getElementById('productsChart').getContext('2d'), {
            type: 'bar',
            data: { labels: @json($labels), datasets: [{ label: 'Units sold', data: @json($data), backgroundColor: 'var(--accent)' }] },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
        });
    }
</script>
@endif
@endpush
