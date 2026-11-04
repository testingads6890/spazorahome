<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'user_id', 'order_number', 'status', 'subtotal', 'tax', 'shipping',
        'discount', 'shipping_tax', 'shipping_method', 'total', 'invoice_number', 'invoice_date', 'coupon_code', 'currency', 'is_export', 'charge_currency', 'charge_total', 'exchange_rate', 'export_weight_kg', 'billing_address_id', 'shipping_address_id', 'notes',
        'tracking_number', 'carrier', 'tracking_url', 'estimated_delivery', 'delivered_at',
    ];

    protected $casts = [
        'invoice_date' => 'datetime',
        'is_export' => 'boolean',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'delivered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function vendorOrders(): HasMany
    {
        return $this->hasMany(VendorOrder::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class);
    }

    protected static function booted(): void
    {
        // The tax invoice is issued when goods leave, whichever path moves the order to shipped/delivered.
        static::updated(function (Order $order) {
            if ($order->wasChanged('status') && in_array($order->status, ['shipped', 'delivered'], true)) {
                if ($order->issueInvoice()) {
                    $order->emailInvoice();
                }
            }
        });
    }

    /** Assign the next gap-free invoice number, once. Returns true only when this call issued it. */
    public function issueInvoice(): bool
    {
        return \Illuminate\Support\Facades\DB::transaction(function (): bool {
            $fresh = static::whereKey($this->id)->lockForUpdate()->first();
            if ($fresh->invoice_number) {
                return false;
            }

            $date = now();
            $number = config('shop.invoice_mode') === 'per_supplier'
                ? $fresh->issueSupplierInvoices($date)
                : \App\Models\InvoiceSequence::next($date);
            $fresh->forceFill(['invoice_number' => $number, 'invoice_date' => $date])->saveQuietly();
            $this->setRawAttributes(array_merge($this->getAttributes(), ['invoice_number' => $number, 'invoice_date' => $date->toDateTimeString()]));

            return true;
        });
    }

    /** Present only when the order was invoiced per supplier (SHOP_INVOICE_MODE=per_supplier at the time it shipped). */
    public function supplierInvoices(): HasMany
    {
        return $this->hasMany(SupplierInvoice::class)->orderByRaw('vendor_id is not null')->orderBy('id');
    }

    /**
     * One invoice per supplier: the marketplace (its own goods and the shipping charge) in the normal INV series,
     * and every vendor in its own series (V<vendor id>). Returns the number kept on the order itself.
     */
    private function issueSupplierInvoices(\Carbon\CarbonInterface $date): string
    {
        $vendorIds = $this->items()->whereNotNull('vendor_id')->distinct()->orderBy('vendor_id')->pluck('vendor_id');
        $ownGoods = $this->items()->whereNull('vendor_id')->exists();
        $numbers = [];

        if ($ownGoods || (float) $this->shipping > 0 || $vendorIds->isEmpty()) {
            $numbers[] = $this->supplierInvoices()->create(['vendor_id' => null, 'number' => InvoiceSequence::next($date), 'issued_at' => $date])->number;
        }
        foreach ($vendorIds as $vendorId) {
            $numbers[] = $this->supplierInvoices()->create(['vendor_id' => $vendorId, 'number' => InvoiceSequence::next($date, 'V' . $vendorId), 'issued_at' => $date])->number;
        }

        return $numbers[0];
    }

    /** Email the invoice to the customer. A mail failure must never block shipping, so it is only reported. */
    public function emailInvoice(): void
    {
        if (! $this->hasInvoice() || ! $this->user?->email) {
            return;
        }

        try {
            \Illuminate\Support\Facades\Mail::to($this->user->email)->send(new \App\Mail\InvoiceIssued($this));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** The credit note that closed the order (cancellation or full return). Partial returns are in creditNotes(). */
    public function creditNote(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CreditNote::class)->where('is_full', true);
    }

    /** Every credit note against this order's invoice: partial returns and the closing one. */
    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    /**
     * Credit note for everything not credited yet (gap-free CN series). With no earlier partial return this
     * reverses the whole invoice. No-op when never invoiced, already closed, or nothing is left to credit.
     */
    public function issueCreditNote(?string $reason = null): ?CreditNote
    {
        if (! $this->hasInvoice() || $this->creditNote()->exists()) {
            return null;
        }

        $remaining = [];
        foreach ($this->returnableQuantities() as $itemId => $qty) {
            if ($qty > 0) {
                $remaining[$itemId] = $qty;
            }
        }
        $shipping = ! $this->shippingCredited();
        if (! $remaining && ! ($shipping && (float) $this->shipping > 0)) {
            return null;
        }

        return $this->writeCreditNotes($remaining, $shipping, true, $reason)->first();
    }

    /**
     * Partial return after the invoice went out: credit some quantities (and optionally the shipping charge),
     * put the goods back in stock and shrink unpaid vendor payouts. The order itself stays as it is.
     * Returns the credit notes issued: one, or one per supplier when the order is invoiced per supplier.
     *
     * @param  array<int, int>  $quantities  order_item_id => quantity being returned
     */
    public function returnItems(array $quantities, bool $creditShipping = false, bool $restock = true, ?string $reason = null): ?\Illuminate\Support\Collection
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($quantities, $creditShipping, $restock, $reason) {
            $order = static::whereKey($this->id)->lockForUpdate()->first();
            if (! $order->hasInvoice() || ! in_array($order->status, ['shipped', 'delivered'], true) || $order->creditNote()->exists()) {
                return null;
            }

            $allowed = $order->returnableQuantities();
            $lines = [];
            foreach ($quantities as $itemId => $qty) {
                $qty = (int) $qty;
                if ($qty <= 0) {
                    continue;
                }
                if (! isset($allowed[$itemId]) || $qty > $allowed[$itemId]) {
                    return null;
                }
                $lines[(int) $itemId] = $qty;
            }
            $creditShipping = $creditShipping && (float) $order->shipping > 0 && ! $order->shippingCredited();
            if (! $lines && ! $creditShipping) {
                return null;
            }

            $notes = $order->writeCreditNotes($lines, $creditShipping, false, $reason);

            foreach (CreditNoteItem::whereIn('credit_note_id', $notes->pluck('id'))->with('orderItem')->get() as $line) {
                $item = $line->orderItem;
                if ($restock) {
                    $stockOwner = $item->variant_id ? ProductVariant::find($item->variant_id) : Product::find($item->product_id);
                    $stockOwner?->increment('stock_quantity', $line->quantity);
                }

                // The vendor is only paid for what the customer kept. Payouts already paid are left for the admin to recover.
                $vendorOrder = VendorOrder::where('order_item_id', $item->id)->where('payout_status', '!=', 'paid')->first();
                if ($vendorOrder && (float) $vendorOrder->subtotal > 0) {
                    $share = min(1, (float) $line->taxable / (float) $vendorOrder->subtotal);
                    $subtotal = round((float) $vendorOrder->subtotal - (float) $line->taxable, 2);
                    $commission = round((float) $vendorOrder->commission * (1 - $share), 2);
                    $tcs = round((float) $vendorOrder->tcs_amount * (1 - $share), 2);
                    $vendorOrder->update([
                        'subtotal' => max(0, $subtotal),
                        'commission' => $commission,
                        'tcs_amount' => $tcs,
                        'payout_amount' => max(0, round($subtotal - $commission - $tcs, 2)),
                        'payout_status' => $subtotal <= 0 ? 'failed' : $vendorOrder->payout_status,
                    ]);
                }
            }

            return $notes;
        });
    }

    /** order_item_id => quantity that can still be returned. */
    public function returnableQuantities(): array
    {
        $credited = CreditNoteItem::whereIn('credit_note_id', $this->creditNotes()->select('id'))
            ->selectRaw('order_item_id, sum(quantity) as qty')->groupBy('order_item_id')->pluck('qty', 'order_item_id');
        // A closing credit note from before partial returns existed has no lines: it covered everything.
        $closed = $this->creditNote()->exists();

        $left = [];
        foreach ($this->items()->get() as $item) {
            $left[$item->id] = $closed ? 0 : max(0, (int) $item->quantity - (int) ($credited[$item->id] ?? 0));
        }

        return $left;
    }

    public function shippingCredited(): bool
    {
        return $this->creditNotes()->where(fn ($q) => $q->where('shipping', '>', 0)->orWhere('is_full', true))->exists();
    }

    /** Total already credited back against the invoice. */
    public function creditedTotal(): float
    {
        return round((float) $this->creditNotes()->get()->sum(fn (CreditNote $n) => (float) ($n->total ?? $this->total)), 2);
    }

    /**
     * Writes the credit note(s) for these quantities. An order invoiced per supplier gets one note per supplier,
     * each against that supplier's own invoice and in that supplier's own series; otherwise there is one note.
     *
     * @param  array<int, int>  $quantities  order_item_id => quantity
     * @return \Illuminate\Support\Collection<int, CreditNote>
     */
    private function writeCreditNotes(array $quantities, bool $withShipping, bool $closing, ?string $reason): \Illuminate\Support\Collection
    {
        $date = now();
        $first = ! $this->creditNotes()->exists();
        $items = $this->items()->get()->keyBy('id');
        $done = CreditNoteItem::whereIn('credit_note_id', $this->creditNotes()->select('id'))
            ->selectRaw('order_item_id, sum(quantity) as qty, sum(taxable) as taxable, sum(tax) as tax')
            ->groupBy('order_item_id')->get()->keyBy('order_item_id');
        $invoices = $this->supplierInvoices()->get();
        $perSupplier = $invoices->isNotEmpty();

        // Work out every line first, then group them by supplier (one group, key 0, when there is a single invoice).
        $groups = [];
        foreach ($quantities as $itemId => $qty) {
            $item = $items[$itemId];
            $lineTaxable = round((float) $item->subtotal - (float) $item->discount_amount, 2);
            $lineTax = (float) $item->tax_amount;
            $before = $done[$itemId] ?? null;

            // The last units of a line take whatever is left, so paise never drift across several returns.
            if ((int) ($before->qty ?? 0) + $qty >= (int) $item->quantity) {
                $t = round($lineTaxable - (float) ($before->taxable ?? 0), 2);
                $x = round($lineTax - (float) ($before->tax ?? 0), 2);
            } else {
                $t = round($lineTaxable * $qty / (int) $item->quantity, 2);
                $x = round($lineTax * $qty / (int) $item->quantity, 2);
            }

            $groups[$perSupplier ? (int) $item->vendor_id : 0]['lines'][] = ['order_item_id' => $itemId, 'quantity' => $qty, 'taxable' => $t, 'tax' => $x];
        }
        if ($withShipping && (float) $this->shipping > 0) {
            $groups[0]['shipping'] = true; // shipping is the marketplace's own supply
        }
        ksort($groups);

        $notes = collect();
        foreach ($groups as $vendorId => $group) {
            $lines = $group['lines'] ?? [];
            $shipping = ! empty($group['shipping']) ? (float) $this->shipping : 0.0;
            $shippingTax = ! empty($group['shipping']) ? (float) $this->shipping_tax : 0.0;
            $taxable = array_sum(array_column($lines, 'taxable'));
            $tax = array_sum(array_column($lines, 'tax'));
            // Reversing an untouched single invoice: use the invoice's own totals so the two documents always agree.
            $whole = $closing && $first && ! $perSupplier;

            $note = $this->creditNotes()->create([
                'supplier_invoice_id' => $perSupplier ? $invoices->first(fn ($i) => (int) $i->vendor_id === $vendorId)?->id : null,
                'is_full' => $closing,
                'number' => InvoiceSequence::next($date, $perSupplier && $vendorId ? 'C' . $vendorId : 'CN'),
                'issued_at' => $date,
                'reason' => $reason,
                'shipping' => $shipping,
                'shipping_tax' => $shippingTax,
                'tax' => $whole ? (float) $this->tax : round($tax + $shippingTax, 2),
                'total' => $whole ? (float) $this->total : round($taxable + $tax + $shipping + $shippingTax, 2),
            ]);
            $note->items()->createMany($lines);
            $notes->push($note);
        }

        return $notes;
    }

    public function hasInvoice(): bool
    {
        return $this->invoice_number !== null;
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, ['pending', 'processing', 'ready_to_ship'], true);
    }

    /**
     * Cancel the order: return stock, void vendor payouts, close out payments and send the money back.
     * Idempotent, and refuses orders that already shipped unless an admin allows it.
     */
    public function cancel(bool $allowAfterShipment = false): bool
    {
        $done = \Illuminate\Support\Facades\DB::transaction(function () use ($allowAfterShipment) {
            $order = static::whereKey($this->id)->lockForUpdate()->first();
            $allowed = $order->isCancellable() || ($allowAfterShipment && in_array($order->status, ['shipped', 'delivered'], true));
            if (! $allowed) {
                return false;
            }

            // Units already taken back through a partial return are in stock again; only put back the rest.
            $returned = CreditNoteItem::whereIn('credit_note_id', $order->creditNotes()->select('id'))
                ->selectRaw('order_item_id, sum(quantity) as qty')->groupBy('order_item_id')->pluck('qty', 'order_item_id');
            foreach ($order->items()->get() as $item) {
                $qty = (int) $item->quantity - (int) ($returned[$item->id] ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                $stockOwner = $item->variant_id ? ProductVariant::find($item->variant_id) : Product::find($item->product_id);
                $stockOwner?->increment('stock_quantity', $qty);
            }

            $order->vendorOrders()->where('payout_status', '!=', 'paid')->update(['payout_status' => 'failed']);
            // That was a bulk update, so tell the seller wallet: whatever was earned on these lines is taken back.
            try {
                app(\App\Services\WalletService::class)->syncOrder($order->id);
            } catch (\Throwable $e) {
                report($e);
            }
            $order->payments()->where('status', 'pending')->update(['status' => 'failed']);
            $order->payments()->where('status', 'completed')->update(['status' => 'refunded']);
            if ($order->coupon_code) {
                // The coupon was not really used: give the slot back.
                Discount::whereRaw('upper(code) = ?', [strtoupper($order->coupon_code)])->where('used_count', '>', 0)->decrement('used_count');
            }
            $order->update(['status' => 'cancelled']);
            // An invoice already went out, so the tax must be reversed with a credit note.
            $order->issueCreditNote('Order cancelled / returned');
            $this->status = 'cancelled';

            return true;
        });

        if ($done) {
            // Outside the transaction: a slow or failing gateway must not hold locks or undo the cancellation.
            try {
                app(\App\Services\RefundService::class)->refundOrder($this, null, 'Order ' . $this->order_number . ' cancelled');
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $done;
    }
}
