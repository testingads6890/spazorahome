<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function index()
    {
        $tickets = SupportTicket::where('user_id', auth()->id())->latest()->paginate(20);
        return view('customer.support-tickets.index', compact('tickets'));
    }

    public function create()
    {
        return view('customer.support-tickets.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|in:order,product,shipping,payment,return,other',
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        $data['user_id'] = auth()->id();
        $data['ticket_number'] = 'TKT-' . strtoupper(\Illuminate\Support\Str::random(10));
        $ticket = SupportTicket::create($data);

        return redirect()->route('customer.support-tickets.show', $ticket)->with('success', __('Ticket created.'));
    }

    public function show(SupportTicket $ticket)
    {
        $this->authorizeOwner($ticket);
        // Internal staff notes must never reach the customer.
        $messages = $ticket->messages()->where('is_internal', false)->with('user:id,name')->oldest()->get();

        return view('customer.support-tickets.show', compact('ticket', 'messages'));
    }

    public function message(Request $request, SupportTicket $ticket)
    {
        $this->authorizeOwner($ticket);
        $request->validate(['message' => 'required|string|max:5000']);
        $ticket->messages()->create([
            'user_id' => auth()->id(),
            'type' => 'chat',
            'message' => $request->message,
            'is_internal' => false,
        ]);
        return back()->with('success', __('Message sent.'));
    }

    private function authorizeOwner(SupportTicket $ticket): void
    {
        abort_unless($ticket->user_id === auth()->id(), 403);
    }
}
