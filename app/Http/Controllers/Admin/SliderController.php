<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use App\Rules\LinkOrPath;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

/** Image sliders (carousels) for the storefront. */
class SliderController extends Controller
{
    public function index()
    {
        return view('admin.sliders.index', ['sliders' => Slider::orderBy('sort_order')->orderBy('id')->paginate(20)]);
    }

    public function create()
    {
        return view('admin.sliders.form', ['slider' => new Slider(['is_active' => true, 'location' => 'home', 'sort_order' => 0])]);
    }

    public function store(Request $request)
    {
        Slider::create($this->data($request));

        return redirect()->route('admin.sliders.index')->with('success', 'Slider created successfully.');
    }

    public function edit(Slider $slider)
    {
        return view('admin.sliders.form', compact('slider'));
    }

    public function update(Request $request, Slider $slider)
    {
        $slider->update($this->data($request));

        return redirect()->route('admin.sliders.index')->with('success', 'Slider updated successfully.');
    }

    public function destroy(Slider $slider)
    {
        $slider->delete();

        return redirect()->route('admin.sliders.index')->with('success', 'Slider deleted successfully.');
    }

    /** Rows left completely empty are dropped; a row with anything in it needs an image. */
    private function data(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'location' => ['required', Rule::in(array_keys(Slider::LOCATIONS))],
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'slides' => 'nullable|array|max:' . Slider::MAX_SLIDES,
            'slides.*.image' => ['nullable', 'string', 'max:500', new LinkOrPath],
            'slides.*.upload' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'slides.*.title' => 'nullable|string|max:120',
            'slides.*.text' => 'nullable|string|max:255',
            'slides.*.button' => 'nullable|string|max:40',
            'slides.*.link' => ['nullable', 'string', 'max:500', new LinkOrPath],
        ], [], [
            'slides.*.image' => 'slide image', 'slides.*.link' => 'slide link', 'slides.*.title' => 'slide heading',
            'slides.*.text' => 'slide text', 'slides.*.button' => 'slide button label',
        ]);

        $slides = [];
        foreach ($data['slides'] ?? [] as $i => $slide) {
            $image = $slide['image'] ?? null;
            if ($request->hasFile('slides.' . $i . '.upload')) {
                $image = $request->file('slides.' . $i . '.upload')->store('sliders', 'public');
            }
            $hasContent = filled($image) || filled($slide['title'] ?? null) || filled($slide['text'] ?? null) || filled($slide['button'] ?? null) || filled($slide['link'] ?? null);
            if ($hasContent && ! filled($image)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['slides.' . $i . '.image' => __('Each slide with content needs an image URL/path or an uploaded image.')]);
            }
            if (filled($image)) {
                $slides[] = [
                    'image' => $image, 'title' => $slide['title'] ?? null, 'text' => $slide['text'] ?? null,
                    'button' => $slide['button'] ?? null, 'link' => $slide['link'] ?? null,
                ];
            }
        }

        return [
            'name' => $data['name'], 'location' => $data['location'], 'slides' => $slides,
            'sort_order' => $data['sort_order'] ?? 0, 'is_active' => $request->boolean('is_active'),
        ];
    }
}
