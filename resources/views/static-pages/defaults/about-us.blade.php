@extends('static-pages.layout')

@section('title', 'About us - ' . $shop['name'])
@section('meta_description', 'Who we are: a furniture and home decor marketplace that checks every seller, shows the full price before you pay and delivers across India.')
@section('crumb', 'About us')
@section('kicker', 'Our story')
@section('heading', 'A home should be built slowly, and bought honestly.')
@section('lead', $shop['name'] . ' is a marketplace for furniture and decor from makers we have checked ourselves. One cart, one checkout, a GST invoice every time.')

@section('doc')
    <p class="doc-intro">Buying furniture online usually means guessing. Is the wood what the listing says? Will the sofa fit through the door? Who do you call when a leg arrives cracked? We started {{ $shop['name'] }} to take the guesswork out of those three questions.</p>

    <div class="fact-grid pairs">
        <div class="fact"><i class="bi bi-patch-check"></i><strong>Every seller is reviewed</strong><span>Workshops and brands apply to sell. Nobody lists a product until our team has approved them.</span></div>
        <div class="fact"><i class="bi bi-receipt"></i><strong>The price is the price</strong><span>GST and delivery are shown at checkout before you pay, and a GST invoice follows every order.</span></div>
        <div class="fact"><i class="bi bi-truck"></i><strong>Delivered to your door</strong><span>Free delivery on orders above {{ $shop['free_over'] }}, with tracking from dispatch to doorstep.</span></div>
        <div class="fact"><i class="bi bi-arrow-return-left"></i><strong>A real way back</strong><span>If something is wrong, you have {{ $shop['return_days'] }} days to tell us and a written policy to rely on.</span></div>
    </div>

    <h2>What we sell</h2>
    <p>Twelve rooms and moods, from the living room to the balcony: sofas and seating, beds and storage, dining sets, study and office desks, lighting, rugs and textiles, kitchen and bathroom fittings, children's furniture, hallway pieces, outdoor furniture and the small decorative things that make a room feel finished.</p>
    <p>You can <a href="{{ route('search') }}">browse everything</a>, shop room by room from the home page, or visit an individual <a href="{{ route('stores.index') }}">seller's store</a> to see their full range.</p>

    <h2>How the marketplace works</h2>
    <ol class="steps-list">
        <li><strong>Makers apply.</strong> A seller sends us their business details and the kind of products they make. We review each application before the store goes live.</li>
        <li><strong>Products are listed with the facts.</strong> Dimensions, material, finish, GST rate and stock are part of every listing, so you can compare like with like.</li>
        <li><strong>You order from one cart.</strong> Pieces from different sellers sit in the same cart and are paid for in one checkout, by card, UPI, netbanking or cash on delivery.</li>
        <li><strong>The seller packs, we stay in the loop.</strong> Your order is packed at the seller's end and handed to a courier. You get a tracking number, and we stay responsible for the order until it reaches you.</li>
        <li><strong>We handle what goes wrong.</strong> Returns, refunds and complaints come to our support team, not to a seller you have never spoken to.</li>
    </ol>

    <h2>What we care about</h2>
    <h3>Honest listings</h3>
    <p>We would rather lose a sale than have a customer open a box and feel misled. If a tabletop is veneer over engineered wood, the listing should say so. If you find a listing that does not match what arrived, tell us and we will fix it.</p>
    <h3>Makers who are paid fairly</h3>
    <p>Small workshops do not have the reach of large brands. A marketplace gives them customers across the country without a showroom in every city, and a clear payout for every order they fulfil.</p>
    <h3>Furniture that stays</h3>
    <p>The cheapest piece is often the one you buy twice. We favour sellers who build things to be repaired, re-polished and handed on.</p>

    @if($shop['export'])
        <h2>Beyond India</h2>
        <p>Selected products can be delivered outside India. International orders are invoiced without Indian GST, and any import duty in your country is paid by you on delivery. Product pages show the countries each item ships to, and bulk buyers can send an export enquiry from the product page.</p>
    @endif

    <h2>Sell with us</h2>
    <p>If you make or trade furniture, lighting, textiles or decor, we would like to hear from you. The application takes a few minutes and every seller is reviewed before going live.</p>
    <p><a href="{{ route('vendor.register') }}" class="btn btn-accent">Apply to sell</a> <a href="{{ route('contact-us') }}" class="btn btn-outline-primary">Talk to us</a></p>
@endsection
