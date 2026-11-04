<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\AdminArea;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Support desk for admins, support managers and support executives.
 * Executives work their own tickets and pick up unassigned ones; managers see and route everything.
 */
class SupportTicketController extends Controller
{
    public const STATUSES = ['open' => 'Open', 'in_progress' => 'In progress', 'escalated' => 'Escalated', 'resolved' => 'Resolved', 'closed' => 'Closed'];

    public const CALL_STATUSES = ['connected' => 'Connected', 'not_answered' => 'Not answered', 'busy' => 'Busy', 'callback_requested' => 'Callback requested', 'wrong_number' => 'Wrong number'];

    public function index(Request $request)
    {
        $user = $request->user();
        $all = AdminArea::seesAllTickets($user);
        $tab = $request->query('tab', $all ? 'open' : 'mine');

        $base = SupportTicket::query()->when(! $all, fn ($q) => $q->where(fn ($w) => $w->where('assigned_to', $user->id)->orWhereNull('assigned_to')));
        $tabs = [
            'mine' => fn ($q) => $q->where('assigned_to', $user->id)->whereNotIn('status', ['resolved', 'closed']),
            'unassigned' => fn ($q) => $q->whereNull('assigned_to')->whereNotIn('status', ['resolved', 'closed']),
            'open' => fn ($q) => $q->whereIn('status', ['open', 'in_progress']),
            'escalated' => fn ($q) => $q->where('status', 'escalated'),
            'follow_up' => fn ($q) => $q->whereNotNull('follow_up_date')->whereDate('follow_up_date', '<=', today())->whereNotIn('status', ['resolved', 'closed']),
            'resolved' => fn ($q) => $q->whereIn('status', ['resolved', 'closed']),
            'all' => fn ($q) => $q,
        ];
        $tab = array_key_exists($tab, $tabs) ? $tab : 'all';
        $counts = collect($tabs)->map(fn ($scope) => $scope((clone $base))->count());

        $tickets = $tabs[$tab](clone $base)
            ->with('user:id,name,email', 'assignedTo:id,name')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->q . '%';
                $q->where(fn ($w) => $w->where('ticket_number', 'like', $term)->orWhere('subject', 'like', $term)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term)));
            })
            ->when($all && $request->filled('assigned_to'), fn ($q) => $q->where('assigned_to', $request->assigned_to))
            ->orderByRaw("CASE status WHEN 'escalated' THEN 1 WHEN 'open' THEN 2 WHEN 'in_progress' THEN 3 WHEN 'resolved' THEN 4 ELSE 5 END")
            ->latest()
            ->paginate(20)->withQueryString();

        $team = $all ? $this->team() : collect();

        return view('admin.support-tickets.index', compact('tickets', 'tab', 'counts', 'all', 'team'));
    }

    public function show(Request $request, SupportTicket $ticket)
    {
        $this->authorizeTicket($request, $ticket);
        $ticket->load('user.profile', 'assignedTo', 'messages.user', 'callRecords.supportExecutive');
        $team = $this->team();
        $canManage = AdminArea::seesAllTickets($request->user());
        $isMine = (int) $ticket->assigned_to === (int) $request->user()->id;

        return view('admin.support-tickets.show', compact('ticket', 'team', 'canManage', 'isMine') + [
            'statuses' => self::STATUSES,
            'callStatuses' => self::CALL_STATUSES,
            // kept for older views that expect this name
            'supportExecutives' => $team,
        ]);
    }

    /** Assign or transfer. Managers can route any ticket; an executive can hand their own ticket to a colleague. */
    public function assign(Request $request, SupportTicket $ticket)
    {
        $this->authorizeTicket($request, $ticket);
        $data = $request->validate([
            'assigned_to' => ['required', Rule::in($this->team()->pluck('id')->all())],
            'note' => 'nullable|string|max:1000',
        ]);
        $manager = AdminArea::seesAllTickets($request->user());
        abort_unless($manager || (int) $ticket->assigned_to === (int) $request->user()->id, 403, 'Only the owner of this ticket or a support manager can transfer it.');

        $to = User::find($data['assigned_to']);
        $ticket->update(['assigned_to' => $to->id, 'status' => $ticket->status === 'open' ? 'in_progress' : $ticket->status]);
        $this->note($ticket, $request, 'Ticket ' . ($manager ? 'assigned' : 'transferred') . " to {$to->name}." . (filled($data['note'] ?? null) ? ' Note: ' . $data['note'] : ''));

        return back()->with('success', "Ticket assigned to {$to->name}.");
    }

    /** An executive picks an unassigned ticket from the queue. */
    public function take(Request $request, SupportTicket $ticket)
    {
        $this->authorizeTicket($request, $ticket);
        abort_if($ticket->assigned_to && (int) $ticket->assigned_to !== (int) $request->user()->id && ! AdminArea::seesAllTickets($request->user()), 403);

        $ticket->update(['assigned_to' => $request->user()->id, 'status' => $ticket->status === 'open' ? 'in_progress' : $ticket->status]);
        $this->note($ticket, $request, $request->user()->name . ' took this ticket.');

        return back()->with('success', 'The ticket is now assigned to you.');
    }

    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $this->authorizeWork($request, $ticket);
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(self::STATUSES))],
            'resolution' => 'nullable|required_if:status,resolved|string|max:5000',
        ], ['resolution.required_if' => 'Write how the issue was resolved.']);

        $update = ['status' => $data['status']];
        if ($data['status'] === 'resolved') {
            $update['resolution'] = $data['resolution'];
            $update['resolved_at'] = now();
        }
        if ($data['status'] === 'closed') {
            $update['closed_at'] = now();
            $update['resolved_at'] = $ticket->resolved_at ?? now();
        }
        if (in_array($data['status'], ['open', 'in_progress', 'escalated'], true)) {
            $update['closed_at'] = null;
        }
        $ticket->update($update);
        $this->note($ticket, $request, 'Status changed to ' . self::STATUSES[$data['status']] . '.' . ($data['status'] === 'resolved' ? ' Resolution: ' . $data['resolution'] : ''));

        return back()->with('success', 'Status updated.');
    }

    /** Raise the ticket to a manager (or another teammate) with the reason recorded as an internal note. */
    public function escalate(Request $request, SupportTicket $ticket)
    {
        $this->authorizeWork($request, $ticket);
        $data = $request->validate([
            'reason' => 'required|string|max:2000',
            'escalate_to' => ['nullable', Rule::in($this->team()->pluck('id')->all())],
        ]);

        $to = isset($data['escalate_to']) ? User::find($data['escalate_to']) : null;
        $ticket->update(array_filter([
            'status' => 'escalated',
            'priority' => in_array($ticket->priority, ['low', 'medium', 'normal'], true) ? 'high' : null,
            'assigned_to' => $to?->id,
        ]));
        $this->note($ticket, $request, 'Escalated' . ($to ? " to {$to->name}" : '') . '. Reason: ' . $data['reason']);

        return back()->with('success', 'Ticket escalated.');
    }

    public function followUp(Request $request, SupportTicket $ticket)
    {
        $this->authorizeWork($request, $ticket);
        $data = $request->validate([
            'follow_up_date' => 'nullable|date|after_or_equal:today',
            'note' => 'nullable|string|max:1000',
        ]);

        $ticket->update(['follow_up_date' => $data['follow_up_date'] ?? null]);
        $this->note($ticket, $request, ($data['follow_up_date'] ?? null)
            ? 'Follow-up set for ' . \Illuminate\Support\Carbon::parse($data['follow_up_date'])->format('j M Y') . '.' . (filled($data['note'] ?? null) ? ' ' . $data['note'] : '')
            : 'Follow-up cleared.');

        return back()->with('success', 'Follow-up saved.');
    }

    /** Reply to the customer, or an internal note only staff can see. */
    public function message(Request $request, SupportTicket $ticket)
    {
        $this->authorizeWork($request, $ticket);
        $request->validate(['message' => 'required|string|max:5000', 'is_internal' => 'nullable|boolean']);
        $internal = $request->boolean('is_internal');

        $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'type' => $internal ? 'note' : 'chat',
            'message' => $request->message,
            'is_internal' => $internal,
        ]);
        if (! $internal && $ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        }

        return back()->with('success', $internal ? 'Internal note added.' : 'Reply sent to the customer.');
    }

    /** Log a phone call made or taken for this ticket. */
    public function storeCall(Request $request, SupportTicket $ticket)
    {
        $this->authorizeWork($request, $ticket);
        $data = $request->validate([
            'call_datetime' => 'required|date|before_or_equal:now',
            'customer_mobile' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{6,20}$/'],
            'call_purpose' => 'required|string|max:255',
            'call_status' => ['required', Rule::in(array_keys(self::CALL_STATUSES))],
            'notes' => 'nullable|string|max:5000',
            'resolution' => 'nullable|string|max:2000',
            'follow_up_required' => 'nullable|boolean',
            'follow_up_date' => 'nullable|required_if:follow_up_required,1|date|after_or_equal:today',
            'recording_url' => 'nullable|url|max:500',
        ], ['follow_up_date.required_if' => 'Choose the follow-up date.', 'customer_mobile.regex' => 'Enter a valid phone number.']);

        $ticket->callRecords()->create($data + [
            'user_id' => $ticket->user_id,
            'support_executive_id' => $request->user()->id,
            'customer_name' => $ticket->user?->name,
            'follow_up_required' => $request->boolean('follow_up_required'),
        ]);
        if ($request->boolean('follow_up_required') && ! empty($data['follow_up_date'])) {
            $ticket->update(['follow_up_date' => $data['follow_up_date']]);
        }

        return back()->with('success', 'Call logged.');
    }

    /** Admins, support managers and support executives who can own tickets. */
    private function team()
    {
        return User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'support_manager', 'support_executive']))->orderBy('name')->get(['id', 'name', 'email']);
    }

    /** Executives may only open their own tickets and the unassigned queue. */
    private function authorizeTicket(Request $request, SupportTicket $ticket): void
    {
        $user = $request->user();
        abort_unless(AdminArea::seesAllTickets($user) || $ticket->assigned_to === null || (int) $ticket->assigned_to === (int) $user->id, 403, 'This ticket is assigned to someone else.');
    }

    /** Changing a ticket needs it to be yours (or a manager). */
    private function authorizeWork(Request $request, SupportTicket $ticket): void
    {
        $user = $request->user();
        abort_unless(AdminArea::seesAllTickets($user) || (int) $ticket->assigned_to === (int) $user->id, 403, 'Take the ticket first, then you can work on it.');
    }

    private function note(SupportTicket $ticket, Request $request, string $text): void
    {
        $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'type' => 'note',
            'message' => $text,
            'is_internal' => true,
        ]);
    }
}
