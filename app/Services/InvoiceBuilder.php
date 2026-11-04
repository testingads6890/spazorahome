<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\Order;
use App\Models\SupplierInvoice;
use App\Support\GstState;

/**
 * Builds the GST breakup for an order from the tax snapshots saved at checkout.
 * Seller state == delivery state -> intra-state supply (CGST + SGST, half each); otherwise IGST.
 */
class InvoiceBuilder
{
    /**
     * @param  SupplierInvoice|null  $only  one supplier's invoice of an order invoiced per supplier: only that
     *                                      supplier's lines (plus shipping for the marketplace's own invoice)
     */
    public function build(Order $order, ?SupplierInvoice $only = null): array
    {
        $order->loadMissing(['items.vendor', 'user', 'shippingAddress', 'billingAddress']);

        $sellerCode = GstState::code(config('shop.seller.state'));
        $placeCode = $order->shippingAddress?->state_code;
        $marketplace = ['name' => config('shop.seller.name'), 'gstin' => config('shop.seller.gstin')];

        $lines = [];
        $itemTax = 0.0;
        $items = $only ? $order->items->filter(fn ($i) => (int) $i->vendor_id === (int) $only->vendor_id) : $order->items;
        foreach ($items as $item) {
            [$supplier, $intra] = $this->supplierFor($item, $marketplace, $sellerCode, $placeCode);

            $lines[] = $this->line($item->product_name, $item->product_sku, $item->hsn_code, (int) $item->quantity, (float) $item->unit_price, (float) $item->subtotal - (float) $item->discount_amount, $item->tax_rate, (float) $item->tax_amount, $intra, $supplier);
            $itemTax += (float) $item->tax_amount;
        }

        $shippingTax = (float) $order->shipping_tax;
        $shippingLine = (float) $order->shipping > 0 && (! $only || $only->vendor_id === null)
            ? $this->line('Shipping charges' . ($order->shipping_method ? ' (' . $order->shipping_method . ')' : ''), null, config('shop.shipping_sac'), 1, (float) $order->shipping, (float) $order->shipping, config('shop.shipping_tax_rate'), $shippingTax, $sellerCode !== null && $sellerCode === $placeCode, $marketplace)
            : null;

        // Orders placed before per-line tax was recorded: keep the invoice total correct.
        $legacy = round((float) $order->tax - $itemTax - $shippingTax, 2);
        $totals = ['taxable' => 0.0, 'cgst' => 0.0, 'sgst' => 0.0, 'igst' => 0.0];
        foreach (array_filter([...$lines, $shippingLine]) as $l) {
            $totals['taxable'] += $l['taxable'];
            $totals['cgst'] += $l['cgst'];
            $totals['sgst'] += $l['sgst'];
            $totals['igst'] += $l['igst'];
        }

        $all = array_values(array_filter([...$lines, $shippingLine]));
        $hasIntra = (bool) array_filter($all, fn ($l) => $l['intra']);
        $hasInter = (bool) array_filter($all, fn ($l) => ! $l['intra']);
        $vendorLines = array_filter($lines, fn ($l) => $l['supplier'] !== $marketplace);

        $invoice = [
            'title' => 'Tax Invoice',
            'number' => $order->invoice_number,
            'date' => $order->invoice_date ?? $order->created_at,
            'cancelled' => $order->status === 'cancelled',
            'partial_credit_notes' => $order->creditNotes()->where('is_full', false)->orderBy('id')->pluck('number')->all(),
            'seller' => config('shop.seller'),
            'intra_state' => $hasIntra && ! $hasInter,
            'has_intra' => $hasIntra,
            'has_inter' => $hasInter,
            'has_vendor_lines' => (bool) $vendorLines,
            'place_of_supply' => $order->is_export
                ? 'Outside India (96): ' . $order->shippingAddress?->country
                : ($placeCode ? GstState::name($placeCode) . " ({$placeCode})" : null),
            // Zero-rated export under a Letter of Undertaking: what the invoice must say, and what the card was charged.
            'export' => $order->is_export ? [
                'country' => $order->shippingAddress?->country,
                'lut' => config('shop.export.lut_number'),
                'iec' => config('shop.export.iec'),
                'origin' => $order->items->map(fn ($i) => $i->product?->country_of_origin)->filter()->unique()->implode(', ') ?: null,
                'weight' => $order->export_weight_kg,
                'charged' => $order->charge_currency ? $order->charge_currency . ' ' . number_format((float) $order->charge_total, 2) : null,
                'rate' => $order->charge_currency ? '1 INR = ' . rtrim(rtrim(number_format((float) $order->exchange_rate, 6), '0'), '.') . ' ' . $order->charge_currency : null,
            ] : null,
            'seller_state_code' => $sellerCode,
            'lines' => $lines,
            'shipping' => $shippingLine,
            'legacy_tax' => abs($legacy) >= 0.01 ? $legacy : 0.0,
            'totals' => array_map(fn ($v) => round($v, 2), $totals),
            'coupon' => $order->coupon_code,
            'discount' => (float) $order->discount,
            'total_tax' => (float) $order->tax,
            'grand_total' => (float) $order->total,
        ];

        if (! $only) {
            return $invoice;
        }

        // One supplier's own invoice: its number, its details as the seller, and totals of its own lines only.
        $vendor = $only->vendor;
        $tax = round(array_sum(array_column($all, 'tax')), 2);

        return array_merge($invoice, [
            'number' => $only->number,
            'date' => $only->issued_at,
            'partial_credit_notes' => $order->creditNotes()->where('supplier_invoice_id', $only->id)->where('is_full', false)->orderBy('id')->pluck('number')->all(),
            'closing_credit_note' => $order->creditNotes()->where('supplier_invoice_id', $only->id)->where('is_full', true)->value('number'),
            'seller' => $only->vendor_id ? [
                'name' => $vendor?->business_name,
                'gstin' => $vendor?->tax_number,
                'address' => trim(implode(', ', array_filter([$vendor?->address, $vendor?->city, $vendor?->postal_code]))),
                'state' => GstState::name($vendor?->state) ?? $vendor?->state,
            ] : config('shop.seller'),
            'operator' => $only->vendor_id ? $marketplace : null,
            'has_vendor_lines' => false,
            'legacy_tax' => 0.0,
            'discount' => round((float) $items->sum('discount_amount'), 2),
            'total_tax' => $tax,
            'grand_total' => round($invoice['totals']['taxable'] + $tax, 2),
        ]);
    }

    /**
     * A credit note against the invoice. The note that reverses an untouched invoice repeats the invoice's
     * own figures; a partial return (or the closing note after partial returns) lists only what it credits.
     */
    public function buildCreditNote(Order $order, ?CreditNote $note = null): array
    {
        $note ??= $order->creditNote ?? $order->creditNotes()->latest('id')->first();
        $supplierInvoice = $note->supplier_invoice_id ? $note->supplierInvoice : null;
        $base = $this->build($order, $supplierInvoice);
        $head = [
            'title' => 'Credit Note',
            'number' => $note->number,
            'date' => $note->issued_at,
            'against' => $supplierInvoice?->number ?? $order->invoice_number,
            'reason' => $note->reason,
            'cancelled' => false,
        ];

        $note->loadMissing('items.orderItem.vendor');
        $whole = ! $supplierInvoice && $note->is_full && $order->creditNotes()->count() === 1;
        if ($whole || ($note->items->isEmpty() && (float) $note->shipping <= 0)) {
            return array_merge($base, $head);
        }

        $sellerCode = GstState::code(config('shop.seller.state'));
        $placeCode = $order->shippingAddress?->state_code;
        $marketplace = ['name' => config('shop.seller.name'), 'gstin' => config('shop.seller.gstin')];

        $lines = [];
        foreach ($note->items as $row) {
            $item = $row->orderItem;
            [$supplier, $intra] = $this->supplierFor($item, $marketplace, $sellerCode, $placeCode);
            $lines[] = $this->line($item->product_name, $item->product_sku, $item->hsn_code, (int) $row->quantity, (float) $item->unit_price, (float) $row->taxable, $item->tax_rate, (float) $row->tax, $intra, $supplier);
        }
        $shippingLine = (float) $note->shipping > 0
            ? $this->line('Shipping charges', null, config('shop.shipping_sac'), 1, (float) $note->shipping, (float) $note->shipping, config('shop.shipping_tax_rate'), (float) $note->shipping_tax, $sellerCode !== null && $sellerCode === $placeCode, $marketplace)
            : null;

        $all = array_values(array_filter([...$lines, $shippingLine]));
        $totals = ['taxable' => 0.0, 'cgst' => 0.0, 'sgst' => 0.0, 'igst' => 0.0];
        foreach ($all as $l) {
            foreach (array_keys($totals) as $k) {
                $totals[$k] += $l[$k];
            }
        }
        $hasIntra = (bool) array_filter($all, fn ($l) => $l['intra']);
        $hasInter = (bool) array_filter($all, fn ($l) => ! $l['intra']);

        return array_merge($base, $head, [
            'partial' => ! $note->is_full,
            'intra_state' => $hasIntra && ! $hasInter,
            'has_intra' => $hasIntra,
            'has_inter' => $hasInter,
            'has_vendor_lines' => ! $supplierInvoice && (bool) array_filter($lines, fn ($l) => $l['supplier'] !== $marketplace),
            'lines' => $lines,
            'shipping' => $shippingLine,
            'legacy_tax' => 0.0,
            'totals' => array_map(fn ($v) => round($v, 2), $totals),
            'coupon' => null,
            'discount' => 0.0,
            'total_tax' => (float) $note->tax,
            'grand_total' => (float) $note->total,
        ]);
    }

    /**
     * Who supplied this line, and is it an intra-state supply? The supplier of a vendor's product is that
     * vendor, and the place of supply is compared with the supplier's own state.
     *
     * @return array{0: array{name: ?string, gstin: ?string}, 1: bool}
     */
    private function supplierFor($item, array $marketplace, ?string $sellerCode, ?string $placeCode): array
    {
        $vendor = $item->vendor;
        $supplier = $vendor
            ? ['name' => $vendor->business_name, 'gstin' => $vendor->tax_number]
            : $marketplace;
        $supplierCode = $vendor
            ? (GstState::codeFromGstin($vendor->tax_number) ?? GstState::code($vendor->state))
            : $sellerCode;

        return [$supplier, $supplierCode !== null && $supplierCode === $placeCode];
    }

    private function line(string $name, ?string $sku, ?string $hsn, int $qty, float $unit, float $taxable, $rate, float $tax, bool $intra, array $supplier): array
    {
        $half = round($tax / 2, 2);

        return [
            'name' => $name, 'sku' => $sku, 'hsn' => $hsn, 'supplier' => $supplier, 'intra' => $intra, 'qty' => $qty, 'unit_price' => $unit, 'taxable' => $taxable,
            'rate' => $rate === null ? null : (float) $rate, 'tax' => $tax,
            'cgst' => $intra ? $half : 0.0,
            'sgst' => $intra ? round($tax - $half, 2) : 0.0,
            'igst' => $intra ? 0.0 : $tax,
        ];
    }
}
