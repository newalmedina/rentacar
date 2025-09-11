<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class AppointmentChangeStatusToWorkerMail extends Mailable
{
    use Queueable, SerializesModels;

    public $appointment;

    public function __construct($appointment)
    {
        $this->appointment = $appointment;
    }

    public function build()
    {
        return $this->subject("Cambio de estado de una cita asignada")
            ->from(config('mail.from.address'), Auth::user()->center->name)
            ->view('emails.appointment_change_status_worker');
    }
}
