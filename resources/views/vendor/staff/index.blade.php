@extends('layouts.vendor')

@section('title', 'Staff')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <p class="text-muted mb-0">Give your team their own sign-in and choose which parts of the seller panel each person can use.</p>
    <a href="{{ route('vendor.staff.create') }}" class="btn btn-primary"><i class="bi bi-person-plus"></i> Add staff member</a>
</div>

@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Name</th><th>Role</th><th>Can use</th><th>Added</th><th class="text-end"></th></tr></thead>
            <tbody>
                <tr class="table-light">
                    <td><strong>{{ auth()->user()->name }}</strong><div class="small text-muted">{{ auth()->user()->email }}</div></td>
                    <td><span class="badge text-bg-dark">Owner</span></td>
                    <td class="small">Everything, including staff and business profile</td>
                    <td class="small text-muted">—</td>
                    <td></td>
                </tr>
                @forelse($members as $m)
                    <tr>
                        <td><strong>{{ $m->user?->name ?? 'Removed user' }}</strong><div class="small text-muted">{{ $m->user?->email }}</div></td>
                        <td><span class="badge {{ $m->role === 'manager' ? 'text-bg-primary' : 'text-bg-secondary' }}">{{ ucfirst($m->role) }}</span></td>
                        <td class="small">{{ collect($m->areas())->map(fn ($a) => $areas[$a] ?? $a)->implode(', ') ?: 'Dashboard only' }}</td>
                        <td class="small text-muted">{{ $m->created_at?->format('d M Y') }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('vendor.staff.edit', $m) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form action="{{ route('vendor.staff.destroy', $m) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove {{ e($m->user?->name) }} from your team? They will no longer be able to open your seller panel.')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No staff yet. Add someone to help with orders, products or payouts.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-3"><div class="card-body small text-muted">
    <strong class="text-body">Who can do what</strong>
    <ul class="mb-0 mt-1">
        <li><strong>Manager:</strong> products, orders and payouts.</li>
        <li><strong>Staff:</strong> only the areas you tick, for example just orders for a packing team.</li>
        <li>Only you (the owner) can add or remove staff and change the business profile and bank details.</li>
    </ul>
</div></div>
@endsection
