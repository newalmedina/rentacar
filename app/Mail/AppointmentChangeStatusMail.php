<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class AppointmentChangeStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public $appointment; // atributo público para acceder desde la vista

    public function __construct($appointment)
    {
        $this->appointment = $appointment;
    }

    public function build()
    {
        return $this->subject("Su cita ha cambiado de estado")
            ->from(config('mail.from.address'), Auth::user()->center->name)
            ->view('emails.appointment_change_status'); // la vista que vamos a crear
    }
}
