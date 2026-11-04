@extends('static-pages.layout')

@section('title', 'Terms of use - ' . $shop['name'])
@section('meta_description', 'The terms that apply when you browse, buy or sell on ' . $shop['name'] . '.')
@section('crumb', 'Terms of use')
@section('kicker', 'Legal')
@section('heading', 'Terms of use')
@section('lead', 'The agreement between you and ' . $shop['name'] . ' when you use this site. We have kept it as plain as a legal page can be.')
@section('updated', $shop['updated'])

@section('doc')
    <h2>1. About these terms</h2>
    <p>These terms apply to everyone who browses or buys on {{ $shop['name'] }}. By placing an order or creating an account you agree to them, together with our <a href="{{ route('privacy') }}">privacy policy</a>, <a href="{{ route('shipping') }}">shipping policy</a> and <a href="{{ route('returns') }}">returns policy</a>. Sellers are additionally bound by the terms of their seller account.</p>

    <h2>2. Who sells what</h2>
    <p>{{ $shop['name'] }} is an online marketplace. Products are listed by independent sellers that we review before they go live, and each product page names its seller. We run the site, take payment, coordinate delivery and handle support for every order.</p>

    <h2>3. Your account</h2>
    <ul>
        <li>You must be at least 18 and able to enter a contract under Indian law.</li>
        <li>Give accurate details and keep them up to date, especially your phone number and delivery address.</li>
        <li>Keep your password private. You are responsible for orders placed from your account.</li>
        <li>You can also check out as a guest. The same terms apply.</li>
    </ul>

    <h2>4. Products and descriptions</h2>
    <p>Sellers describe their products as accurately as they can. Even so, screens show colour differently, handmade and natural materials vary from piece to piece, and dimensions can differ by a small tolerance. If what arrives is materially different from the listing, the <a href="{{ route('returns') }}">returns policy</a> applies.</p>

    <h2>5. Prices, GST and payment</h2>
    <ul>
        <li>Prices are in Indian rupees. Listed prices are before GST; GST and delivery charges are added and shown at checkout before you pay.</li>
        <li>You can pay by card, UPI, netbanking or cash on delivery where it is offered. Online payments are processed by a licensed payment gateway.</li>
        <li>A GST invoice is issued for every order. You can open it from the order page in your account.</li>
        <li>If a product is listed at an obviously wrong price because of an error, we may cancel that order and refund you in full.</li>
        <li>Coupons and offers have their own conditions, cannot be exchanged for cash and can be withdrawn at any time for future orders.</li>
    </ul>

    <h2>6. When your order is accepted</h2>
    <p>Your order is an offer to buy. The confirmation email shows we have received it. The contract is formed when the order is dispatched. Until then we may decline or cancel an order, for example if an item is out of stock, the address cannot be served or the payment cannot be verified. If we do, anything you paid is refunded.</p>

    <h2>7. Delivery</h2>
    <p>Delivery times are estimates. Risk in the goods passes to you when they are delivered to the address on the order. The <a href="{{ route('shipping') }}">shipping policy</a> sets out charges, timelines and what to check on delivery.</p>

    <h2>8. Cancellations, returns and refunds</h2>
    <p>You can cancel an order before it ships. After delivery, you have {{ $shop['return_days'] }} days to request a return for eligible items. The details, including what cannot be returned, are in the <a href="{{ route('returns') }}">returns policy</a>.</p>

    <h2>9. Reviews and content you post</h2>
    <p>Reviews should be honest and about a product you bought. Do not post anything unlawful, abusive, misleading or that infringes someone else's rights. By posting a review or photo you let us display it on the site. We may remove content that breaks these rules.</p>

    <h2>10. Acceptable use</h2>
    <p>Do not misuse the site: no attempts to break its security, scrape it in bulk, place fraudulent orders, or resell products in a way that misrepresents their origin. We may suspend accounts that do.</p>

    <h2>11. Our content</h2>
    <p>The site design, text, logos and software belong to {{ $shop['name'] }} or its licensors. Product photographs and descriptions belong to the sellers who provided them. You may not copy them for commercial use without permission.</p>

    <h2>12. Warranties</h2>
    <p>Where a product carries a manufacturer's or seller's warranty, its length and conditions are stated on the product page. Warranties cover manufacturing defects, not normal wear, misuse, water damage or changes made after delivery.</p>

    <h2>13. Our liability</h2>
    <p>We are responsible for delivering what you ordered in the condition described, and for putting it right when we do not. To the extent the law allows, our total liability for any order is limited to the amount you paid for it, and we are not liable for indirect losses such as loss of income. Nothing in these terms limits rights that consumer law says cannot be limited.</p>

    <h2>14. Governing law</h2>
    <p>These terms are governed by the laws of India. Disputes are subject to the jurisdiction of the courts in {{ $shop['state'] }}, India. Please <a href="{{ route('contact-us') }}">contact us</a> first; most problems are solved faster by a ticket than by a notice.</p>

    <h2>15. Changes</h2>
    <p>We may update these terms as the site changes. The version on this page on the day you place an order is the one that applies to that order.</p>
@endsection
