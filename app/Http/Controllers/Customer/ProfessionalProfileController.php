<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ProfessionalProfile;
use Illuminate\Http\Request;

class ProfessionalProfileController extends Controller
{
    public function edit()
    {
        $profile = auth()->user()->professionalProfile;
        return view('customer.professional-profile.edit', compact('profile'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:255',
            'designation' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'license_number' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
            'specialization' => 'nullable|string|max:255',
            'experience_years' => 'nullable|integer|min:0',
        ]);

        $data['user_id'] = auth()->id();
        $data['is_verified'] = false;
        ProfessionalProfile::updateOrCreate(['user_id' => auth()->id()], $data);

        return back()->with('success', __('Professional profile saved. Verification pending.'));
    }
}
