<?php

namespace App\Http\Controllers\Concerns;

use App\Models\CreditNote;
use App\Models\Order;
use App\Models\SupplierInvoice;
use App\Services\InvoiceBuilder;
use App\Services\InvoicePdf;

/**
 * Invoice and credit note pages (printable HTML, plus a PDF download when dompdf is installed),
 * shared by the admin panel and the customer account. $area is the route-name prefix: "admin" or "customer".
 */
trait ShowsInvoices
{
    protected function invoiceDocument(Order $order, string $area, bool $pdf, ?SupplierInvoice $invoice = null)
    {
        // No invoice until the goods have shipped; a cancelled order keeps its number (a credit note is then required).
        abort_unless($order->hasInvoice(), 404);
        abort_if($invoice && $invoice->order_id !== $order->id, 404);

        // An order invoiced per supplier has several invoices; without a specific one, show the first.
        $all = $order->supplierInvoices()->with('vendor')->get();
        $invoice ??= $all->first();
        if (! $invoice) {
            return $this->document($order, app(InvoiceBuilder::class)->build($order), $pdf, route("{$area}.orders.invoice", $order), route("{$area}.orders.invoice.pdf", $order));
        }

        $others = $all->where('id', '!=', $invoice->id)
            ->map(fn (SupplierInvoice $i) => ['label' => $i->number . ' (' . $i->supplierName() . ')', 'url' => route("{$area}.orders.invoices.show", [$order, $i])])->values()->all();

        return $this->document(
            $order,
            app(InvoiceBuilder::class)->build($order, $invoice),
            $pdf,
            route("{$area}.orders.invoices.show", [$order, $invoice]),
            route("{$area}.orders.invoices.pdf", [$order, $invoice]),
            $others,
        );
    }

    protected function creditNoteDocument(Order $order, ?CreditNote $note, string $area, bool $pdf)
    {
        // Without a specific note: the one that closed the order, else the latest partial return.
        $named = $note !== null;
        $note ??= $order->creditNote ?? $order->creditNotes()->latest('id')->first();
        abort_unless($note && $note->order_id === $order->id, 404);

        return $this->document(
            $order,
            app(InvoiceBuilder::class)->buildCreditNote($order, $note),
            $pdf,
            $named ? route("{$area}.orders.credit-notes.show", [$order, $note]) : route("{$area}.orders.credit-note", $order),
            $named ? route("{$area}.orders.credit-notes.pdf", [$order, $note]) : route("{$area}.orders.credit-note.pdf", $order),
        );
    }

    private function document(Order $order, array $invoice, bool $pdf, string $htmlUrl, string $pdfUrl, array $others = [])
    {
        $service = app(InvoicePdf::class);

        if ($pdf) {
            return $service->available()
                ? $service->download($order, $invoice)
                : redirect($htmlUrl);
        }

        return view('invoices.show', ['order' => $order, 'invoice' => $invoice, 'pdfUrl' => $service->available() ? $pdfUrl : null, 'others' => $others]);
    }
}
