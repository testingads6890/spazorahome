@php
    $showIntra = $invoice['has_intra'];
    $showInter = $invoice['has_inter'];
    $cols = ($showIntra ? 2 : 0) + ($showInter ? 1 : 0);
    $t = $invoice['totals'];
    $inr = fn ($v) => '₹' . number_format($v, 2);
    $pct = fn ($v) => $v === null ? '-' : rtrim(rtrim(number_format($v, 2), '0'), '.') . '%';
    $rows = $invoice['shipping'] ? [...$invoice['lines'], $invoice['shipping']] : $invoice['lines'];
    $addr = $order->shippingAddress;
    $pdf = $pdf ?? false;
    $isNote = $invoice['title'] === 'Credit Note';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice['title'] }} {{ $invoice['number'] }}</title>
    <style>
        @if($pdf)
        @page { margin: 28px 30px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #222; }
        @else
        body { font-family: Arial, sans-serif; font-size: 13px; color: #222; margin: 24px auto; max-width: 900px; }
        @endif
        h1 { font-size: 22px; margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: right; }
        th:first-child, td:first-child { text-align: left; }
        thead th { background: #f3f3f3; }
        table.meta { margin-top: 12px; }
        table.meta td { border: 0; padding: 0 12px 0 0; text-align: left; vertical-align: top; width: 33%; }
        .total td { font-weight: bold; }
        .muted { color: #666; font-size: 12px; }
        .no-print { margin-bottom: 12px; }
        .no-print a, .no-print button { display: inline-block; margin-right: 8px; padding: 6px 12px; border: 1px solid #888; border-radius: 4px; background: #fff; color: #222; font: inherit; text-decoration: none; cursor: pointer; }
        @media print { .no-print { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    @unless($pdf)
        <div class="no-print">
            <button onclick="window.print()">Print</button>
            @if(! empty($pdfUrl))<a href="{{ $pdfUrl }}">Download PDF</a>@endif
            @foreach($others ?? [] as $other)<a href="{{ $other['url'] }}">Other invoice of this order: {{ $other['label'] }}</a>@endforeach
        </div>
    @endunless

    @php $closing = array_key_exists('closing_credit_note', $invoice) ? $invoice['closing_credit_note'] : $order->creditNote?->number; @endphp
    @if($invoice['cancelled'])
        <p style="color:#b00;font-weight:bold">This order was cancelled after the invoice was issued.@if($closing) Credit note {{ $closing }} reverses it.@endif</p>
    @endif
    @if(! $isNote && ! empty($invoice['partial_credit_notes']))
        <p style="color:#b00">Part of this invoice has been credited back. Credit {{ count($invoice['partial_credit_notes']) > 1 ? 'notes' : 'note' }}: {{ implode(', ', $invoice['partial_credit_notes']) }}.</p>
    @endif
    <h1>{{ $invoice['title'] }}@if(! empty($invoice['export'])) (Export)@endif</h1>
    <div class="muted">{{ $isNote ? 'Credit note' : 'Invoice' }} no. {{ $invoice['number'] }} &middot; Date {{ $invoice['date']->format('d M Y') }}
        @if(isset($invoice['against'])) &middot; Against invoice {{ $invoice['against'] }}@if($invoice['reason']) &middot; {{ $invoice['reason'] }}@endif @endif</div>

    @if(! empty($invoice['export']))
        <p style="margin: 10px 0 0"><strong>Supply meant for export under Letter of Undertaking without payment of integrated tax (IGST).</strong>
            @if($invoice['export']['lut'])LUT / ARN: {{ $invoice['export']['lut'] }}.@endif
            @if($invoice['export']['iec'])IEC: {{ $invoice['export']['iec'] }}.@endif
            Country of destination: {{ $invoice['export']['country'] }}.
            @if(! empty($invoice['export']['origin']))Country of origin of goods: {{ $invoice['export']['origin'] }}.@endif
            @if(! empty($invoice['export']['weight']))Packed weight: {{ rtrim(rtrim(number_format((float) $invoice['export']['weight'], 2), '0'), '.') }} kg.@endif</p>
    @endif

    <table class="meta">
        <tr>
            <td>
                <strong>Sold by</strong><br>
                {{ $invoice['seller']['name'] }}<br>
                @if($invoice['seller']['address']){{ $invoice['seller']['address'] }}<br>@endif
                {{ $invoice['seller']['state'] }}<br>
                @if($invoice['seller']['gstin'])GSTIN: {{ $invoice['seller']['gstin'] }}@endif
                @if(! empty($invoice['operator']))<br><span class="muted">Sold through {{ $invoice['operator']['name'] }} (e-commerce operator)@if($invoice['operator']['gstin']), GSTIN {{ $invoice['operator']['gstin'] }}@endif</span>@endif
            </td>
            <td>
                <strong>Billed / shipped to</strong><br>
                {{ $addr?->full_name ?? $order->user?->name }}<br>
                @if($addr){{ $addr->address_line_1 }}@if($addr->address_line_2), {{ $addr->address_line_2 }}@endif<br>
                {{ $addr->city }}, {{ $addr->state }} {{ $addr->postal_code }}<br>{{ $addr->country }}@endif
            </td>
            <td>
                <strong>Order</strong><br>{{ $order->order_number }}<br>
                <strong>Place of supply</strong><br>{{ $invoice['place_of_supply'] ?? ($addr?->state ? $addr->state . ' (unrecognised state, treated as inter-state)' : '-') }}<br>
                <span class="muted">{{ ! empty($invoice['export']) ? 'Export: zero-rated, no GST charged' : ($showIntra && $showInter ? 'Mixed suppliers: CGST + SGST or IGST per line' : ($showIntra ? 'Intra-state: CGST + SGST' : 'Inter-state: IGST')) }}</span>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Item</th><th>HSN/SAC</th><th>Qty</th><th>Rate</th><th>Taxable value</th><th>GST %</th>
                @if($showIntra)<th>CGST</th><th>SGST</th>@endif @if($showInter)<th>IGST</th>@endif
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $r)
                <tr>
                    <td>{{ $r['name'] }}@if($r['sku'])<br><span class="muted">{{ $r['sku'] }}</span>@endif
                        @if($invoice['has_vendor_lines'])<br><span class="muted">Supplier: {{ $r['supplier']['name'] }}@if($r['supplier']['gstin']) &middot; GSTIN {{ $r['supplier']['gstin'] }}@else &middot; GSTIN not provided @endif</span>@endif</td>
                    <td>{{ $r['hsn'] ?: '-' }}</td>
                    <td>{{ $r['qty'] }}</td>
                    <td>{{ $inr($r['unit_price']) }}</td>
                    <td>{{ $inr($r['taxable']) }}</td>
                    <td>{{ $pct($r['rate']) }}</td>
                    @if($showIntra)<td>{{ $inr($r['cgst']) }}</td><td>{{ $inr($r['sgst']) }}</td>@endif @if($showInter)<td>{{ $inr($r['igst']) }}</td>@endif
                    <td>{{ $inr($r['taxable'] + $r['tax']) }}</td>
                </tr>
            @endforeach
            @if($invoice['legacy_tax'] != 0)
                <tr><td>Tax (order placed before itemised GST)</td><td></td><td></td><td></td><td></td><td>-</td>
                    <td colspan="{{ $cols }}">{{ $inr($invoice['legacy_tax']) }}</td><td>{{ $inr($invoice['legacy_tax']) }}</td></tr>
            @endif
            <tr class="total">
                <td>Total</td><td></td><td></td><td></td><td>{{ $inr($t['taxable']) }}</td><td></td>
                @if($showIntra)<td>{{ $inr($t['cgst']) }}</td><td>{{ $inr($t['sgst']) }}</td>@endif @if($showInter)<td>{{ $inr($t['igst']) }}</td>@endif
                <td>{{ $inr($invoice['grand_total']) }}</td>
            </tr>
        </tbody>
    </table>

    <table style="width: 320px; margin-left: auto;">
        <tr><td>Taxable value</td><td>{{ $inr($t['taxable']) }}</td></tr>
        @if($showIntra)
            <tr><td>CGST</td><td>{{ $inr($t['cgst']) }}</td></tr>
            <tr><td>SGST</td><td>{{ $inr($t['sgst']) }}</td></tr>
        @endif
        @if($showInter)
            <tr><td>IGST</td><td>{{ $inr($t['igst']) }}</td></tr>
        @endif
        @if(($invoice['discount'] ?? 0) > 0)
            <tr><td>Discount already applied {{ $invoice['coupon'] ? '(' . $invoice['coupon'] . ')' : '' }}</td><td>{{ $inr($invoice['discount']) }}</td></tr>
        @endif
        <tr><td>Total GST</td><td>{{ $inr($invoice['total_tax']) }}</td></tr>
        <tr class="total"><td>{{ $isNote ? 'Credit total' : 'Invoice total' }}</td><td>{{ $inr($invoice['grand_total']) }}</td></tr>
    </table>

    @if(! empty($invoice['export']['charged']))
        <p>Amounts are in Indian rupees. The customer's card was charged <strong>{{ $invoice['export']['charged'] }}</strong> for the order ({{ $invoice['export']['rate'] }}).</p>
    @endif
    <p class="muted">This is a computer-generated {{ $isNote ? 'credit note' : 'invoice' }}.</p>
</body>
</html>
