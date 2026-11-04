@extends('layouts.account')

@section('title', __('Support - SpazoraHome'))
@section('heading', __('Support'))
@section('crumb', __('Support'))

@section('account')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-muted mb-0">{{ __('Need help with an order or product? Open a ticket.') }}</p>
        <a href="{{ route('customer.support-tickets.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> {{ __('New ticket') }}</a>
    </div>

    @forelse($tickets as $ticket)
        <a href="{{ route('customer.support-tickets.show', $ticket) }}" class="order-card mb-3">
            <div class="d-flex flex-wrap justify-content-between gap-2">
                <div>
                    <strong class="text-dark">{{ $ticket->subject }}</strong>
                    <div class="small text-muted">{{ $ticket->ticket_number }} &middot; {{ __(ucfirst($ticket->category)) }} &middot; {{ $ticket->created_at->translatedFormat('d M Y') }}</div>
                </div>
                <div class="text-end">
                    <span class="status-badge {{ in_array($ticket->status, ['resolved', 'closed'], true) ? 'delivered' : ($ticket->status === 'open' ? 'pending' : 'processing') }}">@status($ticket->status)</span>
                    <div class="small text-muted mt-1">{{ __(':priority priority', ['priority' => __(ucfirst($ticket->priority))]) }}</div>
                </div>
            </div>
        </a>
    @empty
        <div class="empty"><i class="bi bi-life-preserver"></i><h5 class="mt-3">{{ __('No tickets') }}</h5><p>{{ __('We are here to help. Open a ticket any time.') }}</p><a href="{{ route('customer.support-tickets.create') }}" class="btn btn-primary">{{ __('New ticket') }}</a></div>
    @endforelse
    <div class="d-flex justify-content-center">{{ $tickets->links() }}</div>
@endsection
