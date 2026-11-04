@extends('static-pages.layout')

@section('title', 'Help and FAQ - ' . $shop['name'])
@section('meta_description', 'Answers about ordering, payment, delivery, returns, GST invoices and selling on ' . $shop['name'] . '.')
@section('crumb', 'FAQ')
@section('heading', 'How can we help?')
@section('lead', 'The questions customers ask us most, answered in a few lines each.')

@php
    $faqs = [
        'Ordering' => ['bi-bag', [
            ['Do I need an account to order?', 'No. You can check out as a guest with just your email, phone number and address. An account is useful if you want to save addresses, keep a wishlist and see all your orders in one place.'],
            ['Can I buy from several sellers in one order?', 'Yes. Add pieces from any seller to the same cart and pay once. Each seller ships their own items, so the order may arrive in more than one delivery.'],
            ['How do I know an item will fit?', 'Every product page lists the dimensions. Measure the space, then measure the route to it: the lift, the staircase turn and the narrowest doorway. If a measurement you need is missing, ask us before you order.'],
            ['Can I change or cancel my order?', 'You can cancel from My orders for as long as the order has not shipped. To change an address or an item, raise a ticket before dispatch and we will do our best.'],
            ['Do you take bulk or project orders?', 'Yes. Architects, interior designers and businesses can raise a ticket with the products and quantities they need, and we will come back with availability and timelines.'],
        ]],
        'Payment and GST' => ['bi-credit-card', [
            ['Which payment methods do you accept?', 'Credit and debit cards, UPI, netbanking and cash on delivery where it is available for your address.'],
            ['Are the prices inclusive of GST?', 'Listed prices are before GST. The GST amount and the delivery charge are shown at checkout before you pay, so the total you see there is the total you are charged.'],
            ['Will I get a GST invoice?', 'Yes, with every order. It shows the GST charged on each item and on delivery, and you can open it from the order page in your account.'],
            ['Is it safe to pay online?', 'Online payments are handled on a licensed payment gateway\'s secure page. We never see or store your full card number.'],
            ['My payment was deducted but the order did not go through.', 'This is usually a bank timeout. The amount is normally reversed automatically within a few working days. If it is not, raise a ticket with the transaction reference from your bank.'],
        ]],
        'Delivery' => ['bi-truck', [
            ['How much is delivery?', 'Free on the standard rate for orders above ' . $shop['free_over'] . '. Below that, the standard charge starts at ' . $shop['fee'] . '. The exact charge for your address is shown at checkout.'],
            ['How long will my order take?', 'Items in stock usually ship in ' . $shop['dispatch_days'] . ' working days. Transit then takes about ' . $shop['delivery_metro'] . ' working days to large cities and ' . $shop['delivery_other'] . ' to other towns. Made-to-order pieces take longer and say so on the product page.'],
            ['How do I track my order?', 'Once it ships, the tracking number is on the order page in your account. You can also enter it on the Track order page.'],
            ['What should I check when it arrives?', 'Count the cartons, look for crushed or wet boxes and note any damage on the courier\'s receipt before signing. Then open and inspect everything within ' . $shop['damage_hours'] . ' hours.'],
            ['Is assembly included?', 'Standard delivery is to your doorstep and does not include assembly, unless the product page or the delivery option you chose says it does.'],
        ]],
        'Returns and refunds' => ['bi-arrow-return-left', [
            ['What if my furniture arrives damaged?', 'Raise a ticket within ' . $shop['damage_hours'] . ' hours with photos of the damage, the box and the carton label. Depending on the problem we send a replacement part, repair it, replace it or refund you.'],
            ['Can I return something I simply do not like?', 'Yes, within ' . $shop['return_days'] . ' days of delivery, if the item is eligible, unused, unassembled and in its original packaging. The return delivery cost is deducted from the refund. Made-to-order pieces are excluded.'],
            ['When will I get my refund?', 'Within ' . $shop['refund_days'] . ' working days of the return being approved, to the method you paid with. For cash on delivery orders we refund to your bank account or UPI ID.'],
            ['Can I return only part of an order?', 'Yes. Only the items you send back are credited, and a credit note is issued against your original invoice.'],
        ]],
        'Products and care' => ['bi-lamp', [
            ['Why does the colour look slightly different from the photo?', 'Screens vary, and so does natural material. Wood grain, stone veining and hand-applied finishes differ from piece to piece. If the difference is more than variation, tell us.'],
            ['How should I look after solid wood furniture?', 'Keep it out of direct sunlight and away from damp walls, wipe spills at once, use coasters and dust with a soft dry cloth. A light polish once or twice a year keeps the finish alive.'],
            ['Is there a warranty?', 'Where a product has a warranty, its length and what it covers are stated on the product page. Warranties cover manufacturing defects, not normal wear or damage after delivery.'],
        ]],
        'Selling with us' => ['bi-shop', [
            ['How do I become a seller?', 'Fill in the seller application with your business details. We review every application, and your store goes live once it is approved.'],
            ['Who can sell?', 'Manufacturers, workshops, brands and traders of furniture, lighting, textiles and home decor with a registered business. GSTIN and HSN details are added to the seller account after approval.'],
            ['How do sellers get paid?', 'Each order a seller fulfils is recorded in their seller dashboard, and payouts are made against those orders after the marketplace commission.'],
        ]],
    ];
    $links = ['Ordering' => null, 'Delivery' => 'shipping', 'Returns and refunds' => 'returns', 'Selling with us' => 'vendor.register'];
@endphp

@section('doc')
    <nav class="faq-jump" aria-label="FAQ topics">
        @foreach($faqs as $topic => [$icon, $items])
            <a href="#{{ \Illuminate\Support\Str::slug($topic) }}"><i class="bi {{ $icon }}"></i> {{ $topic }}</a>
        @endforeach
    </nav>

    @foreach($faqs as $topic => [$icon, $items])
        <section class="faq-group" id="{{ \Illuminate\Support\Str::slug($topic) }}">
            <h2><i class="bi {{ $icon }}"></i> {{ $topic }}</h2>
            @foreach($items as [$question, $answer])
                <details class="faq-item">
                    <summary>{{ $question }}</summary>
                    <p>{{ $answer }}</p>
                </details>
            @endforeach
            @if(! empty($links[$topic]))
                <a class="link-arrow faq-more" href="{{ route($links[$topic]) }}">{{ $topic === 'Selling with us' ? 'Apply to sell' : 'Read the full ' . strtolower($topic) . ' policy' }} <i class="bi bi-arrow-right"></i></a>
            @endif
        </section>
    @endforeach

    <div class="doc-cta">
        <div><strong>Did not find your answer?</strong><span>Send us the details and a member of the team will get back to you.</span></div>
        <a href="{{ route('contact-us') }}" class="btn btn-accent">Contact us</a>
    </div>
@endsection

@push('head')
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => collect($faqs)->flatMap(fn ($g) => $g[1])->map(fn ($qa) => ['@type' => 'Question', 'name' => $qa[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]]])->values()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
