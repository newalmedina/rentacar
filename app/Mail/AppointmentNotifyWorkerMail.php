<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class AppointmentNotifyWorkerMail extends Mailable
{
    use Queueable, SerializesModels;

    public $appointment;

    public function __construct($appointmentId)
    {
        $this->appointment = Appointment::find($appointmentId);
    }


    public function build()
    {
        return $this->subject("Nueva cita asignada")
            ->from(config('mail.from.address'), Auth::user()->center->name)
            ->view('emails.appointment_notify_worker')
            ->with([
                'appointment' => $this->appointment
            ]);
    }
}
