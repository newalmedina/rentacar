<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class PendingAppointmentsToWorkerMail extends Mailable
{
    use Queueable, SerializesModels;

    public $worker;
    public $appointments;

    public function __construct($worker, $appointments)
    {
        $this->worker = $worker;
        $this->appointments = $appointments;
    }

    public function build()
    {
        return $this->subject("Tienes citas pendientes de confirmar")
            ->from(config('mail.from.address'), Auth::user()->center->name)
            ->view('emails.pending_appointments_worker');
    }
}
