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
    // Accessor para formatear el status
    public function getStatusLabelAttribute(): ?string
    {
        // dd($this->status);
        $labels = [
            'confirmacion' => 'Confirmado',
            'comienzo'     => 'En curso',
            'cancelacion'  => 'Cancelado',
            'ampliacion'   => 'Ampliado',
            'devolucion'   => 'Devolución',
        ];

        return $labels[$this->status] ?? null;
    }
    // Accessor para obtener el color en HEX según el status
    public function getStatusColorAttribute(): ?string
    {
        $label = $this->status_label;

        return match ($label) {
            'Confirmado'  => '#198754', // Verde (success)
            'En curso'    => '#0dcaf0', // Azul (info)
            'Cancelado'   => '#dc3545', // Rojo (danger)
            'Ampliado'    => '#ffc107', // Amarillo (warning)
            'Devolución'  => '#6c757d', // Gris oscuro (secondary)
            default       => null,
        };
    }
}
