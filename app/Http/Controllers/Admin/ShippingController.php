<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingZone;
use App\Models\ShippingMethod;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    /** Turn "India, Nepal" style inputs into clean arrays. */
    private function zoneData(Request $request): array
    {
        $data = $request->only(['name', 'rate', 'rate_per_kg', 'free_shipping_threshold']);
        $data['is_active'] = $request->boolean('is_active');
        foreach (['countries', 'states', 'cities'] as $field) {
            $data[$field] = array_values(array_filter(array_map('trim', explode(',', (string) $request->input($field)))));
        }

        return $data;
    }

    // Shipping Zones
    public function zonesIndex()
    {
        $zones = ShippingZone::paginate(20);
        return view('admin.shipping.zones.index', compact('zones'));
    }

    public function zonesCreate()
    {
        return view('admin.shipping.zones.create');
    }

    public function zonesStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'countries' => 'nullable|string|max:1000',
            'states' => 'nullable|string|max:2000',
            'cities' => 'nullable|string|max:2000',
            'rate' => 'required|numeric|min:0',
            'rate_per_kg' => 'nullable|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        ShippingZone::create($this->zoneData($request));

        return redirect()->route('admin.shipping.zones.index')
            ->with('success', 'Shipping zone created successfully.');
    }

    public function zonesEdit(ShippingZone $shippingZone)
    {
        return view('admin.shipping.zones.edit', compact('shippingZone'));
    }

    public function zonesUpdate(Request $request, ShippingZone $shippingZone)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'countries' => 'nullable|string|max:1000',
            'states' => 'nullable|string|max:2000',
            'cities' => 'nullable|string|max:2000',
            'rate' => 'required|numeric|min:0',
            'rate_per_kg' => 'nullable|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $shippingZone->update($this->zoneData($request));

        return redirect()->route('admin.shipping.zones.index')
            ->with('success', 'Shipping zone updated successfully.');
    }

    public function zonesDestroy(ShippingZone $shippingZone)
    {
        $shippingZone->delete();
        return redirect()->route('admin.shipping.zones.index')
            ->with('success', 'Shipping zone deleted successfully.');
    }

    // Shipping Methods
    public function methodsIndex()
    {
        $methods = ShippingMethod::orderBy('sort_order')->orderBy('id')->paginate(20);
        return view('admin.shipping.methods.index', compact('methods'));
    }

    public function methodsCreate()
    {
        return view('admin.shipping.methods.create');
    }

    /** Validated shipping method fields; an unticked "Active" box means inactive. */
    private function methodData(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', \Illuminate\Validation\Rule::in(array_keys(ShippingMethod::TYPES))],
            'rate' => 'nullable|numeric|min:0',
            'min_order' => 'nullable|numeric|min:0',
            'delivery_estimate' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $data['rate'] = $data['type'] === 'flat_rate' ? ($data['rate'] ?? 0) : 0;
        $data['min_order'] = $data['type'] === 'free_shipping' ? ($data['min_order'] ?? null) : null;
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    public function methodsStore(Request $request)
    {
        ShippingMethod::create($this->methodData($request));

        return redirect()->route('admin.shipping.methods.index')
            ->with('success', 'Shipping method created successfully.');
    }

    public function methodsEdit(ShippingMethod $shippingMethod)
    {
        return view('admin.shipping.methods.edit', compact('shippingMethod'));
    }

    public function methodsUpdate(Request $request, ShippingMethod $shippingMethod)
    {
        $shippingMethod->update($this->methodData($request));

        return redirect()->route('admin.shipping.methods.index')
            ->with('success', 'Shipping method updated successfully.');
    }

    public function methodsDestroy(ShippingMethod $shippingMethod)
    {
        $shippingMethod->delete();
        return redirect()->route('admin.shipping.methods.index')
            ->with('success', 'Shipping method deleted successfully.');
    }
}