<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\PDF;
use Illuminate\Support\Facades\Auth;

class AutomaticInvoicedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $pdf;
    public $order;
    public $center;

    /**
     * Create a new message instance.
     *
     * @param PDF $pdf
     * @param $order
     */
    public function __construct(PDF $pdf, $order, $center)
    {
        $this->pdf = $pdf;
        $this->order = $order;
        $this->center = $center;
    }

    public function build()
    {
        return $this->subject("Recibo para la orden {$this->order->code}")
            ->from(config('mail.from.address'), $this->center->name)
            ->view('emails.automatic_invoiced') // Puedes crear esta vista o usar texto plano
            ->attachData($this->pdf->output(), "{$this->order->code}.pdf", [
                'mime' => 'application/pdf',
            ]);
    }
}
