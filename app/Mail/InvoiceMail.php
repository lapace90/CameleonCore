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
            ->view('emails.invoice')
            ->with([
                'invoice' => $this->invoice,
                'customer' => $this->invoice->customer,
                'company' => [
                    'name' => 'CampCameleonX',
                    'email' => 'contact@campcameleonx.com',
                    'phone' => '+212 XXX XXX XXX',
                    'website' => 'https://campcameleonx.com'
                ]
            ])
            ->attach($this->pdfPath, [
                'as' => "facture_{$this->invoice->invoice_number}.pdf",
                'mime' => 'application/pdf',
            ]);
    }
}
