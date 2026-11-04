<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RazorpayController extends Controller
{
    public function initiate(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        $payment = $order->payments()->where('method', 'razorpay')->latest('id')->first();
        if (! $payment || $payment->status === 'completed') {
            return redirect()->route('customer.orders.show', $order);
        }

        $key = config('services.razorpay.key');
        $secret = config('services.razorpay.secret');
        if (! $key || ! $secret) {
            return redirect()->route('customer.orders.show', $order)
                ->with('error', 'Online payment is not configured. Please contact support.');
        }

        $details = $payment->payment_details ?? [];
        if (empty($details['razorpay_order_id'])) {
            $response = Http::withBasicAuth($key, $secret)->post('https://api.razorpay.com/v1/orders', [
                // An international order may be charged in another currency; charge_amount is that amount.
                'amount' => $this->paise($payment->charge_amount ?? $payment->amount),
                'currency' => $payment->currency ?: 'INR',
                'receipt' => $order->order_number,
            ]);

            if ($response->failed()) {
                report(new \RuntimeException('Razorpay order creation failed (HTTP ' . $response->status() . ').'));
                return redirect()->route('customer.orders.show', $order)
                    ->with('error', 'Could not start the payment. Please try again.');
            }

            $details['razorpay_order_id'] = $response->json('id');
            $payment->update(['payment_details' => $details]);
        }

        return view('payments.razorpay', [
            'order' => $order,
            'razorpayOrderId' => $details['razorpay_order_id'],
            'amountPaise' => $this->paise($payment->charge_amount ?? $payment->amount),
            'currency' => $payment->currency ?: 'INR',
            'display' => $payment->charge_amount !== null ? $payment->currency . ' ' . number_format((float) $payment->charge_amount, 2) : '₹' . number_format((float) $payment->amount, 2),
        ]);
    }

    public function callback(Request $request)
    {
        $data = $request->validate([
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $payment = Payment::where('method', 'razorpay')
            ->where('payment_details->razorpay_order_id', $data['razorpay_order_id'])
            ->whereHas('order', fn ($q) => $q->where('user_id', auth()->id()))
            ->firstOrFail();
        $order = $payment->order;

        if ($payment->status === 'completed') {
            return redirect()->route('customer.orders.show', $order)->with('success', 'Payment already received.');
        }

        $secret = (string) config('services.razorpay.secret');
        $expected = hash_hmac('sha256', $data['razorpay_order_id'] . '|' . $data['razorpay_payment_id'], $secret);
        if ($secret === '' || ! hash_equals($expected, $data['razorpay_signature'])) {
            return redirect()->route('customer.orders.show', $order)->with('error', 'Payment verification failed.');
        }

        // A valid signature binds the payment ID to the Razorpay order, but fetch the payment server-side
        // as well so a merely-authorized/failed payment cannot be treated as money received.
        try {
            $gateway = Http::withBasicAuth((string) config('services.razorpay.key'), $secret)
                ->acceptJson()->timeout(10)
                ->get('https://api.razorpay.com/v1/payments/' . rawurlencode($data['razorpay_payment_id']));
        } catch (\Throwable $e) {
            report(new \RuntimeException('Razorpay payment verification request failed.', 0, $e));
            return redirect()->route('customer.orders.show', $order)->with('error', 'Payment verification is temporarily unavailable. Please refresh the order in a moment.');
        }

        $gatewayPayment = $gateway->json();
        $expectedAmount = $this->paise($payment->charge_amount ?? $payment->amount);
        $expectedCurrency = strtoupper((string) ($payment->currency ?: 'INR'));
        $gatewayMatches = $gateway->successful()
            && is_array($gatewayPayment)
            && ($gatewayPayment['status'] ?? null) === 'captured'
            && (string) ($gatewayPayment['order_id'] ?? '') === $data['razorpay_order_id']
            && (int) ($gatewayPayment['amount'] ?? -1) === $expectedAmount
            && strtoupper((string) ($gatewayPayment['currency'] ?? '')) === $expectedCurrency;

        if (! $gatewayMatches) {
            // Log only identifiers/status, never credentials or full gateway payloads.
            report(new \RuntimeException('Razorpay payment did not pass server-side verification (HTTP ' . $gateway->status() . ').'));
            return redirect()->route('customer.orders.show', $order)->with('error', 'Payment is not yet confirmed as captured. If money was debited, contact support before retrying.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($payment, $order, $data) {
            $lockedPayment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($lockedPayment->status === 'completed') {
                return;
            }
            $lockedPayment->update([
                'status' => 'completed',
                'transaction_id' => $data['razorpay_payment_id'],
                'payment_details' => array_merge($lockedPayment->payment_details ?? [], ['razorpay_signature' => $data['razorpay_signature']]),
                'paid_at' => now(),
            ]);
            $lockedPayment->order()->whereIn('status', ['pending', 'confirmed'])->update(['status' => 'processing']);
        });

        return redirect()->route('customer.orders.show', $order)->with('success', 'Payment successful.');
    }

    private function paise($amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
