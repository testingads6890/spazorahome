<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\ExportEnquiry;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingZone;
use App\Support\Countries;
use Illuminate\Http\Request;

/** Selling outside India: what is set up, which products may be exported, and the international orders. */
class InternationalController extends Controller
{
    public function index()
    {
        $zones = ShippingZone::where('is_active', true)->get()
            ->filter(fn (ShippingZone $z) => collect($z->countries ?? [])->contains(fn ($c) => ! Countries::isIndia($c)));

        return view('admin.international.index', [
            // Everything that has to be in place before a customer abroad can place an order.
            'checks' => [
                ['International selling switched on', (bool) config('shop.export.enabled'), 'Set SHOP_EXPORT_ENABLED=true in .env.'],
                ['Letter of Undertaking (LUT) number', filled(config('shop.export.lut_number')), 'Set SHOP_LUT_NUMBER in .env. Export invoices are raised without GST under your LUT.'],
                ['Importer Exporter Code (IEC)', filled(config('shop.export.iec')), 'Set SHOP_IEC in .env. It is printed on export invoices.'],
                ['Razorpay keys', filled(config('services.razorpay.key')) && filled(config('services.razorpay.secret')), 'International orders are paid online only. International payments must also be enabled on the Razorpay account.'],
                ['Shipping zone for at least one other country', $zones->isNotEmpty(), 'Add a zone under Shipping zones with the country names you deliver to and its rate. Orders are refused for countries without a zone.'],
                ['Products available for export', Product::where('export_available', true)->exists(), 'Tick "Available for export" on a product, or use the buttons below.'],
            ],
            'zones' => $zones,
            'currencies' => Currency::where('is_active', true)->where('code', '!=', 'INR')->orderBy('sort_order')->get(),
            'chargeForeign' => (bool) config('shop.export.charge_foreign_currency'),
            'exportable' => Product::where('export_available', true)->count(),
            // Freight priced per kg needs a weight on the product, or that product ships for the base rate only.
            'perKg' => $zones->contains(fn (ShippingZone $z) => (float) $z->rate_per_kg > 0),
            'noWeight' => Product::where('export_available', true)->where(fn ($q) => $q->whereNull('export_weight_kg')->orWhere('export_weight_kg', 0))->count(),
            'enquiries' => ExportEnquiry::with('product')->latest()->paginate(15, ['*'], 'enquiries'),
            'newEnquiries' => ExportEnquiry::where('status', 'new')->count(),
            'products' => Product::count(),
            'orders' => Order::where('is_export', true)->with(['user', 'shippingAddress'])->latest()->paginate(20),
            'totals' => [
                'orders' => Order::where('is_export', true)->where('status', '!=', 'cancelled')->count(),
                'value' => (float) Order::where('is_export', true)->where('status', '!=', 'cancelled')->sum('total'),
            ],
        ]);
    }

    /** Record that an export enquiry was answered or closed, with a note for the team. */
    public function enquiry(Request $request, ExportEnquiry $exportEnquiry)
    {
        $exportEnquiry->update($request->validate([
            'status' => ['required', \Illuminate\Validation\Rule::in(array_keys(ExportEnquiry::STATUSES))],
            'admin_notes' => 'nullable|string|max:2000',
        ]));

        return back()->with('success', 'Enquiry updated.');
    }

    /** Mark every active product as available for export, or none. Single products are set on their own form. */
    public function products(Request $request)
    {
        $data = $request->validate(['available' => 'required|boolean']);
        $count = $data['available']
            ? Product::where('is_active', true)->update(['export_available' => true])
            : Product::query()->update(['export_available' => false]);

        return back()->with('success', $data['available'] ? "{$count} active products are now available for export." : 'No product is available for export now.');
    }
}
