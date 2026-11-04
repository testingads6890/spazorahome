<?php

namespace Tests\Unit\Support;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Shipment;
use App\Support\OrderProgress;
use Tests\TestCase;

class OrderProgressTest extends TestCase
{
    private function states(string $status, array $payments = [], ?string $shipment = null): array
    {
        $order = new Order(['status' => $status]);
        $order->setRelation('payments', collect(array_map(fn ($p) => new Payment($p), $payments)));
        $order->setRelation('shipment', $shipment ? new Shipment(['status' => $shipment]) : null);

        return array_column(OrderProgress::steps($order), 'state', 'key');
    }

    public function test_new_unpaid_order(): void
    {
        $s = $this->states('pending');
        $this->assertSame('done', $s['placed']);
        $this->assertSame('now', $s['payment']);
        $this->assertSame('next', $s['processing']);
        $this->assertSame('next', $s['delivered']);
    }

    public function test_paid_order_moves_to_processing(): void
    {
        $s = $this->states('pending', [['status' => 'completed', 'method' => 'razorpay']]);
        $this->assertSame('done', $s['payment']);
        $this->assertSame('now', $s['processing']);
    }

    public function test_cash_on_delivery_counts_as_payment_step_with_cod_label(): void
    {
        $order = new Order(['status' => 'pending']);
        $order->setRelation('payments', collect([new Payment(['status' => 'pending', 'method' => 'cod'])]));
        $order->setRelation('shipment', null);
        $steps = collect(OrderProgress::steps($order))->keyBy('key');

        $this->assertSame('done', $steps['payment']['state']);
        $this->assertSame('Cash on delivery', $steps['payment']['label']);
    }

    public function test_shipped_order_without_shipment_record_is_in_transit(): void
    {
        $s = $this->states('shipped');
        $this->assertSame('done', $s['in_transit']);
        $this->assertSame('now', $s['out_for_delivery']);
    }

    public function test_shipment_status_drives_later_steps_and_fills_earlier_ones(): void
    {
        $s = $this->states('processing', [], 'out_for_delivery');
        foreach (['placed', 'payment', 'processing', 'ready', 'shipment', 'picked_up', 'in_transit', 'out_for_delivery'] as $key) {
            $this->assertSame('done', $s[$key], $key);
        }
        $this->assertSame('now', $s['delivered']);
    }

    public function test_delivered_order_is_all_done_and_has_one_now_at_most(): void
    {
        $this->assertSame(['done'], array_values(array_unique($this->states('delivered'))));

        $counts = array_count_values($this->states('processing'));
        $this->assertSame(1, $counts['now']);
    }

    public function test_unknown_status_is_treated_as_pending(): void
    {
        $this->assertSame($this->states('pending'), $this->states('weird'));
    }
}
