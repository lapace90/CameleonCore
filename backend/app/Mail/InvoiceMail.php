<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Mail pour l'envoi de factures
 */
class InvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $invoice;
    public $pdfPath;

    public function __construct(Invoice $invoice, string $pdfPath)
    {
        $this->invoice = $invoice;
        $this->pdfPath = $pdfPath;
    }

    public function build()
    {
        $subject = "Facture {$this->invoice->invoice_number} - CampCameleonX";

        return $this->subject($subject)
            ->view('email.invoices.email')
            ->with([
                'invoice_number' => $this->invoice->invoice_number,
                'customer' => [
                    'name' => $this->invoice->customer->first_name ?? '',
                    'last_name' => $this->invoice->customer->last_name ?? '',
                ],
                'dates' => [
                    'issue_date' => $this->invoice->issue_date->format('d/m/Y'),
                    'due_date' => $this->invoice->due_date->format('d/m/Y'),
                ],
                'status' => [
                    'code' => $this->invoice->payment_status,
                ],
                'amount' => [
                    'formatted' => number_format($this->invoice->total_amount, 2, ',', ' ') . ' €',
                ],
                'reservation' => $this->invoice->reservation ? [
                    'product' => $this->invoice->reservation->product->name ?? 'Séjour',
                    'checkin' => $this->invoice->reservation->start_date->format('d/m/Y'),
                    'checkout' => $this->invoice->reservation->end_date->format('d/m/Y'),
                ] : null,
            ])
            ->attach($this->pdfPath, [
                'as' => "facture_{$this->invoice->invoice_number}.pdf",
                'mime' => 'application/pdf',
            ]);
    }
}
