<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all();
        return view('admin.settings.index', compact('settings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'key' => 'required|string|max:255|unique:settings',
            'value' => 'nullable',
            'type' => 'required|in:string,boolean,integer,json',
        ]);

        $value = $request->value;
        if ($request->type === 'boolean') {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        } elseif ($request->type === 'integer') {
            $value = (int) $value;
        } elseif ($request->type === 'json') {
            $value = json_decode($value, true);
        }

        Setting::create([
            'key' => $request->key,
            'value' => $value,
            'type' => $request->type,
        ]);

        return redirect()->route('admin.settings.index')
            ->with('success', 'Setting created successfully.');
    }

    public function update(Request $request, Setting $setting)
    {
        $request->validate([
            'key' => 'required|string|max:255|unique:settings,key,' . $setting->id,
            'value' => 'nullable',
            'type' => 'required|in:string,boolean,integer,json',
        ]);

        $value = $request->value;
        if ($request->type === 'boolean') {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        } elseif ($request->type === 'integer') {
            $value = (int) $value;
        } elseif ($request->type === 'json') {
            $value = json_decode($value, true);
        }

        $setting->update([
            'key' => $request->key,
            'value' => $value,
            'type' => $request->type,
        ]);

        return redirect()->route('admin.settings.index')
            ->with('success', 'Setting updated successfully.');
    }

    public function destroy(Setting $setting)
    {
        $setting->delete();
        return redirect()->route('admin.settings.index')
            ->with('success', 'Setting deleted successfully.');
    }
}