@extends('layouts.admin')

@section('title', 'Ticket #' . $ticket->ticket_number)

@php
    $statusBadge = ['open' => 'warning', 'in_progress' => 'info', 'escalated' => 'danger', 'resolved' => 'success', 'closed' => 'secondary'];
    $canWork = $canManage || $isMine;
    $phone = $ticket->user?->profile?->phone;
    $done = in_array($ticket->status, ['resolved', 'closed'], true);
@endphp

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <a href="{{ route('admin.support-tickets.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> All tickets</a>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="badge bg-{{ $statusBadge[$ticket->status] ?? 'secondary' }} fs-6">{{ $statuses[$ticket->status] ?? ucfirst($ticket->status) }}</span>
        @if(! $ticket->assigned_to)
            <form method="POST" action="{{ route('admin.support-tickets.take', $ticket) }}">@csrf<button class="btn btn-success btn-sm"><i class="bi bi-hand-index"></i> Take this ticket</button></form>
        @endif
    </div>
</div>

@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@unless($canWork)<div class="alert alert-info">Take this ticket to reply, log calls or change its status.</div>@endunless

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header fw-semibold">{{ $ticket->subject }}</div>
            <div class="card-body">
                <p class="mb-0" style="white-space:pre-line">{{ $ticket->description }}</p>
                @if($ticket->resolution)
                    <div class="alert alert-success mt-3 mb-0"><strong>Resolution:</strong> {{ $ticket->resolution }}</div>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-semibold">Conversation and notes</div>
            <div class="list-group list-group-flush">
                @forelse($ticket->messages->sortBy('created_at') as $message)
                    <div class="list-group-item {{ $message->is_internal ? 'bg-warning-subtle' : '' }}">
                        <div class="d-flex justify-content-between gap-2">
                            <strong>{{ $message->user?->name ?? 'System' }} @if($message->is_internal)<span class="badge text-bg-warning">Internal</span>@elseif($message->user_id === $ticket->user_id)<span class="badge text-bg-light border">Customer</span>@endif</strong>
                            <small class="text-muted">{{ $message->created_at->format('j M Y, g:i a') }}</small>
                        </div>
                        <p class="mb-0" style="white-space:pre-line">{{ $message->message }}</p>
                    </div>
                @empty
                    <div class="list-group-item text-muted">No messages yet.</div>
                @endforelse
            </div>
            @if($canWork)
                <div class="card-body border-top">
                    <form method="POST" action="{{ route('admin.support-tickets.message', $ticket) }}">
                        @csrf
                        <label for="reply" class="form-label fw-semibold">Reply or note</label>
                        <textarea id="reply" name="message" class="form-control mb-2" rows="3" required maxlength="5000">{{ old('message') }}</textarea>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="is_internal" value="1" id="is_internal"><label class="form-check-label" for="is_internal">Internal note (customer does not see it)</label></div>
                            <button class="btn btn-primary"><i class="bi bi-send"></i> Save</button>
                        </div>
                    </form>
                </div>
            @endif
        </div>

        <div class="card mb-3">
            <div class="card-header fw-semibold d-flex justify-content-between"><span>Call records</span><span class="badge text-bg-light border">{{ $ticket->callRecords->count() }}</span></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>When</th><th>By</th><th>Purpose</th><th>Outcome</th><th>Notes</th><th>Follow-up</th></tr></thead>
                    <tbody>
                    @forelse($ticket->callRecords->sortByDesc('call_datetime') as $call)
                        <tr>
                            <td class="text-nowrap">{{ optional($call->call_datetime)->format('j M, g:i a') }}</td>
                            <td>{{ $call->supportExecutive?->name }}</td>
                            <td>{{ $call->call_purpose }}@if($call->customer_mobile)<div class="small text-muted">{{ $call->customer_mobile }}</div>@endif</td>
                            <td>{{ $callStatuses[$call->call_status] ?? ucfirst(str_replace('_', ' ', (string) $call->call_status)) }}</td>
                            <td class="small">{{ $call->notes }}@if($call->resolution)<div class="text-success">{{ $call->resolution }}</div>@endif @if($call->recording_url)<a href="{{ $call->recording_url }}" target="_blank" rel="noopener">Recording</a>@endif</td>
                            <td>{{ $call->follow_up_required ? optional($call->follow_up_date)->format('j M') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted text-center py-3">No calls logged.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($canWork)
                <div class="card-body border-top">
                    <form method="POST" action="{{ route('admin.support-tickets.calls.store', $ticket) }}" class="row g-2">
                        @csrf
                        <div class="col-md-4"><label class="form-label small" for="call_datetime">Call time</label><input type="datetime-local" id="call_datetime" name="call_datetime" value="{{ old('call_datetime', now()->format('Y-m-d\TH:i')) }}" max="{{ now()->format('Y-m-d\TH:i') }}" class="form-control form-control-sm" required></div>
                        <div class="col-md-4"><label class="form-label small" for="customer_mobile">Number called</label><input type="tel" id="customer_mobile" name="customer_mobile" value="{{ old('customer_mobile', $phone) }}" class="form-control form-control-sm"></div>
                        <div class="col-md-4"><label class="form-label small" for="call_status">Outcome</label>
                            <select id="call_status" name="call_status" class="form-select form-select-sm" required>@foreach($callStatuses as $k => $v)<option value="{{ $k }}" @selected(old('call_status') === $k)>{{ $v }}</option>@endforeach</select></div>
                        <div class="col-12"><label class="form-label small" for="call_purpose">Purpose</label><input id="call_purpose" name="call_purpose" value="{{ old('call_purpose') }}" class="form-control form-control-sm" placeholder="e.g. Confirm delivery slot" required maxlength="255"></div>
                        <div class="col-md-6"><label class="form-label small" for="call_notes">Notes</label><textarea id="call_notes" name="notes" rows="2" class="form-control form-control-sm">{{ old('notes') }}</textarea></div>
                        <div class="col-md-6"><label class="form-label small" for="call_resolution">Resolution (if any)</label><textarea id="call_resolution" name="resolution" rows="2" class="form-control form-control-sm">{{ old('resolution') }}</textarea></div>
                        <div class="col-md-4 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="follow_up_required" value="1" id="follow_up_required" @checked(old('follow_up_required'))><label class="form-check-label small" for="follow_up_required">Follow-up needed</label></div></div>
                        <div class="col-md-4"><label class="form-label small" for="call_follow_up">Follow-up date</label><input type="date" id="call_follow_up" name="follow_up_date" value="{{ old('follow_up_date') }}" min="{{ today()->toDateString() }}" class="form-control form-control-sm"></div>
                        <div class="col-md-4"><label class="form-label small" for="recording_url">Recording link</label><input type="url" id="recording_url" name="recording_url" value="{{ old('recording_url') }}" class="form-control form-control-sm"></div>
                        <div class="col-12"><button class="btn btn-sm btn-primary"><i class="bi bi-telephone-plus"></i> Log call</button></div>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Details</div>
            <div class="card-body small">
                <dl class="row mb-0">
                    <dt class="col-5 fw-normal text-muted">Customer</dt><dd class="col-7">{{ $ticket->user?->name }}<br><span class="text-muted">{{ $ticket->user?->email }}</span>@if($phone)<br><a href="tel:{{ $phone }}">{{ $phone }}</a>@endif</dd>
                    <dt class="col-5 fw-normal text-muted">Category</dt><dd class="col-7">{{ $ticket->category ? ucfirst(str_replace('_', ' ', $ticket->category)) : '—' }}</dd>
                    <dt class="col-5 fw-normal text-muted">Priority</dt><dd class="col-7">{{ ucfirst($ticket->priority ?? '—') }}</dd>
                    <dt class="col-5 fw-normal text-muted">Assigned to</dt><dd class="col-7">{{ $ticket->assignedTo?->name ?? 'Unassigned' }}</dd>
                    <dt class="col-5 fw-normal text-muted">Opened</dt><dd class="col-7">{{ $ticket->created_at->format('j M Y, g:i a') }}</dd>
                    @if($ticket->resolved_at)<dt class="col-5 fw-normal text-muted">Resolved</dt><dd class="col-7">{{ $ticket->resolved_at->format('j M Y, g:i a') }}</dd>@endif
                    <dt class="col-5 fw-normal text-muted">Follow-up</dt><dd class="col-7 {{ $ticket->follow_up_date && $ticket->follow_up_date->lte(today()) && ! $done ? 'text-danger fw-semibold' : '' }}">{{ $ticket->follow_up_date?->format('j M Y') ?? '—' }}</dd>
                </dl>
            </div>
        </div>

        @if($canWork)
            <div class="card mb-3">
                <div class="card-header fw-semibold">Status</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.support-tickets.status', $ticket) }}">
                        @csrf
                        <select name="status" class="form-select mb-2" aria-label="Status" id="ticketStatus">
                            @foreach($statuses as $k => $v)<option value="{{ $k }}" @selected(old('status', $ticket->status) === $k)>{{ $v }}</option>@endforeach
                        </select>
                        <textarea name="resolution" id="resolutionBox" class="form-control mb-2" rows="3" placeholder="How was it resolved? (needed for Resolved)" maxlength="5000">{{ old('resolution', $ticket->resolution) }}</textarea>
                        <button class="btn btn-primary w-100">Update status</button>
                    </form>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header fw-semibold">Follow-up date</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.support-tickets.follow-up', $ticket) }}">
                        @csrf
                        <input type="date" name="follow_up_date" value="{{ old('follow_up_date', $ticket->follow_up_date?->toDateString()) }}" min="{{ today()->toDateString() }}" class="form-control mb-2" aria-label="Follow-up date">
                        <input type="text" name="note" class="form-control mb-2" placeholder="What to follow up on (optional)" maxlength="1000">
                        <button class="btn btn-outline-primary w-100">Save follow-up</button>
                    </form>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header fw-semibold">{{ $canManage ? 'Assign / transfer' : 'Transfer to a colleague' }}</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.support-tickets.assign', $ticket) }}">
                        @csrf
                        <select name="assigned_to" class="form-select mb-2" aria-label="Assign to" required>
                            <option value="">Choose…</option>
                            @foreach($team as $member)<option value="{{ $member->id }}" @selected($ticket->assigned_to == $member->id)>{{ $member->name }}</option>@endforeach
                        </select>
                        <input type="text" name="note" class="form-control mb-2" placeholder="Handover note (optional)" maxlength="1000">
                        <button class="btn btn-outline-secondary w-100">{{ $canManage ? 'Assign' : 'Transfer' }}</button>
                    </form>
                </div>
            </div>

            @if($ticket->status !== 'escalated' && ! $done)
                <div class="card mb-3 border-danger-subtle">
                    <div class="card-header fw-semibold text-danger">Escalate</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.support-tickets.escalate', $ticket) }}">
                            @csrf
                            <textarea name="reason" class="form-control mb-2" rows="2" placeholder="Why does this need a manager?" required maxlength="2000"></textarea>
                            <select name="escalate_to" class="form-select mb-2" aria-label="Escalate to">
                                <option value="">Keep the current owner</option>
                                @foreach($team as $member)<option value="{{ $member->id }}">{{ $member->name }}</option>@endforeach
                            </select>
                            <button class="btn btn-outline-danger w-100"><i class="bi bi-arrow-up-circle"></i> Escalate</button>
                        </form>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
