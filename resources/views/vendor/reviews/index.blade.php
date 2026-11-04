@extends('layouts.vendor')

@section('title', 'Reviews')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card h-100"><div class="card-body">
        <div class="text-muted small">Average rating</div>
        <div class="fs-2 fw-bold">{{ number_format($stats['average'], 1) }} <span class="fs-6 text-warning">@for($i = 1; $i <= 5; $i++)<i class="bi bi-star{{ $i <= round($stats['average']) ? '-fill' : '' }}"></i>@endfor</span></div>
        <div class="small text-muted">{{ $stats['count'] }} review(s) on your products</div>
    </div></div></div>
    <div class="col-md-5"><div class="card h-100"><div class="card-body small">
        @for($s = 5; $s >= 1; $s--)
            @php $c = (int) ($stats['by_star'][$s] ?? 0); $pct = $stats['count'] ? round($c / $stats['count'] * 100) : 0; @endphp
            <a href="{{ route('vendor.reviews.index', ['rating' => $s]) }}" class="d-flex align-items-center gap-2 text-decoration-none text-body mb-1">
                <span style="width:28px">{{ $s }}<i class="bi bi-star-fill text-warning"></i></span>
                <span class="progress flex-grow-1" style="height:8px"><span class="progress-bar bg-warning" style="width: {{ $pct }}%"></span></span>
                <span class="text-muted" style="width:32px">{{ $c }}</span>
            </a>
        @endfor
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body">
        <div class="text-muted small">Waiting for your reply</div>
        <div class="fs-2 fw-bold">{{ $stats['unanswered'] }}</div>
        <a href="{{ route('vendor.reviews.index', ['show' => 'unanswered']) }}" class="small">Show these</a>
    </div></div></div>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('vendor.reviews.index') }}" class="btn btn-sm {{ ! request('rating') && ! request('show') ? 'btn-primary' : 'btn-outline-primary' }}">All</a>
    <a href="{{ route('vendor.reviews.index', ['show' => 'unanswered']) }}" class="btn btn-sm {{ request('show') === 'unanswered' ? 'btn-primary' : 'btn-outline-primary' }}">Not replied</a>
    @for($s = 5; $s >= 1; $s--)<a href="{{ route('vendor.reviews.index', ['rating' => $s]) }}" class="btn btn-sm {{ (int) request('rating') === $s ? 'btn-primary' : 'btn-outline-primary' }}">{{ $s }} ★</a>@endfor
</div>

@forelse($reviews as $review)
    <div class="card mb-3"><div class="card-body">
        <div class="d-flex flex-wrap justify-content-between gap-2">
            <div>
                <strong>{{ $review->user?->name ?? 'Customer' }}</strong>
                <span class="text-warning ms-1">@for($i = 1; $i <= 5; $i++)<i class="bi bi-star{{ $i <= $review->rating ? '-fill' : '' }}"></i>@endfor</span>
                @unless($review->is_approved)<span class="badge text-bg-light border ms-1">Awaiting approval</span>@endunless
                <div class="small text-muted">on <a href="{{ route('products.show', $review->product) }}" target="_blank" rel="noopener">{{ $review->product?->name }}</a></div>
            </div>
            <span class="small text-muted">{{ $review->created_at->format('d M Y') }}</span>
        </div>
        @if($review->title)<div class="fw-semibold mt-2">{{ $review->title }}</div>@endif
        <p class="mb-2">{{ $review->comment }}</p>
        <form method="POST" action="{{ route('vendor.reviews.reply', $review) }}">
            @csrf
            <label class="form-label small text-muted" for="reply{{ $review->id }}">Your public reply @if($review->vendor_replied_at)<span>(posted {{ \Illuminate\Support\Carbon::parse($review->vendor_replied_at)->format('d M Y') }})</span>@endif</label>
            <textarea id="reply{{ $review->id }}" name="vendor_reply" rows="2" class="form-control form-control-sm mb-2" maxlength="2000" placeholder="Thank the customer or answer their concern">{{ $review->vendor_reply }}</textarea>
            <button class="btn btn-sm btn-primary">{{ $review->vendor_reply ? 'Update reply' : 'Post reply' }}</button>
        </form>
    </div></div>
@empty
    <div class="card card-body text-center text-muted py-5">No reviews here yet. They appear as customers review your products.</div>
@endforelse
<div class="mt-3">{{ $reviews->links() }}</div>
@endsection
