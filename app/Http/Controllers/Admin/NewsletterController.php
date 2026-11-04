<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use Illuminate\Http\Request;

/** Newsletter subscribers collected by the storefront sign-up form. */
class NewsletterController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:100', 'status' => 'nullable|in:active,inactive']);

        $subscribers = $this->filtered($request)->latest('id')->paginate(30)->withQueryString();

        return view('admin.newsletters.index', [
            'subscribers' => $subscribers,
            'activeCount' => Newsletter::where('is_active', true)->count(),
            'totalCount' => Newsletter::count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['email' => 'required|email:rfc|max:255']);

        $subscriber = Newsletter::firstOrCreate(['email' => mb_strtolower($data['email'])], ['is_active' => true, 'subscribed_at' => now()]);

        return back()->with('success', $subscriber->wasRecentlyCreated ? 'Subscriber added.' : 'That address is already on the list.');
    }

    /** Switch a subscriber on or off (off = unsubscribed, kept so they are not added again by mistake). */
    public function update(Newsletter $newsletter)
    {
        $newsletter->update(['is_active' => ! $newsletter->is_active, 'subscribed_at' => $newsletter->is_active ? $newsletter->subscribed_at : now()]);

        return back()->with('success', $newsletter->is_active ? 'Subscriber re-activated.' : 'Subscriber unsubscribed.');
    }

    public function destroy(Newsletter $newsletter)
    {
        $newsletter->delete();

        return back()->with('success', 'Subscriber deleted.');
    }

    /** CSV of the current filter, for importing into a mailing tool. */
    public function export(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:100', 'status' => 'nullable|in:active,inactive']);
        $query = $this->filtered($request)->orderBy('id');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['email', 'status', 'subscribed_at']);
            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $row) {
                    // A leading = + - @ would be run as a formula by spreadsheet programs.
                    $email = preg_match('/^[=+\-@]/', $row->email) ? "'" . $row->email : $row->email;
                    fputcsv($out, [$email, $row->is_active ? 'active' : 'unsubscribed', optional($row->subscribed_at)->toDateTimeString()]);
                }
            });
            fclose($out);
        }, 'newsletter-subscribers-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    private function filtered(Request $request)
    {
        return Newsletter::query()
            ->when($request->filled('q'), fn ($q) => $q->where('email', 'like', '%' . str_replace(['%', '_'], ['\%', '\_'], $request->q) . '%'))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'));
    }
}
