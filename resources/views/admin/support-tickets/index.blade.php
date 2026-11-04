@extends('layouts.admin')

@section('title', 'Support tickets')

@php
    $tabLabels = ['mine' => 'My tickets', 'unassigned' => 'Unassigned', 'open' => 'Open', 'escalated' => 'Escalated', 'follow_up' => 'Follow-ups due', 'resolved' => 'Resolved', 'all' => 'All'];
    $statusBadge = ['open' => 'warning', 'in_progress' => 'info', 'escalated' => 'danger', 'resolved' => 'success', 'closed' => 'secondary'];
    $prioBadge = ['urgent' => 'danger', 'high' => 'danger', 'medium' => 'warning', 'normal' => 'secondary', 'low' => 'light'];
@endphp

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <ul class="nav nav-pills flex-wrap">
        @foreach($tabLabels as $key => $label)
            <li class="nav-item"><a class="nav-link {{ $tab === $key ? 'active' : '' }}" href="{{ route('admin.support-tickets.index', ['tab' => $key]) }}">{{ $label }} <span class="badge {{ $key === 'follow_up' && $counts[$key] ? 'text-bg-danger' : 'text-bg-light border' }}">{{ $counts[$key] }}</span></a></li>
        @endforeach
    </ul>
    <form class="d-flex flex-wrap gap-2" method="GET">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="width:220px" placeholder="Ticket #, subject or customer" aria-label="Search tickets">
        @if($all && $team->isNotEmpty())
            <select name="assigned_to" class="form-select form-select-sm" style="width:auto" aria-label="Assigned to">
                <option value="">Anyone</option>
                @foreach($team as $member)<option value="{{ $member->id }}" @selected(request('assigned_to') == $member->id)>{{ $member->name }}</option>@endforeach
            </select>
        @endif
        <button class="btn btn-sm btn-outline-primary">Filter</button>
    </form>
</div>

@unless($all)
    <p class="small text-muted">You see the tickets assigned to you and the unassigned queue. Take a ticket from <em>Unassigned</em> to start working on it.</p>
@endunless

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Ticket</th><th>Customer</th><th>Status</th><th>Priority</th><th>Assigned to</th><th>Follow-up</th><th>Updated</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse($tickets as $ticket)
                @php $due = $ticket->follow_up_date && \Illuminate\Support\Carbon::parse($ticket->follow_up_date)->lte(today()) && ! in_array($ticket->status, ['resolved', 'closed'], true); @endphp
                <tr>
                    <td><strong>#{{ $ticket->ticket_number }}</strong><div class="small text-truncate" style="max-width:280px">{{ $ticket->subject }}</div>@if($ticket->category)<div class="small text-muted">{{ ucfirst(str_replace('_', ' ', $ticket->category)) }}</div>@endif</td>
                    <td>{{ $ticket->user?->name }}<div class="small text-muted">{{ $ticket->user?->email }}</div></td>
                    <td><span class="badge bg-{{ $statusBadge[$ticket->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span></td>
                    <td><span class="badge text-bg-{{ $prioBadge[$ticket->priority] ?? 'light' }} border">{{ ucfirst($ticket->priority ?? '—') }}</span></td>
                    <td>{{ $ticket->assignedTo?->name ?? 'Unassigned' }}</td>
                    <td class="{{ $due ? 'text-danger fw-semibold' : '' }}">{{ $ticket->follow_up_date ? \Illuminate\Support\Carbon::parse($ticket->follow_up_date)->format('j M') : '—' }}</td>
                    <td class="small text-muted">{{ $ticket->updated_at->diffForHumans() }}</td>
                    <td class="text-end text-nowrap">
                        @if(! $ticket->assigned_to && ! in_array($ticket->status, ['resolved', 'closed'], true))
                            <form method="POST" action="{{ route('admin.support-tickets.take', $ticket) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success">Take</button></form>
                        @endif
                        <a href="{{ route('admin.support-tickets.show', $ticket) }}" class="btn btn-sm btn-primary">Open</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No tickets here.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $tickets->links() }}</div>
@endsection
