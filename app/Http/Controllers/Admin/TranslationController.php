<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Translation;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    public function index(Request $request, $language = null)
    {
        $languages = Language::where('is_active', true)->get();
        $selectedLanguage = $language ? Language::where('code', $language)->first() : $languages->first();

        if (!$selectedLanguage) {
            // Redirecting back to this same page would loop forever, so send the admin to add a language first.
            return redirect()->route('admin.languages.index')
                ->with('error', 'Add an active language before managing translations.');
        }

        $search = $request->get('search');
        $group = $request->get('group');

        $query = Translation::with('language')
            ->where('language_id', $selectedLanguage->id);

        if ($search) {
            $query->where('key', 'like', "%{$search}%")
                ->orWhere('value', 'like', "%{$search}%");
        }

        if ($group) {
            $query->where('group', $group);
        }

        $translations = $query->paginate(50);
        $groups = Translation::where('language_id', $selectedLanguage->id)
            ->distinct()
            ->pluck('group');

        return view('admin.translations.index', compact('translations', 'languages', 'selectedLanguage', 'groups', 'search', 'group'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'language_id' => 'required|exists:languages,id',
            'group' => 'nullable|string|max:255',
            'key' => 'required|string|max:255',
            'value' => 'required|string',
        ]);

        Translation::updateOrCreate(
            [
                'language_id' => $request->language_id,
                'group' => $request->group,
                'key' => $request->key,
            ],
            ['value' => $request->value]
        );

        return back()->with('success', 'Translation saved successfully.');
    }

    public function update(Request $request, Translation $translation)
    {
        $request->validate([
            'value' => 'required|string',
        ]);

        $translation->update(['value' => $request->value]);

        return back()->with('success', 'Translation updated successfully.');
    }

    public function destroy(Translation $translation)
    {
        $translation->delete();
        return back()->with('success', 'Translation deleted successfully.');
    }

    public function bulkImport(Request $request)
    {
        $request->validate([
            'language_id' => 'required|exists:languages,id',
            'translations' => 'required|array',
            'translations.*.key' => 'required|string',
            'translations.*.value' => 'required|string',
            'group' => 'nullable|string',
        ]);

        foreach ($request->translations as $translation) {
            Translation::updateOrCreate(
                [
                    'language_id' => $request->language_id,
                    'group' => $request->group,
                    'key' => $translation['key'],
                ],
                ['value' => $translation['value']]
            );
        }

        return back()->with('success', 'Translations imported successfully.');
    }
}