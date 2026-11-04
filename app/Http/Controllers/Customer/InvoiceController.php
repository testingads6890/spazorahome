<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

/** Every tax invoice and credit note of the customer's orders in one place. */
class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->whereNotNull('invoice_number')
            ->with(['supplierInvoices.vendor:id,business_name', 'creditNotes'])
            ->latest('invoice_date')->latest('id')
            ->paginate(15);

        return view('customer.invoices.index', compact('orders'));
    }
}
