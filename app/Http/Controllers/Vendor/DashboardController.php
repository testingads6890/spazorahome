<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;

class DashboardController extends Controller
{
    public function index()
    {
        $vendor = auth()->user()->vendorAccount();

        // Cancelled orders have their payout marked failed, so they drop out of every money figure below.
        $live = $vendor->orders()->where('payout_status', '!=', 'failed');

        $stats = [
            'products' => $vendor->products()->count(),
            'pending_listings' => $vendor->vendorProducts()->where('status', 'pending')->count(),
            'orders' => (clone $live)->count(),
            'new_orders' => (clone $live)->where('status', 'pending')->count(),
            'sales' => (float) (clone $live)->sum('subtotal'),
            'commission' => (float) (clone $live)->sum('commission'),
            'unpaid' => (float) (clone $live)->whereIn('payout_status', ['pending', 'processing'])->sum('payout_amount'),
            'paid_out' => (float) $vendor->payouts()->where('status', 'completed')->sum('amount'),
        ];

        // Listings by where they are in the approval process.
        $byStatus = $vendor->products()->selectRaw('vendor_status, count(*) as c')->groupBy('vendor_status')->pluck('c', 'vendor_status');
        $listings = [
            'approved' => (int) ($byStatus['approved'] ?? 0),
            'pending' => (int) ($byStatus['pending'] ?? 0),
            'changes_requested' => (int) ($byStatus['changes_requested'] ?? 0),
            'rejected' => (int) ($byStatus['rejected'] ?? 0),
        ];

        // Stock at a glance.
        $inventory = [
            'units' => (int) $vendor->products()->sum('stock_quantity'),
            'in_stock' => $vendor->products()->whereColumn('stock_quantity', '>', 'low_stock_threshold')->count(),
            'low' => $vendor->products()->where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count(),
            'out' => $vendor->products()->where('stock_quantity', '<=', 0)->count(),
        ];

        // Orders by stage (cancelled orders are counted on their own).
        $stages = (clone $live)->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $orderStages = [
            'New' => (int) ($stages['pending'] ?? 0),
            'Processing' => (int) ($stages['processing'] ?? 0),
            'Ready to ship' => (int) ($stages['ready_to_ship'] ?? 0),
            'Shipped' => (int) ($stages['shipped'] ?? 0),
            'Delivered' => (int) ($stages['delivered'] ?? 0),
            'Cancelled' => $vendor->orders()->where('payout_status', 'failed')->count(),
        ];

        // Notifications: everything that is waiting on the seller, newest first.
        $notices = collect();
        foreach ($vendor->products()->whereIn('vendor_status', ['changes_requested', 'rejected'])->latest('updated_at')->limit(8)->get() as $p) {
            $notices->push([
                'icon' => $p->vendor_status === 'rejected' ? 'bi-x-circle' : 'bi-pencil-square',
                'tone' => $p->vendor_status === 'rejected' ? 'danger' : 'warning',
                'text' => ($p->vendor_status === 'rejected' ? 'Listing rejected: ' : 'Changes requested: ') . $p->name,
                'note' => $p->vendor_rejection_reason,
                'url' => route('vendor.products.edit', $p),
                'at' => $p->updated_at,
            ]);
        }
        foreach ($vendor->orders()->with('order')->where('status', 'pending')->where('payout_status', '!=', 'failed')->latest()->limit(8)->get() as $vo) {
            $notices->push([
                'icon' => 'bi-bag-check', 'tone' => 'primary',
                'text' => 'New order ' . ($vo->order?->order_number ?? '#' . $vo->order_id) . ' to process',
                'note' => null, 'url' => route('vendor.orders.show', $vo), 'at' => $vo->created_at,
            ]);
        }
        foreach ($vendor->products()->where('vendor_status', 'approved')->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->orderBy('stock_quantity')->limit(8)->get() as $p) {
            $notices->push([
                'icon' => 'bi-exclamation-triangle', 'tone' => $p->stock_quantity <= 0 ? 'danger' : 'warning',
                'text' => ($p->stock_quantity <= 0 ? 'Out of stock: ' : 'Low stock (' . $p->stock_quantity . ' left): ') . $p->name,
                'note' => null, 'url' => route('vendor.products.edit', $p), 'at' => $p->updated_at,
            ]);
        }
        $notices = $notices->sortByDesc('at')->take(12)->values();

        $recentOrders = $vendor->orders()->with('order')->latest()->limit(6)->get();

        $topProducts = OrderItem::where('vendor_id', $vendor->id)
            ->whereHas('order', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->selectRaw('product_id, product_name, sum(quantity) as units, sum(subtotal) as revenue')
            ->groupBy('product_id', 'product_name')->orderByDesc('units')->limit(5)->get();

        // Sales chart: order lines per day over the chosen period (cancelled ones left out).
        $days = in_array((int) request('days'), [7, 30, 90], true) ? (int) request('days') : 30;
        $from = now()->subDays($days - 1)->startOfDay();
        $daily = (clone $live)->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as d, sum(subtotal) as sales, sum(payout_amount) as earnings, count(*) as orders')
            ->groupBy('d')->get()->keyBy('d');
        $chart = collect(range(0, $days - 1))->map(function ($i) use ($from, $daily) {
            $day = $from->copy()->addDays($i)->toDateString();
            $row = $daily[$day] ?? null;

            return ['date' => $day, 'sales' => (float) ($row->sales ?? 0), 'earnings' => (float) ($row->earnings ?? 0), 'orders' => (int) ($row->orders ?? 0)];
        });
        $period = [
            'days' => $days,
            'sales' => $chart->sum('sales'),
            'earnings' => $chart->sum('earnings'),
            'orders' => $chart->sum('orders'),
            'previous_sales' => (float) (clone $live)->whereBetween('created_at', [$from->copy()->subDays($days), $from])->sum('subtotal'),
        ];

        // Rating across this seller's products, and what can be withdrawn right now.
        $reviewBase = \App\Models\Review::whereHas('product', fn ($q) => $q->where('vendor_id', $vendor->id))->where('is_approved', true);
        $stats['rating'] = round((float) (clone $reviewBase)->avg('rating'), 1);
        $stats['reviews'] = (clone $reviewBase)->count();
        $stats['available'] = \Illuminate\Support\Facades\Schema::hasTable('vendor_withdrawals')
            ? (float) \App\Models\VendorWithdrawal::availableLines($vendor)->sum('payout_amount') : 0.0;
        $stats['earnings'] = (float) (clone $live)->sum('payout_amount');

        // Staff only get links and money figures for the areas their account allows.
        $user = auth()->user();
        $can = ['products' => $user->vendorCan('products'), 'orders' => $user->vendorCan('orders'), 'payouts' => $user->vendorCan('payouts')];

        return view('vendor.dashboard', compact('vendor', 'stats', 'recentOrders', 'topProducts', 'can', 'listings', 'inventory', 'orderStages', 'notices', 'chart', 'period'));
    }
}
