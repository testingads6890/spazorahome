@extends('layouts.admin')

@section('title', 'Exports')

@section('content')
<div class="container-fluid">
    <h2 class="h4 mb-1">Exports</h2>
    <p class="text-muted">Download the shop's data as CSV files, which open in Excel and Google Sheets. Leave the dates empty to export everything.</p>
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="row g-3">
        @foreach($exports as $route => [$title, $dateLabel, $description])
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="mb-1">{{ $title }}</h5>
                        <p class="text-muted small flex-grow-1">{{ $description }}</p>
                        <form method="GET" action="{{ route($route) }}">
                            <div class="small text-muted mb-1">{{ $dateLabel }}</div>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <input type="date" name="from" class="form-control form-control-sm w-auto" aria-label="{{ $title }}: from date">
                                <span class="small text-muted">to</span>
                                <input type="date" name="to" class="form-control form-control-sm w-auto" aria-label="{{ $title }}: to date">
                            </div>
                            <button class="btn btn-primary btn-sm mt-3"><i class="bi bi-download"></i> Download CSV</button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
