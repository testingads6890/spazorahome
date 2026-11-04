<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorOrder;
use Illuminate\Http\Request;

/** Order lines of this seller that came back (a credit note was issued against them). */
class ReturnController extends Controller
{
    public function index(Request $request)
    {
        $vendor = $request->user()->vendorAccount();
        $notes = class_exists(\App\Models\CreditNoteItem::class)
            ? \App\Models\CreditNoteItem::whereIn('order_item_id', VendorOrder::where('vendor_id', $vendor->id)->select('order_item_id'))->latest('id')->get()
            : collect();

        $lines = VendorOrder::where('vendor_id', $vendor->id)->whereIn('order_item_id', $notes->pluck('order_item_id'))
            ->with(['order:id,order_number,user_id,created_at', 'order.user:id,name', 'orderItem:id,product_name,quantity,unit_price'])
            ->latest('id')->paginate(20);
        $byItem = $notes->groupBy('order_item_id');

        return view('vendor.returns.index', compact('lines', 'byItem'));
    }
}
