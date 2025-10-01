<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;

class EndMessageNotification extends Mailable
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
        return $this->subject("Tu alquiler está por finalizar")
            ->view('emails.end_message');
    }
}
