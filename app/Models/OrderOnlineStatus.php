<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderOnlineStatus extends Model
{
    protected $guarded = [];
    protected $table = 'order_online_statuses'; // Importante
    public $timestamps = true;

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
