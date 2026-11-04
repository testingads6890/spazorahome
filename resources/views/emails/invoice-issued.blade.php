@php $inr = fn ($v) => '₹' . number_format($v, 2); @endphp
<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #222;">
    <p>Hi {{ $order->user?->name }},</p>
    <p>Your order <strong>{{ $order->order_number }}</strong> has shipped. @if(count($numbers ?? []) > 1)Each seller has issued its own tax invoice: <strong>{{ implode(', ', $numbers) }}</strong>, dated {{ $invoice['date']->format('d M Y') }}. The totals below cover the whole order.@else Here is your tax invoice <strong>{{ $invoice['number'] }}</strong> dated {{ $invoice['date']->format('d M Y') }}.@endif</p>

    <table cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; border-color: #ccc;">
        <tr><td>Taxable value</td><td align="right">{{ $inr($invoice['totals']['taxable']) }}</td></tr>
        @if($invoice['has_intra'])
            <tr><td>CGST</td><td align="right">{{ $inr($invoice['totals']['cgst']) }}</td></tr>
            <tr><td>SGST</td><td align="right">{{ $inr($invoice['totals']['sgst']) }}</td></tr>
        @endif
        @if($invoice['has_inter'])
            <tr><td>IGST</td><td align="right">{{ $inr($invoice['totals']['igst']) }}</td></tr>
        @endif
        <tr><td><strong>Invoice total</strong></td><td align="right"><strong>{{ $inr($invoice['grand_total']) }}</strong></td></tr>
    </table>

    <p>@if(! empty($attached))The invoice is attached as a PDF. @endif<a href="{{ $url }}">View or print the full invoice</a> (log in required).</p>
    <p>{{ $invoice['seller']['name'] }}</p>
</body>
</html>
