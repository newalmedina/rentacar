<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OrderDeletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $brandLogoUrl;
    public $brandName;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function build()
    {
        return $this->subject('Orden eliminada')
            ->from(config('mail.from.address'), Auth::user()->center->name)
            ->view('emails.order_deleted');
    }
}
