@php $addr = $order->shippingAddress; $kg = fn ($v) => $v === null ? '-' : rtrim(rtrim(number_format((float) $v, 2), '0'), '.') . ' kg'; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Packing list {{ $order->order_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; color: #222; margin: 24px auto; max-width: 900px; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: right; }
        th:first-child, td:first-child, th.l, td.l { text-align: left; }
        thead th { background: #f3f3f3; }
        table.meta td { border: 0; padding: 0 12px 0 0; text-align: left; vertical-align: top; width: 33%; }
        .total td { font-weight: bold; }
        .muted { color: #666; font-size: 12px; }
        .no-print button { margin-bottom: 12px; padding: 6px 12px; border: 1px solid #888; border-radius: 4px; background: #fff; font: inherit; cursor: pointer; }
        @media print { .no-print { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="no-print"><button onclick="window.print()">Print</button></div>
    <h1>Packing list{{ $order->is_export ? ' (Export)' : '' }}</h1>
    <div class="muted">Order {{ $order->order_number }} &middot; {{ $order->created_at->format('d M Y') }}@if($order->invoice_number) &middot; Invoice {{ $order->invoice_number }}@endif</div>

    <table class="meta">
        <tr>
            <td><strong>Exporter / shipper</strong><br>{{ config('shop.seller.name') }}<br>{{ config('shop.seller.address') }}
                @if(config('shop.seller.gstin'))<br>GSTIN: {{ config('shop.seller.gstin') }}@endif
                @if($order->is_export && config('shop.export.iec'))<br>IEC: {{ config('shop.export.iec') }}@endif</td>
            <td><strong>Consignee</strong><br>{{ $addr?->full_name }}@if($addr?->company)<br>{{ $addr->company }}@endif<br>{{ $addr?->address_line_1 }}@if($addr?->address_line_2), {{ $addr->address_line_2 }}@endif
                <br>{{ $addr?->city }}, {{ $addr?->state }} {{ $addr?->postal_code }}<br>{{ $addr?->country }}@if($addr?->phone)<br>Phone: {{ $addr->phone }}@endif</td>
            <td><strong>Shipment</strong><br>Country of destination: {{ $addr?->country }}@if($order->shipping_method)<br>Delivery: {{ $order->shipping_method }}@endif
                @if($order->carrier)<br>Carrier: {{ $order->carrier }}@endif @if($order->tracking_number)<br>Tracking: {{ $order->tracking_number }}@endif</td>
        </tr>
    </table>

    <table>
        <thead><tr><th>#</th><th class="l">Description</th><th class="l">SKU</th><th class="l">HS code</th><th class="l">Country of origin</th><th>Qty</th><th>Unit weight</th><th>Total weight</th></tr></thead>
        <tbody>
        @foreach($rows as $n => $row)
            <tr><td>{{ $n + 1 }}</td><td class="l">{{ $row['name'] }}</td><td class="l">{{ $row['sku'] }}</td><td class="l">{{ $row['hs'] ?: '-' }}</td><td class="l">{{ $row['origin'] }}</td>
                <td>{{ $row['qty'] }}</td><td>{{ $kg($row['unit']) }}</td><td>{{ $kg($row['total']) }}</td></tr>
        @endforeach
        <tr class="total"><td colspan="5">Total</td><td>{{ $rows->sum('qty') }}</td><td></td><td>{{ $kg($rows->sum('total')) }}</td></tr>
        </tbody>
    </table>
    @if($rows->contains(fn ($r) => $r['unit'] === null))<p class="muted">Items shown with "-" have no packed weight on the product, so the total weight leaves them out.</p>@endif
    <p class="muted" style="margin-top: 28px">Number of packages: ____________ &nbsp;&nbsp; Gross weight: ____________ &nbsp;&nbsp; Dimensions: ____________</p>
    <p class="muted">Declared by {{ config('shop.seller.name') }}: the goods above are as described. Signature: ____________________</p>
</body>
</html>
