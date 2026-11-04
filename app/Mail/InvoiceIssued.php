<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\InvoiceBuilder;
use App\Services\InvoicePdf;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class InvoiceIssued extends Mailable
{
    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your tax invoice ' . $this->order->invoice_number . ' for order ' . $this->order->order_number);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-issued',
            with: [
                'order' => $this->order,
                'invoice' => app(InvoiceBuilder::class)->build($this->order),
                'url' => route('customer.orders.invoice', $this->order),
                'attached' => app(InvoicePdf::class)->available(),
                'numbers' => $this->order->supplierInvoices()->pluck('number')->all(),
            ],
        );
    }

    /** The invoice as a PDF, when the PDF library is installed. A PDF failure must not stop the email. */
    public function attachments(): array
    {
        $pdf = app(InvoicePdf::class);
        if (! $pdf->available()) {
            return [];
        }

        // One PDF per invoice: a single one normally, one per supplier when the order is invoiced per supplier.
        $builder = app(InvoiceBuilder::class);
        $supplierInvoices = $this->order->supplierInvoices()->with('vendor')->get();
        $documents = $supplierInvoices->isEmpty() ? [null] : $supplierInvoices->all();

        $attachments = [];
        foreach ($documents as $document) {
            try {
                $invoice = $builder->build($this->order, $document);
                $bytes = $pdf->render($this->order, $invoice);
            } catch (\Throwable $e) {
                report($e);

                continue;
            }
            $attachments[] = Attachment::fromData(fn () => $bytes, $pdf->filename($invoice))->withMime('application/pdf');
        }

        return $attachments;
    }
}
