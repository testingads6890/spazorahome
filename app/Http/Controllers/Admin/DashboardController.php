<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\SupportTicket;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'users' => User::count(),
            'products' => Product::count(),
            'orders' => Order::count(),
            // Cancelled orders never count as revenue.
            'revenue' => (float) Order::where('status', '!=', 'cancelled')->sum('total'),
        ];

        $activity = collect()
            ->concat(User::latest()->limit(3)->get()->map(fn ($u) => [
                'time' => $u->created_at, 'color' => 'primary', 'title' => 'New user', 'text' => "{$u->name} registered",
            ]))
            ->concat(Order::latest()->limit(3)->get()->map(fn ($o) => [
                'time' => $o->created_at, 'color' => 'warning', 'title' => 'Order ' . $o->order_number, 'text' => 'Status: ' . ucfirst($o->status),
            ]))
            ->concat(SupportTicket::latest()->limit(3)->get()->map(fn ($t) => [
                'time' => $t->created_at, 'color' => 'info', 'title' => 'Support ticket', 'text' => $t->subject,
            ]))
            ->sortByDesc('time')->take(6);

        return view('admin.dashboard', compact('stats', 'activity'));
    }
}
