<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Gives money back to the customer.
 *
 * Razorpay payments are refunded through the Razorpay API. Everything else (cash on delivery that was
 * collected, a Razorpay call that failed, missing API keys) is recorded as "refund due" so an admin can
 * retry it or pay it by hand and mark it done. Nothing is ever shown as refunded unless it was sent.
 */
class RefundService
{
    /**
     * Refund $amount of an order (null = everything the customer paid and has not got back yet).
     * Call this AFTER the database transaction that cancelled / credited the order has committed.
     *
     * @return array{refunded: float, due: float} what was sent now, and what is left for manual follow-up
     */
    public function refundOrder(Order $order, ?float $amount = null, string $reason = 'Order cancelled'): array
    {
        $sent = 0.0;
        $due = 0.0;

        foreach ($order->payments()->whereNotNull('paid_at')->orderBy('id')->get() as $payment) {
            $want = $amount === null ? $payment->refundable() : min($payment->refundable(), round($amount - $sent - $due, 2));
            if ($want <= 0) {
                continue;
            }

            $this->refund($payment, $want, $reason) ? $sent += $want : $due += $want;
        }

        return ['refunded' => round($sent, 2), 'due' => round($due, 2)];
    }

    /**
     * Refund part of one payment. True when the money was sent; false when it stays queued as "refund due".
     *
     * The amount is first reserved as "due" in a single guarded UPDATE, so two requests at the same moment can
     * never promise the same money twice; it only moves to "refunded" once the gateway confirms.
     */
    public function refund(Payment $payment, float $amount, string $reason = ''): bool
    {
        $amount = round(min($amount, $payment->refundable()), 2);
        if ($amount <= 0) {
            return true;
        }

        // Amounts go into the SQL as plain numbers (they are computed here, never user text); the half-paisa slack
        // only matters on SQLite, which does this arithmetic in floating point.
        $value = number_format($amount, 2, '.', '');
        $reserved = Payment::whereKey($payment->id)->whereNotNull('paid_at')
            ->whereRaw("amount - refunded_amount - refund_due >= {$value} - 0.005")
            ->update(['refund_due' => DB::raw("refund_due + {$value}")]);
        if (! $reserved) {
            return false; // already promised elsewhere
        }

        return $this->settle($payment, $amount, $this->send($payment, $amount, $reason));
    }

    /** Try again to send whatever is still owed on this payment. */
    public function retry(Payment $payment): bool
    {
        $amount = round((float) $payment->fresh()->refund_due, 2);
        if ($amount <= 0) {
            return true;
        }

        return $this->settle($payment, $amount, $this->send($payment, $amount, 'Retry'));
    }

    /** The admin paid the customer back outside the gateway (bank transfer, cash, Razorpay dashboard). */
    public function markManuallyRefunded(Payment $payment, ?string $reference = null): void
    {
        $amount = round((float) $payment->fresh()->refund_due, 2);
        if ($amount > 0) {
            $this->settle($payment, $amount, ['ok' => true, 'id' => $reference, 'status' => 'manual', 'error' => null]);
        }
    }

    public function gatewayConfigured(): bool
    {
        return filled(config('services.razorpay.key')) && filled(config('services.razorpay.secret'));
    }

    /** @return array{ok: bool, id: ?string, status: ?string, error: ?string} */
    private function send(Payment $payment, float $amount, string $reason): array
    {
        if ($payment->method !== 'razorpay' || ! $payment->transaction_id) {
            return ['ok' => false, 'id' => null, 'status' => null, 'error' => 'This payment was not taken through Razorpay; refund it by hand.'];
        }
        if (! $this->gatewayConfigured()) {
            return ['ok' => false, 'id' => null, 'status' => null, 'error' => 'Razorpay keys are not configured.'];
        }

        // A payment charged in another currency is refunded in that currency, in proportion to the rupee amount;
        // the refund that completes the payment sends whatever is left, so the customer gets back exactly what they paid.
        $gatewayAmount = $amount;
        if ($payment->charge_amount !== null && (float) $payment->amount > 0) {
            $sentBefore = (float) collect($payment->payment_details['refunds'] ?? [])->sum('gateway_amount');
            $completes = round((float) $payment->refunded_amount + $amount, 2) >= round((float) $payment->amount, 2);
            $gatewayAmount = $completes
                ? round((float) $payment->charge_amount - $sentBefore, 2)
                : round($amount * (float) $payment->charge_amount / (float) $payment->amount, 2);
        }

        try {
            $response = Http::withBasicAuth(config('services.razorpay.key'), config('services.razorpay.secret'))
                ->timeout(20)
                ->post('https://api.razorpay.com/v1/payments/' . $payment->transaction_id . '/refund', [
                    'amount' => (int) round($gatewayAmount * 100),
                    'speed' => 'normal',
                    'notes' => ['order' => (string) $payment->order?->order_number, 'reason' => mb_substr($reason, 0, 200)],
                ]);
        } catch (\Throwable $e) {
            report($e);

            return ['ok' => false, 'id' => null, 'status' => null, 'error' => 'Could not reach Razorpay: ' . $e->getMessage()];
        }

        if ($response->failed() || ! $response->json('id')) {
            $error = $response->json('error.description') ?: ('Razorpay answered with status ' . $response->status());
            report(new \RuntimeException('Razorpay refund failed for payment ' . $payment->id . ': ' . $response->body()));

            return ['ok' => false, 'id' => null, 'status' => null, 'error' => $error];
        }

        return ['ok' => true, 'id' => $response->json('id'), 'status' => $response->json('status'), 'error' => null, 'gateway_amount' => $gatewayAmount, 'currency' => $payment->currency ?: 'INR'];
    }

    /**
     * Book the outcome for an amount that is currently reserved as "due": on success it moves to "refunded"
     * (guarded, so a double click cannot move it twice); on failure it stays due and the reason is kept.
     */
    private function settle(Payment $payment, float $amount, array $result): bool
    {
        $value = number_format($amount, 2, '.', '');

        if ($result['ok']) {
            $moved = Payment::whereKey($payment->id)->whereRaw("refund_due >= {$value} - 0.005")->update([
                'refund_due' => DB::raw("refund_due - {$value}"),
                'refunded_amount' => DB::raw("refunded_amount + {$value}"),
            ]);
            if (! $moved) {
                return true; // another request settled it first
            }
        }

        $payment->refresh();
        $details = $payment->payment_details ?? [];
        if ($result['ok']) {
            $details['refunds'][] = ['id' => $result['id'], 'amount' => $amount, 'status' => $result['status'], 'at' => now()->toDateTimeString()]
                + (isset($result['gateway_amount']) && $payment->charge_amount !== null ? ['gateway_amount' => $result['gateway_amount'], 'currency' => $result['currency']] : []);
            unset($details['refund_error']);
        } else {
            $details['refund_error'] = $result['error'];
        }
        $payment->payment_details = $details;
        // "refunded" means the whole payment is being given back; a part refund leaves it "completed".
        if (round((float) $payment->refunded_amount + (float) $payment->refund_due, 2) >= round((float) $payment->amount, 2)) {
            $payment->status = 'refunded';
        }
        $payment->save();

        return $result['ok'];
    }
}
