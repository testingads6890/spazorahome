@extends('static-pages.layout')

@section('title', 'Shipping policy - ' . $shop['name'])
@section('meta_description', 'Delivery charges, timelines, tracking and what to check when your furniture arrives.')
@section('crumb', 'Shipping policy')
@section('heading', 'Shipping policy')
@section('lead', 'What delivery costs, how long it takes and what to do on the day your order arrives.')
@section('updated', $shop['updated'])

@section('doc')
    <div class="fact-grid">
        <div class="fact"><i class="bi bi-truck"></i><strong>Free above {{ $shop['free_over'] }}</strong><span>On the standard delivery rate. Below that, delivery starts at {{ $shop['fee'] }}.</span></div>
        <div class="fact"><i class="bi bi-box-seam"></i><strong>Ships in {{ $shop['dispatch_days'] }} working days</strong><span>For items in stock. Made-to-order pieces take longer and say so on the product page.</span></div>
        <div class="fact"><i class="bi bi-geo-alt"></i><strong>Tracked all the way</strong><span>You get a tracking number as soon as your order leaves the seller.</span></div>
    </div>

    <h2>Where we deliver</h2>
    <p>We deliver across India to every pin code our courier partners serve. Enter your address at checkout and the delivery options for that address are shown before you pay. If we cannot reach your pin code, checkout tells you at that point, not after you have paid.</p>
    @if($shop['export'])
        <p>Selected products are also delivered outside India. The product page lists the countries each item ships to. See <a href="#international">international delivery</a> below.</p>
    @endif

    <h2>Delivery charges</h2>
    <ul>
        <li>Orders above <strong>{{ $shop['free_over'] }}</strong> are delivered free on the standard rate.</li>
        <li>Orders below that carry a standard delivery charge starting at <strong>{{ $shop['fee'] }}</strong>.</li>
        <li>Some addresses and faster delivery options cost more. The exact charge for your address is always shown at checkout, and GST on delivery is itemised on your invoice.</li>
    </ul>

    <h2>How long it takes</h2>
    <div class="table-responsive">
        <table class="doc-table">
            <thead><tr><th>Stage</th><th>Typical time</th></tr></thead>
            <tbody>
                <tr><td>Packing and dispatch (items in stock)</td><td>{{ $shop['dispatch_days'] }} working days</td></tr>
                <tr><td>Transit to metro and large cities</td><td>{{ $shop['delivery_metro'] }} working days after dispatch</td></tr>
                <tr><td>Transit to other towns and remote pin codes</td><td>{{ $shop['delivery_other'] }} working days after dispatch</td></tr>
            </tbody>
        </table>
    </div>
    <p>These are estimates, not guarantees. Large furniture travels by surface transport, and weather, festivals, strikes and local restrictions can add days. If your order is running late, <a href="{{ route('customer.order-tracking.index') }}">track it</a> first and raise a ticket if the tracking has not moved for three working days.</p>

    <h2>Orders with more than one seller</h2>
    <p>{{ $shop['name'] }} is a marketplace. If your cart holds pieces from different sellers, each seller packs and ships their own part, so your order may arrive in more than one delivery, each with its own tracking number. You pay once and are not charged extra for the split.</p>

    <h2>Tracking your order</h2>
    <p>Once your order ships, the courier name and tracking number appear on the order page in your account and on our <a href="{{ route('customer.order-tracking.index') }}">order tracking page</a>.</p>

    <h2>On delivery day</h2>
    <ol class="steps-list">
        <li><strong>Be reachable.</strong> The courier calls the phone number on the order before arriving. After repeated failed attempts the parcel goes back to the seller, and a re-delivery charge may apply.</li>
        <li><strong>Check the cartons before you sign.</strong> Count them against the delivery note. If a box is crushed, torn or wet, write that on the courier's receipt and take a photo.</li>
        <li><strong>Open and inspect within {{ $shop['damage_hours'] }} hours.</strong> Report any damage or missing part within that time, with photos, so we can put it right under our <a href="{{ route('returns') }}">returns policy</a>.</li>
    </ol>
    <p>Standard delivery is to your doorstep. Carrying items upstairs, assembly and removal of old furniture are not included unless the product page or your delivery option says they are.</p>

    <h2>Changing an address or cancelling</h2>
    <p>You can cancel an order from your account for as long as it has not shipped. To change the delivery address, raise a ticket before dispatch. After dispatch the address cannot be changed.</p>

    @if($shop['export'])
        <h2 id="international">International delivery</h2>
        <ul>
            <li>International orders are invoiced without Indian GST.</li>
            <li>Customs duty, import tax and clearance fees in the destination country are paid by the buyer on delivery.</li>
            <li>Orders delivered outside India must be paid for online. Cash on delivery is not available.</li>
            <li>Some products have a minimum quantity for export, shown on the product page.</li>
        </ul>
    @endif
@endsection
