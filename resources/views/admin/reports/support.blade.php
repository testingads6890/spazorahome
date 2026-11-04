@extends('layouts.admin')

@section('title', 'Support Report')

@section('content')
<h1>Support Report</h1>

@if($byStatus->isNotEmpty())
    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach($byStatus as $status => $count)
            <span class="badge text-bg-light border fs-6 fw-normal">{{ ucfirst(str_replace('_', ' ', $status)) }}: <strong>{{ $count }}</strong></span>
        @endforeach
    </div>
@endif

<table class="table table-bordered">
    <thead>
        <tr><th>Ticket #</th><th>Subject</th><th>User</th><th>Status</th><th>Priority</th><th>Created</th></tr>
    </thead>
    <tbody>
        @forelse($tickets as $ticket)
            <tr>
                <td><a href="{{ route('admin.support-tickets.show', $ticket) }}">{{ $ticket->ticket_number }}</a></td>
                <td>{{ $ticket->subject }}</td>
                <td>{{ $ticket->user?->name ?? '-' }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</td>
                <td>{{ ucfirst($ticket->priority) }}</td>
                <td>{{ $ticket->created_at->format('M d, Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted">No tickets yet.</td></tr>
        @endforelse
    </tbody>
</table>
{{ $tickets->links() }}
@endsection
