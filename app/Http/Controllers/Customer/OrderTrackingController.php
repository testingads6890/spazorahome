<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderTrackingController extends Controller
{
    public function index()
    {
        // Get orders with tracking information
        $orders = Order::where('user_id', auth()->id())
            ->with('items.product')
            ->latest()
            ->paginate(20);
            
        return view('customer.order-tracking.index', compact('orders'));
    }

    public function trackOrder(Request $request)
    {
        $trackingNumber = trim($request->validate(['tracking_number' => 'required|string|max:100'])['tracking_number']);

        $order = Order::where('tracking_number', $trackingNumber)
            ->where('user_id', auth()->id())
            ->with('items.product')
            ->firstOrFail();
            
        return view('customer.order-tracking.track', compact('order'));
    }

    public function updateTracking(Request $request, $orderId)
    {
        $order = Order::where('id', $orderId)
            ->where('user_id', auth()->id())
            ->firstOrFail();
            
        $validated = $request->validate([
            'tracking_number' => 'required|string|unique:orders,tracking_number,' . $order->id,
            'carrier' => 'nullable|string',
            'tracking_url' => 'nullable|url',
            'estimated_delivery' => 'nullable|date',
            'status' => 'required|in:pending,processing,shipped,out_for_delivery,delivered,cancelled',
        ]);
        
        $order->update($validated);
        
        // If order status is delivered, mark as completed
        if ($validated['status'] === 'delivered') {
            $order->update(['status' => 'delivered', 'delivered_at' => now()]);
        }
        
        return redirect()->back()->with('success', 'Order tracking information updated successfully.');
    }

    public function integrateCarrierTracking($orderId, $carrier)
    {
        $order = Order::where('id', $orderId)
            ->where('user_id', auth()->id())
            ->firstOrFail();
            
        // Simulate carrier tracking integration
        // In a real implementation, this would call carrier APIs
        $trackingNumber = $this->generateTrackingNumber();
        
        $order->update([
            'tracking_number' => $trackingNumber,
            'carrier' => $carrier,
            'tracking_url' => "https://{$carrier}.com/track/{$trackingNumber}",
            'status' => 'processing',
        ]);
        
        return redirect()->back()->with('success', "Order tracking integrated with {$carrier}. Tracking number: {$trackingNumber}");
    }

    private function generateTrackingNumber()
    {
        do {
            $trackingNumber = 'TRK' . strtoupper(substr(md5(mt_rand()), 0, 8));
        } while (Order::where('tracking_number', $trackingNumber)->exists());
        
        return $trackingNumber;
    }
}
