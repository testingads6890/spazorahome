<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfessionalProfile;
use Illuminate\Http\Request;

/** Interior designers and architects who registered as professionals, and their verification. */
class ProfessionalController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'pending');
        $profiles = ProfessionalProfile::with('user:id,name,email,created_at')
            ->when($tab === 'pending', fn ($q) => $q->where('is_verified', false))
            ->when($tab === 'verified', fn ($q) => $q->where('is_verified', true))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->q . '%';
                $q->where(fn ($w) => $w->where('company_name', 'like', $term)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term)));
            })
            ->latest()->paginate(20)->withQueryString();

        $counts = [
            'pending' => ProfessionalProfile::where('is_verified', false)->count(),
            'verified' => ProfessionalProfile::where('is_verified', true)->count(),
        ];
        $counts['all'] = $counts['pending'] + $counts['verified'];

        return view('admin.professionals.index', compact('profiles', 'counts', 'tab'));
    }

    public function verify(ProfessionalProfile $profile)
    {
        $profile->update(['is_verified' => true]);

        return back()->with('success', ($profile->company_name ?: 'Profile') . ' is now a verified professional.');
    }

    public function unverify(ProfessionalProfile $profile)
    {
        $profile->update(['is_verified' => false]);

        return back()->with('success', 'Verification removed.');
    }
}
