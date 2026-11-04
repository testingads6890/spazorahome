<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** On-screen reports. Each has a matching CSV under Admin > Exports. */
class ReportController extends Controller
{
    /** Sales per day (cancelled orders left out). Without dates: the last 30 days. */
    public function sales(Request $request)
    {
        $dates = $request->validate(['from' => 'nullable|date', 'to' => 'nullable|date|after_or_equal:from']);
        $from = Carbon::parse($dates['from'] ?? now()->subDays(29))->startOfDay();
        $to = Carbon::parse($dates['to'] ?? now())->endOfDay();

        $sales = Order::where('status', '!=', 'cancelled')->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as date, SUM(total) as total, COUNT(*) as orders')
            ->groupBy('date')->orderBy('date')->get();

        return view('admin.reports.sales', [
            'sales' => $sales,
            'totalSales' => (float) $sales->sum('total'),
            'totalOrders' => (int) $sales->sum('orders'),
            'labels' => $sales->map(fn ($d) => Carbon::parse($d->date)->format('d M'))->all(),
            'data' => $sales->map(fn ($d) => round((float) $d->total, 2))->all(),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    /** Best sellers by units sold, from orders that were not cancelled. */
    public function products()
    {
        $products = OrderItem::whereHas('order', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->selectRaw('product_id, product_name, SUM(quantity) as units, SUM(subtotal) as revenue')
            ->groupBy('product_id', 'product_name')->orderByDesc('units')->limit(20)->get();

        return view('admin.reports.products', [
            'products' => $products,
            'labels' => $products->take(10)->pluck('product_name')->all(),
            'data' => $products->take(10)->map(fn ($p) => (int) $p->units)->all(),
        ]);
    }

    public function customers()
    {
        $customers = User::whereHas('roles', fn ($q) => $q->where('name', 'customer'))->withCount('orders')
            ->withSum(['orders as spent' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'total')
            ->orderByDesc('orders_count')->orderBy('id')->paginate(20);

        return view('admin.reports.customers', [
            'customers' => $customers,
            'totalCustomers' => User::whereHas('roles', fn ($q) => $q->where('name', 'customer'))->count(),
            'newCustomers' => User::whereHas('roles', fn ($q) => $q->where('name', 'customer'))->where('created_at', '>=', now()->startOfMonth())->count(),
        ]);
    }

    public function vendors()
    {
        $live = fn ($q) => $q->where('payout_status', '!=', 'failed');
        $vendors = Vendor::withCount('products')->withCount(['orders' => $live])
            ->withSum(['orders as revenue' => $live], 'subtotal')->orderByDesc('revenue')->orderBy('id')->paginate(20);

        return view('admin.reports.vendors', compact('vendors'));
    }

    public function support()
    {
        return view('admin.reports.support', [
            'tickets' => SupportTicket::with('user')->latest()->paginate(20),
            'byStatus' => SupportTicket::selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status'),
        ]);
    }
}
