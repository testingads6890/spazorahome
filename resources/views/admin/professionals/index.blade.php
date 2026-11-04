@extends('layouts.admin')

@section('title', 'Professionals')

@section('content')
<p class="text-muted">Interior designers and architects who joined as professionals. Verify a profile once you have checked their studio, licence or portfolio.</p>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <ul class="nav nav-pills">
        @foreach(['pending' => 'Awaiting verification', 'verified' => 'Verified', 'all' => 'All'] as $key => $label)
            <li class="nav-item"><a class="nav-link {{ $tab === $key ? 'active' : '' }}" href="{{ route('admin.professionals.index', ['tab' => $key]) }}">{{ $label }} <span class="badge text-bg-light border">{{ $counts[$key] }}</span></a></li>
        @endforeach
    </ul>
    <form class="d-flex gap-2" method="GET">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Name, email or studio" aria-label="Search professionals">
        <button class="btn btn-sm btn-outline-primary">Search</button>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Professional</th><th>Studio / company</th><th>Role</th><th>Experience</th><th>Licence</th><th>Status</th><th class="text-end">Action</th></tr></thead>
            <tbody>
            @forelse($profiles as $p)
                <tr>
                    <td><strong>{{ $p->user?->name }}</strong><div class="small text-muted">{{ $p->user?->email }}</div><div class="small text-muted">Joined {{ optional($p->user?->created_at)->format('j M Y') }}</div></td>
                    <td>{{ $p->company_name }}@if($p->website)<div class="small"><a href="{{ $p->website }}" target="_blank" rel="noopener nofollow">{{ parse_url($p->website, PHP_URL_HOST) ?: $p->website }}</a></div>@endif</td>
                    <td>{{ $p->designation ?: '—' }}@if($p->specialization)<div class="small text-muted">{{ $p->specialization }}</div>@endif</td>
                    <td>{{ $p->experience_years !== null ? $p->experience_years . ' yrs' : '—' }}</td>
                    <td>{{ $p->license_number ?: '—' }}</td>
                    <td><span class="badge bg-{{ $p->is_verified ? 'success' : 'warning' }}">{{ $p->is_verified ? 'Verified' : 'Pending' }}</span></td>
                    <td class="text-end">
                        @if($p->is_verified)
                            <form method="POST" action="{{ route('admin.professionals.unverify', $p) }}">@csrf<button class="btn btn-sm btn-outline-secondary">Remove verification</button></form>
                        @else
                            <form method="POST" action="{{ route('admin.professionals.verify', $p) }}">@csrf<button class="btn btn-sm btn-success"><i class="bi bi-patch-check"></i> Verify</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No professionals here yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $profiles->links() }}</div>
@endsection
