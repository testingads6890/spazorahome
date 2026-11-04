<?php

namespace App\Http\Controllers;

use App\Support\DeliveryArea;
use Illuminate\Http\Request;

class DeliveryAreaController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate(['pincode' => ['required', 'digits:6']], ['pincode.digits' => __('Enter a 6-digit PIN code.')]);

        if (DeliveryArea::set($data['pincode'])) {
            return back()->with('success', __('shop.messages.delivery_pin_saved'));
        }

        DeliveryArea::clear();

        return back()->with('error', __('shop.messages.delivery_pin_unknown'));
    }

    public function clear()
    {
        DeliveryArea::clear();

        return back()->with('success', __('shop.messages.delivery_pin_cleared'));
    }
}
