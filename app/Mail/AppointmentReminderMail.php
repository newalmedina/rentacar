<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;

class AppointmentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $appointment;
    public $hoursBefore;

    public function __construct(Appointment $appointment, int $hoursBefore)
    {
        $this->appointment = $appointment;
        $this->hoursBefore = $hoursBefore;
    }

    public function build()
    {
        $subject = "Recordatorio: tu cita es en {$this->hoursBefore} horas";

        return $this->subject($subject)
            ->from(config('mail.from.address'), Auth::user()->center->name)
            ->view('emails.appointment_reminder');
    }
}
