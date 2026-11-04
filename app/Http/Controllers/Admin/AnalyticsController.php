<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index()
    {
        $analytics = [
            'total_orders' => Order::count(),
            'total_revenue' => Order::where('status', 'completed')->sum('total'),
            'total_customers' => User::whereHas('roles', function ($query) {
                $query->where('name', 'customer');
            })->count(),
            'total_products' => Product::where('is_active', true)->count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'completed_orders' => Order::where('status', 'completed')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count(),
        ];

        $recentOrders = Order::latest()->take(10)->get();
        $topProducts = Product::withCount('orderItems')
            ->orderByDesc('order_items_count')
            ->take(10)
            ->get();

        $chartData = $this->getChartData();

        return view('admin.analytics.index', compact('analytics', 'recentOrders', 'topProducts', 'chartData'));
    }

    private function getChartData()
    {
        $data = [];
        $labels = [];
        $values = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('M d');
            $values[] = Order::whereDate('created_at', $date)->sum('total');
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }
}