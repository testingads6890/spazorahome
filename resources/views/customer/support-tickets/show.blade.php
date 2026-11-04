@extends('layouts.account')

@section('title', $ticket->ticket_number . ' - SpazoraHome')
@section('heading', $ticket->subject)
@section('crumb', $ticket->ticket_number)

@php $closed = in_array($ticket->status, ['resolved', 'closed'], true); @endphp

@section('account')
    <div class="card card-body mb-3">
        <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
            <div class="small text-muted">{{ $ticket->ticket_number }} &middot; {{ __(ucfirst($ticket->category)) }} &middot; {{ __(':priority priority', ['priority' => __(ucfirst($ticket->priority))]) }} &middot; {{ __('opened :date', ['date' => $ticket->created_at->translatedFormat('d M Y, h:i A')]) }}</div>
            <span class="status-badge {{ $closed ? 'delivered' : ($ticket->status === 'open' ? 'pending' : 'processing') }}">@status($ticket->status)</span>
        </div>
        <p class="mb-0" style="white-space: pre-line">{{ $ticket->description }}</p>
        @if($ticket->resolution)<div class="alert alert-success mt-3 mb-0"><strong>{{ __('Resolution:') }}</strong> {{ $ticket->resolution }}</div>@endif
    </div>

    <div class="d-grid gap-2 mb-3">
        @foreach($messages as $message)
            @php $mine = $message->user_id === auth()->id(); @endphp
            <div class="card card-body {{ $mine ? '' : 'border-success' }}" style="{{ $mine ? 'margin-left:10%' : 'margin-right:10%;background:var(--brand-100)' }}">
                <div class="d-flex justify-content-between small text-muted mb-1">
                    <strong class="text-dark">{{ $mine ? __('You') : __('Support team') }}</strong>
                    <span>{{ $message->created_at->translatedFormat('d M Y, h:i A') }}</span>
                </div>
                <div style="white-space: pre-line">{{ $message->message }}</div>
            </div>
        @endforeach
    </div>

    @if($closed)
        <div class="alert alert-secondary">{{ __('This ticket is :status. Open a new ticket if you need more help.', ['status' => mb_strtolower(__(ucfirst(str_replace('_', ' ', $ticket->status))))]) }}</div>
    @else
        <form method="POST" action="{{ route('customer.support-tickets.message', $ticket) }}" class="card card-body">
            @csrf
            <label class="form-label fw-semibold" for="message">{{ __('Reply') }}</label>
            <textarea id="message" name="message" rows="3" class="form-control @error('message') is-invalid @enderror" required maxlength="5000"></textarea>
            @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <button class="btn btn-primary mt-3 align-self-start">{{ __('Send reply') }}</button>
        </form>
    @endif
@endsection
