@extends('layouts.app')

@section('title', 'Razorpay Checkout')

@section('content')
<div class="container py-5 text-center">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h2>Complete Payment</h2>
                    <p class="text-muted">Order #{{ $order->order_number }}</p>
                    <h3 class="my-4">{{ $display }}</h3>

                    <div id="razorpay-btn"></div>

                    <p class="mt-3 text-muted">You will be redirected to Razorpay to complete your payment securely.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    var options = {
        key: {{ \Illuminate\Support\Js::from(config('services.razorpay.key')) }},
        amount: {{ \Illuminate\Support\Js::from((string) $amountPaise) }},
        currency: {{ \Illuminate\Support\Js::from($currency) }},
        name: "SpazoraHome",
        description: {{ \Illuminate\Support\Js::from("Order #" . $order->order_number) }},
        order_id: {{ \Illuminate\Support\Js::from($razorpayOrderId) }},
        handler: function (response) {
            window.location.href = "{{ route('payments.razorpay.callback') }}?razorpay_payment_id=" + response.razorpay_payment_id + "&razorpay_order_id=" + response.razorpay_order_id + "&razorpay_signature=" + response.razorpay_signature;
        },
        prefill: {
            name: {{ \Illuminate\Support\Js::from(auth()->user()->name) }},
            email: {{ \Illuminate\Support\Js::from(auth()->user()->email) }},
        },
        theme: {
            color: "#0d6efd"
        }
    };
    var rzp = new Razorpay(options);
    rzp.on('payment.failed', function (response) {
        alert('Payment failed. Please try again.');
        window.location.href = "{{ route('customer.orders.index') }}";
    });
    rzp.open();
</script>
@endpush
