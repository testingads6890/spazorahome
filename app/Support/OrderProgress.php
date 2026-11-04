<?php

namespace App\Support;

use App\Models\Order;

/**
 * The stages an order passes through, as the customer and the team see them:
 * Placed, Payment confirmed, Processing, Ready for shipment, Shipment created, Picked up, In transit,
 * Out for delivery, Delivered. Worked out from the order status, its payments and its shipment.
 */
class OrderProgress
{
    /** Order statuses in the order they happen. */
    public const RANK = ['pending' => 0, 'processing' => 1, 'ready_to_ship' => 2, 'shipped' => 3, 'out_for_delivery' => 4, 'delivered' => 5];

    /** Shipment statuses in the order they happen. */
    public const SHIPMENT_RANK = ['pending' => 0, 'picked_up' => 1, 'in_transit' => 2, 'out_for_delivery' => 3, 'delivered' => 4];

    /** @return array<int, array{key: string, label: string, state: string}> state: done, now or next */
    public static function steps(Order $order): array
    {
        $rank = self::RANK[$order->status] ?? 0;
        $shipment = $order->relationLoaded('shipment') ? $order->shipment : $order->shipment()->first();
        $shipRank = $shipment ? (self::SHIPMENT_RANK[$shipment->status] ?? 0) : -1;
        $payments = $order->relationLoaded('payments') ? $order->payments : $order->payments()->get();
        $paid = $payments->contains(fn ($p) => in_array($p->status, ['completed', 'paid', 'successful'], true));
        $cod = $payments->contains(fn ($p) => $p->method === 'cod');

        $done = [
            'placed' => true,
            'payment' => $paid || $cod || $rank >= 1,
            'processing' => $rank >= 1,
            'ready' => $rank >= 2,
            'shipment' => $rank >= 3 || $shipRank >= 0,
            'picked_up' => $rank >= 3 && ($shipRank >= 1 || $shipRank === -1) || $rank >= 4,
            'in_transit' => $shipRank >= 2 || $rank >= 4 || ($rank === 3 && $shipRank === -1),
            'out_for_delivery' => $rank >= 4 || $shipRank >= 3,
            'delivered' => $rank >= 5 || $shipRank >= 4,
        ];
        $labels = [
            'placed' => __('Order placed'),
            'payment' => $cod && ! $paid ? __('Cash on delivery') : __('Payment confirmed'),
            'processing' => __('Seller processing'),
            'ready' => __('Ready for shipment'),
            'shipment' => __('Shipment created'),
            'picked_up' => __('Picked up'),
            'in_transit' => __('In transit'),
            'out_for_delivery' => __('Out for delivery'),
            'delivered' => __('Delivered'),
        ];

        // A later stage being done means every earlier one is done too.
        $keys = array_keys($done);
        for ($i = count($keys) - 1, $later = false; $i >= 0; $i--) {
            $later = $later || $done[$keys[$i]];
            $done[$keys[$i]] = $later;
        }

        $steps = [];
        $nowSet = false;
        foreach ($keys as $key) {
            $state = $done[$key] ? 'done' : (! $nowSet ? 'now' : 'next');
            $nowSet = $nowSet || $state === 'now';
            $steps[] = ['key' => $key, 'label' => $labels[$key], 'state' => $state];
        }

        return $steps;
    }
}
