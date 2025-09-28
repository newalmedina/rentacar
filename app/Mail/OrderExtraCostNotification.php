<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;

class OrderExtraCostNotification extends Mailable
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
        return $this->subject("Atención: Coste extra en tu reserva {$this->order->reserva_id}")
            ->view('emails.order_extra_cost');
    }
}
