<?php

namespace App\Services;

use App\Models\Order;

/**
 * Turns the invoice / credit note view into a PDF with dompdf.
 * The library is optional: until `composer require dompdf/dompdf` has been run, available() is false
 * and callers fall back to the printable HTML page.
 */
class InvoicePdf
{
    public function available(): bool
    {
        return class_exists(\Dompdf\Dompdf::class);
    }

    /** PDF bytes for an invoice array built by InvoiceBuilder. */
    public function render(Order $order, array $invoice): string
    {
        $html = view('invoices.show', ['order' => $order, 'invoice' => $invoice, 'pdf' => true])->render();

        $work = storage_path('app/dompdf');
        if (! is_dir($work)) {
            @mkdir($work, 0775, true);
        }

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans'); // has the rupee sign
        if (is_dir($work) && is_writable($work)) {
            $options->set('fontCache', $work);
            $options->set('tempDir', $work);
        }

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        return (string) $dompdf->output();
    }

    /** Safe download name, e.g. INV-26-27-000001.pdf */
    public function filename(array $invoice): string
    {
        return preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $invoice['number']) . '.pdf';
    }

    public function download(Order $order, array $invoice)
    {
        return response($this->render($order, $invoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $this->filename($invoice) . '"',
        ]);
    }
}
