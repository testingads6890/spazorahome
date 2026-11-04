<?php

return [
    // Google review link (Google Business profile). Empty = no button.
    'google_review_url' => env('SHOP_GOOGLE_REVIEW_URL', ''),

    // Seller store pages (/stores). Off until sellers have their own store addresses.
    'stores_enabled' => (bool) env('SHOP_STORES_ENABLED', false),


    /*
     * Bulk (quantity) pricing. The more units of one product (or one variant) in a line, the lower each unit costs.
     * default_tiers apply to every product that has not set its own: minimum quantity => percent off each unit.
     * Sellers and admins can set different tiers per product, or switch them off for a product.
     */
    // Seller withdrawal requests: smallest amount that can be asked for, and a flat fee per transfer (0 = free).
    'withdrawals' => [
        'minimum' => env('WITHDRAWAL_MINIMUM', 500),
        'fee' => env('WITHDRAWAL_FEE', 0),
    ],

    // Seller wallet: earnings from a delivered order stay "on hold" for this many days (the return window) before they can be withdrawn. 0 = available at once.
    'wallet' => [
        'hold_days' => env('WALLET_HOLD_DAYS', 0),
    ],

    'bulk_pricing' => [
        'enabled' => env('BULK_PRICING', true),
        'default_tiers' => [2 => 5, 5 => 10],
    ],

    // GST rate applied at checkout, in percent (most furniture is 18%).
    'tax_rate' => (float) env('SHOP_TAX_RATE', 18),

    // GST slabs vendors may choose from when listing a product.
    'gst_slabs' => [0, 5, 12, 18, 28],

    // SAC printed against the shipping line (courier / transport services).
    'shipping_sac' => env('SHOP_SHIPPING_SAC', '996812'),

    // GST charged on the shipping fee, in percent.
    'shipping_tax_rate' => (float) env('SHOP_SHIPPING_TAX_RATE', 18),

    // Flat shipping fee in INR, waived when the subtotal exceeds the threshold.
    'shipping_fee' => (float) env('SHOP_SHIPPING_FEE', 149),
    'free_shipping_over' => (float) env('SHOP_FREE_SHIPPING_OVER', 5000),

    // Who invoices vendor products.
    //   single       : one invoice per order from the marketplace, with the supplier shown on each line (default).
    //   per_supplier : the marketplace is an e-commerce operator; every vendor issues its own invoice and credit
    //                  notes in its own number series, and TCS is held back from vendor payouts.
    // Confirm with your CA before switching. Orders invoiced before the switch keep their single invoice.
    'invoice_mode' => env('SHOP_INVOICE_MODE', 'single'),

    // TCS (GST section 52) on the net taxable value of vendor sales, in percent. Only used in per_supplier mode.
    'tcs_rate' => (float) env('SHOP_TCS_RATE', 0.5),

    // Selling to customers outside India. Off by default: switch on only once you have a Letter of Undertaking (LUT),
    // shipping zones for the countries you deliver to, and international payments enabled on your Razorpay account.
    'export' => [
        'enabled' => (bool) env('SHOP_EXPORT_ENABLED', false),
        // Exports are invoiced without GST (zero-rated supply under LUT). The LUT reference (ARN) is printed on the invoice.
        'lut_number' => env('SHOP_LUT_NUMBER', ''),
        // Importer Exporter Code, printed on export invoices.
        'iec' => env('SHOP_IEC', ''),
        // Charge international orders in the currency the visitor picked (Admin > Currencies) instead of rupees.
        'charge_foreign_currency' => (bool) env('SHOP_EXPORT_CHARGE_CURRENCY', true),
    ],

    // Seller details printed on invoices. The state decides CGST+SGST (same state) vs IGST (other states).
    'seller' => [
        'name' => env('SHOP_SELLER_NAME', 'SpazoraHome'),
        'gstin' => env('SHOP_SELLER_GSTIN', ''),
        'address' => env('SHOP_SELLER_ADDRESS', ''),
        'state' => env('SHOP_SELLER_STATE', 'Maharashtra'),
    ],

    // Customer support details shown on the Contact page. Leave one empty to hide it.
    'contact' => [
        'email' => env('SHOP_CONTACT_EMAIL', ''),
        'phone' => env('SHOP_CONTACT_PHONE', ''),
        'hours' => env('SHOP_CONTACT_HOURS', 'Monday to Saturday, 10 am to 6 pm IST'),
        // WhatsApp number with country code, digits only (for example 919660533307).
        'whatsapp' => env('CONTACT_WHATSAPP', ''),
    ],

    // Social pages for the floating buttons and the footer. Empty = that button is not shown.
    'social' => [
        'instagram' => env('CONTACT_INSTAGRAM', ''),
        'facebook' => env('CONTACT_FACEBOOK', ''),
    ],

    // Figures quoted on the Shipping, Returns, Terms and FAQ pages. Change them here and every page follows.
    'policies' => [
        'return_days' => (int) env('SHOP_RETURN_DAYS', 7),                  // days after delivery to ask for a return
        'damage_report_hours' => (int) env('SHOP_DAMAGE_REPORT_HOURS', 48), // hours after delivery to report damage
        'refund_days' => env('SHOP_REFUND_DAYS', '5 to 7'),                 // working days for a refund to arrive
        'dispatch_days' => env('SHOP_DISPATCH_DAYS', '2 to 4'),             // working days before an in-stock item ships
        'delivery_days_metro' => env('SHOP_DELIVERY_DAYS_METRO', '5 to 8'),
        'delivery_days_other' => env('SHOP_DELIVERY_DAYS_OTHER', '7 to 14'),
        'updated' => env('SHOP_POLICIES_UPDATED', '11 October 2026'),       // "Last updated" date printed on the policies
    ],
];
