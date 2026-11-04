<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Services\RefundService;
use Illuminate\Http\Request;

/** Customer return requests: approving one issues the credit note(s) and the refund, exactly like a manual return. */
class ReturnRequestController extends Controller
{
    public function index(Request $request)
    {
        $tab = array_key_exists($request->query('status'), ReturnRequest::STATUSES) ? $request->query('status') : 'pending';
        $requests = ReturnRequest::with(['order.items', 'user:id,name,email'])->where('status', $tab)->latest('id')->paginate(20)->withQueryString();
        $counts = ReturnRequest::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        return view('admin.return-requests.index', compact('requests', 'tab', 'counts') + ['statuses' => ReturnRequest::STATUSES]);
    }

    public function approve(Request $request, ReturnRequest $returnRequest, RefundService $refunds)
    {
        abort_unless($returnRequest->status === 'pending', 422);
        $data = $request->validate(['restock' => 'nullable|boolean', 'admin_note' => 'nullable|string|max:1000']);
        $order = $returnRequest->order;

        $notes = $order->returnItems((array) $returnRequest->items, false, $request->boolean('restock', true), 'Return request #' . $returnRequest->id . ': ' . $returnRequest->reasonLabel());
        if (! $notes || $notes->isEmpty()) {
            return back()->with('error', 'This return could not be recorded: the order may already be fully credited, or the quantities are more than what is left.');
        }

        $total = round((float) $notes->sum('total'), 2);
        $refunds->refundOrder($order, $total, 'Return ' . $notes->pluck('number')->implode(', '));
        $returnRequest->update([
            'status' => 'approved',
            'admin_note' => $data['admin_note'] ?? null,
            'credit_notes' => $notes->pluck('number')->implode(', '),
            'resolved_at' => now(),
            'resolved_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Approved. Credit note ' . $returnRequest->credit_notes . ' issued for ₹' . number_format($total, 2) . ' and the refund started.');
    }

    public function reject(Request $request, ReturnRequest $returnRequest)
    {
        abort_unless($returnRequest->status === 'pending', 422);
        $data = $request->validate(['admin_note' => 'required|string|max:1000']);
        $returnRequest->update(['status' => 'rejected', 'admin_note' => $data['admin_note'], 'resolved_at' => now(), 'resolved_by' => $request->user()->id]);

        return back()->with('success', 'Return request declined. The customer sees your note.');
    }
}
