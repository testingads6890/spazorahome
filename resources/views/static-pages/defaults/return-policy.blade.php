@extends('static-pages.layout')

@section('title', 'Returns and refunds - ' . $shop['name'])
@section('meta_description', 'How to return an item, what can and cannot be returned, and when your refund arrives.')
@section('crumb', 'Returns & refunds')
@section('heading', 'Returns and refunds')
@section('lead', 'If something is damaged, wrong or not what the listing promised, here is exactly how we put it right.')
@section('updated', $shop['updated'])

@section('doc')
    <div class="fact-grid">
        <div class="fact"><i class="bi bi-calendar-check"></i><strong>{{ $shop['return_days'] }} days to ask</strong><span>Counted from the day the item is delivered.</span></div>
        <div class="fact"><i class="bi bi-camera"></i><strong>{{ $shop['damage_hours'] }} hours for damage</strong><span>Report transit damage or missing parts quickly, with photos.</span></div>
        <div class="fact"><i class="bi bi-cash-coin"></i><strong>Refund in {{ $shop['refund_days'] }} working days</strong><span>After the returned item is received and checked.</span></div>
    </div>

    <h2>When you can return an item</h2>
    <ul>
        <li><strong>It arrived damaged or defective.</strong> Tell us within {{ $shop['damage_hours'] }} hours of delivery.</li>
        <li><strong>It is not what you ordered.</strong> Wrong product, colour, size or finish.</li>
        <li><strong>It does not match the listing.</strong> The material, dimensions or build differ from what the product page stated.</li>
        <li><strong>Parts are missing.</strong> We will usually send the missing part first, as that is faster than a full return.</li>
        <li><strong>You changed your mind.</strong> Accepted within {{ $shop['return_days'] }} days for eligible items that are unused, unassembled and in their original packaging. The return delivery cost is deducted from the refund.</li>
    </ul>

    <h2>What cannot be returned</h2>
    <ul>
        <li>Made-to-order and customised pieces, unless they arrive damaged or defective.</li>
        <li>Mattresses, pillows, bedding and textiles once opened or used, for hygiene reasons.</li>
        <li>Items that have been assembled, installed, altered or damaged after delivery.</li>
        <li>Items marked "non-returnable" on the product page, and clearance items sold as seen.</li>
        <li>Natural variation. Grain, knots and slight shade differences in solid wood, stone and handmade pieces are part of the material and are not defects.</li>
    </ul>

    <h2>How to return something</h2>
    <ol class="steps-list">
        <li><strong>Raise a ticket.</strong> Go to <a href="{{ route('customer.support-tickets.index') }}">Support</a>, choose the order and tell us what is wrong. Attach photos of the item, the damage and the carton label.</li>
        <li><strong>We review it.</strong> We reply on the ticket. We may offer a replacement part, a repair, a replacement or a refund, depending on the problem.</li>
        <li><strong>We arrange pickup.</strong> Keep the item in its original packaging with all parts, manuals and tags. Our courier collects it from the delivery address.</li>
        <li><strong>The item is checked.</strong> Once it reaches the seller it is inspected against your report.</li>
        <li><strong>You are refunded or sent a replacement.</strong> A credit note is issued against your original GST invoice for any amount refunded.</li>
    </ol>
    <p>You can return part of an order. Only the items you send back are credited; the rest of the order stays as it is.</p>

    <h2>Refunds</h2>
    <div class="table-responsive">
        <table class="doc-table">
            <thead><tr><th>You paid by</th><th>Refund goes to</th></tr></thead>
            <tbody>
                <tr><td>Card, UPI or netbanking</td><td>The same card or account, {{ $shop['refund_days'] }} working days after the return is approved</td></tr>
                <tr><td>Cash on delivery</td><td>Your bank account or UPI ID, which we ask for on the ticket</td></tr>
            </tbody>
        </table>
    </div>
    <p>Your bank may take a few more days to show the credit. The refund covers the price of the returned item and the GST on it. The original delivery charge is refunded when the return is because of damage, a defect or our mistake.</p>

    <h2>Cancelling an order</h2>
    <p>You can cancel from <a href="{{ route('customer.orders.index') }}">My orders</a> for as long as the order has not shipped. Anything you paid online is refunded in full to the original payment method. Once an order has shipped it cannot be cancelled, but you can refuse delivery or request a return under this policy.</p>

    <h2>If we cannot agree</h2>
    <p>If you are unhappy with the outcome of a return request, reply on the same ticket and ask for a review. A senior member of the team will look at the photos and the conversation again. Nothing in this policy takes away the rights you have under Indian consumer law.</p>
@endsection
