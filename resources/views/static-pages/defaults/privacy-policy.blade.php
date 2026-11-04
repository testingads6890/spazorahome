@extends('static-pages.layout')

@section('title', 'Privacy policy - ' . $shop['name'])
@section('meta_description', 'What personal data ' . $shop['name'] . ' collects, why, who sees it and the choices you have.')
@section('crumb', 'Privacy policy')
@section('kicker', 'Legal')
@section('heading', 'Privacy policy')
@section('lead', 'We collect what we need to sell and deliver furniture, and nothing we would be uncomfortable explaining. This page says what, why and who sees it.')
@section('updated', $shop['updated'])

@section('doc')
    <h2>The short version</h2>
    <ul>
        <li>We use your details to take your order, deliver it, invoice it and support you afterwards.</li>
        <li>We do not sell your personal data to anyone.</li>
        <li>Card and UPI details are entered on our payment partner's secure page. We never see or store your full card number.</li>
        <li>You can ask to see, correct or delete your data at any time.</li>
    </ul>

    <h2>What we collect</h2>
    <div class="table-responsive">
        <table class="doc-table">
            <thead><tr><th>Information</th><th>When we get it</th><th>Why we need it</th></tr></thead>
            <tbody>
                <tr><td>Name, email and phone number</td><td>When you create an account or check out as a guest</td><td>To confirm orders, send delivery updates and reach you about a problem</td></tr>
                <tr><td>Delivery and billing addresses</td><td>At checkout</td><td>To deliver your order and print a correct GST invoice</td></tr>
                <tr><td>Order history, wishlist and compare list</td><td>As you use the site</td><td>To show your past orders, handle returns and keep your saved items</td></tr>
                <tr><td>Payment status and transaction reference</td><td>From our payment partner after you pay</td><td>To match the payment to your order and process refunds</td></tr>
                <tr><td>Messages, reviews and photos you send</td><td>When you raise a ticket or review a product</td><td>To resolve your issue and publish your review</td></tr>
                <tr><td>Device and usage data</td><td>Automatically, when you browse</td><td>To keep the site secure, fix errors and understand which pages are useful</td></tr>
            </tbody>
        </table>
    </div>

    <h2>Cookies</h2>
    <p>We use a small number of cookies that the site cannot work without. They keep you signed in, remember what is in your cart, protect forms from misuse and remember the language and currency you chose. You can block cookies in your browser, but the cart and checkout will stop working if you do.</p>

    <h2>Who we share it with</h2>
    <ul>
        <li><strong>The seller of your item.</strong> {{ $shop['name'] }} is a marketplace. The seller who packs your order sees the name, delivery address and phone number needed to ship it.</li>
        <li><strong>Courier and logistics partners.</strong> They receive your name, address and phone number to deliver the parcel.</li>
        <li><strong>Our payment partner.</strong> Online payments are processed by a licensed payment gateway, which handles your card, UPI or netbanking details under its own security standards.</li>
        <li><strong>Service providers.</strong> Companies that host the site and send our emails process data on our instructions only.</li>
        <li><strong>Authorities.</strong> When the law requires it, for example tax records or a valid legal order.</li>
    </ul>
    <p>We do not sell or rent personal data, and we do not share it with advertisers.</p>

    <h2>Marketing messages</h2>
    <p>We send the newsletter only to people who signed up for it. Every email has an unsubscribe link. Order, delivery and refund messages are not marketing and are sent for every order.</p>

    <h2>How long we keep it</h2>
    <ul>
        <li><strong>Account details:</strong> for as long as your account is open.</li>
        <li><strong>Invoices and order records:</strong> for the period tax law requires us to keep them, even if you close your account.</li>
        <li><strong>Support tickets:</strong> for as long as they are useful for handling warranty and return questions about that order.</li>
        <li><strong>Newsletter sign-ups:</strong> until you unsubscribe.</li>
    </ul>

    <h2>How we protect it</h2>
    <p>Passwords are stored in hashed form, never as plain text. Access to customer data is limited to staff and sellers who need it for a specific order. No system is perfectly secure, so please use a password you do not use elsewhere and tell us at once if you think your account has been accessed by someone else.</p>

    <h2>Your choices</h2>
    <ul>
        <li><strong>See and correct your data.</strong> Your profile, addresses and orders are visible in <a href="{{ route('customer.profile.edit') }}">your account</a>, where you can edit them.</li>
        <li><strong>Delete your account.</strong> Raise a ticket asking for deletion. We will remove what we are not legally required to keep.</li>
        <li><strong>Withdraw consent.</strong> Unsubscribe from marketing at any time.</li>
        <li><strong>Raise a concern.</strong> Write to us through the <a href="{{ route('contact-us') }}">contact page</a>@if($shop['email']) or at <a href="mailto:{{ $shop['email'] }}">{{ $shop['email'] }}</a>@endif. We respond to data requests as quickly as we can, and within the time the law allows.</li>
    </ul>

    <h2>Children</h2>
    <p>The site is meant for adults. We do not knowingly collect data from anyone under 18. Children's furniture is sold to the adults who buy it.</p>

    <h2>Changes to this policy</h2>
    <p>If we change how we use personal data, we will update this page and the date at the top.</p>
@endsection
