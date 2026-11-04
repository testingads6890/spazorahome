<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Http\Requests\Customer\AddressRequest;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index()
    {
        $addresses = auth()->user()->addresses()->latest()->paginate(20);
        return view('customer.addresses.index', compact('addresses'));
    }

    public function create()
    {
        return view('customer.addresses.create');
    }

    public function store(AddressRequest $request)
    {
        $address = auth()->user()->addresses()->create($request->validated());
        if ($request->is_default) {
            auth()->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }
        return redirect()->route('customer.addresses.index')->with('success', __('Address created successfully.'));
    }

    public function edit(Address $address)
    {
        $this->authorizeOwner($address);
        return view('customer.addresses.edit', compact('address'));
    }

    public function update(AddressRequest $request, Address $address)
    {
        $this->authorizeOwner($address);
        $address->update($request->validated());
        if ($request->is_default) {
            auth()->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }
        return redirect()->route('customer.addresses.index')->with('success', __('Address updated successfully.'));
    }

    public function destroy(Address $address)
    {
        $this->authorizeOwner($address);
        $address->delete();
        return redirect()->route('customer.addresses.index')->with('success', __('Address deleted successfully.'));
    }

    private function authorizeOwner(Address $address): void
    {
        abort_unless($address->user_id === auth()->id(), 403);
    }
}
