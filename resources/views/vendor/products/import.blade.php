@extends('layouts.vendor')

@section('title', 'Import products from Excel')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <p class="text-muted mb-0">Add many products at once. Every row is checked; good rows wait for the marketplace team to approve them.</p>
    <a href="{{ route('vendor.products.index') }}" class="btn btn-outline-secondary">Back to products</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <ol class="mb-3 ps-3">
            <li>Download the template and fill the <strong>Products</strong> sheet: one row per product (up to {{ $maxRows }} rows).</li>
            <li>Upload it here. Photos are added afterwards from <em>Products &gt; Edit</em>.</li>
            <li>Fix any rows listed below with a reason and upload only those rows again. Rows already added are skipped, so re-uploading is safe.</li>
        </ol>
        <a href="{{ route('vendor.products.import.template') }}" class="btn btn-outline-primary mb-3"><i class="bi bi-download"></i> Download Excel template</a>

        <form method="POST" action="{{ route('vendor.products.import.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="row g-2 align-items-start">
                <div class="col-md-8">
                    <input type="file" name="file" accept=".xlsx,.csv" class="form-control @error('file') is-invalid @enderror" required>
                    @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4"><button class="btn btn-primary w-100">Upload and check</button></div>
            </div>
        </form>
    </div>
</div>

@if($r = session('import_result'))
    <div class="card">
        <div class="card-header fw-semibold">Result: {{ $r['total'] }} {{ Str::plural('row', $r['total']) }} read</div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="badge text-bg-success fs-6">{{ $r['created'] }} added for approval</span>
                <span class="badge text-bg-secondary fs-6">{{ count($r['skipped']) }} skipped</span>
                <span class="badge {{ $r['errors'] ? 'text-bg-danger' : 'text-bg-light border' }} fs-6">{{ count($r['errors']) }} with mistakes</span>
            </div>
            @if($r['errors'])
                <h6>Rows to fix</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-sm align-middle">
                        <thead><tr><th style="width:70px">Row</th><th>Product</th><th>What to fix</th></tr></thead>
                        <tbody>
                        @foreach($r['errors'] as $e)
                            <tr><td>{{ $e['row'] }}</td><td>{{ $e['name'] }}</td><td><ul class="mb-0 ps-3">@foreach($e['messages'] as $m)<li>{{ $m }}</li>@endforeach</ul></td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            @if($r['skipped'])
                <h6>Skipped</h6>
                <ul class="mb-3">@foreach($r['skipped'] as $s)<li>Row {{ $s['row'] }} ({{ $s['name'] }}): {{ $s['reason'] }}</li>@endforeach</ul>
            @endif
            @if($r['created'])<a href="{{ route('vendor.products.index', ['status' => 'pending']) }}" class="btn btn-outline-primary">See products waiting for approval</a>@endif
        </div>
    </div>
@endif
@endsection
