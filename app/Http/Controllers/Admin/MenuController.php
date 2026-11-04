<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Rules\LinkOrPath;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Extra links for the storefront header bar and footer. */
class MenuController extends Controller
{
    public function index()
    {
        return view('admin.menus.index', ['menus' => Menu::orderBy('location')->orderBy('name')->paginate(20)]);
    }

    public function create()
    {
        return view('admin.menus.form', ['menu' => new Menu(['is_active' => true, 'location' => 'header'])]);
    }

    public function store(Request $request)
    {
        Menu::create($this->data($request));

        return redirect()->route('admin.menus.index')->with('success', 'Menu created successfully.');
    }

    public function edit(Menu $menu)
    {
        return view('admin.menus.form', compact('menu'));
    }

    public function update(Request $request, Menu $menu)
    {
        $menu->update($this->data($request));

        return redirect()->route('admin.menus.index')->with('success', 'Menu updated successfully.');
    }

    public function destroy(Menu $menu)
    {
        $menu->delete();

        return redirect()->route('admin.menus.index')->with('success', 'Menu deleted successfully.');
    }

    /** The links are typed one per line as "Label | link". */
    private function data(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'location' => ['required', Rule::in(array_keys(Menu::LOCATIONS))],
            'links' => 'nullable|string|max:5000',
            'is_active' => 'boolean',
        ]);

        $items = [];
        foreach (preg_split('/\r\n|\r|\n/', (string) ($data['links'] ?? '')) as $n => $line) {
            if (trim($line) === '') {
                continue;
            }
            $parts = array_map('trim', explode('|', $line, 2));
            if (count($parts) !== 2 || $parts[0] === '' || mb_strlen($parts[0]) > 60 || ! LinkOrPath::passes($parts[1])) {
                throw ValidationException::withMessages(['links' => 'Line ' . ($n + 1) . ' must look like "Label | https://example.com" or "Label | /page" (label up to 60 characters).']);
            }
            $items[] = ['label' => $parts[0], 'url' => $parts[1]];
        }
        if (count($items) > 12) {
            throw ValidationException::withMessages(['links' => 'A menu can hold at most 12 links.']);
        }

        return ['name' => $data['name'], 'location' => $data['location'], 'items' => $items, 'is_active' => $request->boolean('is_active')];
    }
}
