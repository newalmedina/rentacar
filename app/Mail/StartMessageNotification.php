<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;

class StartMessageNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $center;

    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->center = $order->center;
    }

    public function build()
    {
        return $this->subject("Todo lo que tienes que saber de tu alquiler")
            ->view('emails.start_message');
    }
}
