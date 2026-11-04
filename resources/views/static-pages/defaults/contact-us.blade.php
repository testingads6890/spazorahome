@extends('static-pages.layout')

@section('title', 'Contact us - ' . $shop['name'])
@section('meta_description', 'Get help with an order, a return, a product question or selling on ' . $shop['name'] . '.')
@section('crumb', 'Contact us')
@section('kicker', 'We are here')
@section('heading', 'Talk to us')
@section('lead', 'Pick the route that fits your question. The quickest answers come when we have your order number in front of us.')

@section('doc')
    <div class="contact-grid">
        <a class="contact-card" href="{{ route('customer.support-tickets.index') }}">
            <i class="bi bi-ticket-perforated"></i>
            <strong>Raise a support ticket</strong>
            <span>Best for anything about an order: delivery, damage, returns, refunds or invoices. Sign in and your order history is attached automatically.</span>
            <em>Open a ticket <i class="bi bi-arrow-right"></i></em>
        </a>
        <a class="contact-card" href="{{ route('customer.order-tracking.index') }}">
            <i class="bi bi-geo-alt"></i>
            <strong>Track an order</strong>
            <span>Enter the tracking number from your dispatch email to see where your parcel is right now.</span>
            <em>Track order <i class="bi bi-arrow-right"></i></em>
        </a>
        @if($shop['email'])
            <a class="contact-card" href="mailto:{{ $shop['email'] }}">
                <i class="bi bi-envelope"></i>
                <strong>Email us</strong>
                <span>For product questions before you buy, press and partnership enquiries.</span>
                <em>{{ $shop['email'] }}</em>
            </a>
        @endif
        @if($shop['phone'])
            <a class="contact-card" href="tel:{{ preg_replace('/[^0-9+]/', '', $shop['phone']) }}">
                <i class="bi bi-telephone"></i>
                <strong>Call us</strong>
                <span>{{ $shop['hours'] }}.</span>
                <em>{{ $shop['phone'] }}</em>
            </a>
        @endif
        <a class="contact-card" href="{{ route('vendor.register') }}">
            <i class="bi bi-shop"></i>
            <strong>Sell on {{ $shop['name'] }}</strong>
            <span>Makers, workshops and brands can apply online. We review every application before a store goes live.</span>
            <em>Apply to sell <i class="bi bi-arrow-right"></i></em>
        </a>
        <a class="contact-card" href="{{ route('faq') }}">
            <i class="bi bi-question-circle"></i>
            <strong>Read the FAQ</strong>
            <span>Delivery times, payment options, returns and GST invoices are answered there.</span>
            <em>See answers <i class="bi bi-arrow-right"></i></em>
        </a>
    </div>

    <h2>Before you write to us</h2>
    <p>Three details save a round of back-and-forth:</p>
    <ul>
        <li><strong>Your order number.</strong> It is in your confirmation email and under <a href="{{ route('customer.orders.index') }}">My orders</a>.</li>
        <li><strong>Photos, if something arrived damaged.</strong> One of the outer box, one of the damage and one of the label on the carton.</li>
        <li><strong>What you would like us to do.</strong> A replacement, a repair, a refund or simply an answer.</li>
    </ul>

    <h2>When we reply</h2>
    <p>Our support team works {{ $shop['hours'] }}. Tickets raised outside these hours are picked up on the next working day, in the order they arrive. Damage reports are looked at first.</p>

    @if($shop['address'] || $shop['gstin'])
        <h2>Registered details</h2>
        <dl class="doc-dl">
            <dt>Business name</dt><dd>{{ $shop['name'] }}</dd>
            @if($shop['address'])<dt>Address</dt><dd>{{ $shop['address'] }}</dd>@endif
            @if($shop['gstin'])<dt>GSTIN</dt><dd>{{ $shop['gstin'] }}</dd>@endif
        </dl>
    @endif

    <h2>Complaints</h2>
    <p>If a ticket has not been resolved to your satisfaction, reply on the same ticket and ask for it to be escalated. A senior member of the team will review the whole conversation and respond. Our <a href="{{ route('privacy') }}">privacy policy</a> explains how to raise a concern about your personal data.</p>
@endsection
