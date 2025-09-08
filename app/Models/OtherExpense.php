<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class OtherExpense extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function details()
    {
        return $this->hasMany(OtherExpenseDetail::class);
    }
    /**
     * Obtener el total (suma del precio de los detalles)
     */
    public function getTotalAttribute()
    {
        // Sumar los precios de todos los detalles relacionados
        return $this->details()->sum('price');
    }

    // Relación con Center
    public function center()
    {
        return $this->belongsTo(Center::class);
    }
    /**
     * Obtener el nombre de los items como un string separado por comas
     */
    public function getItemnamestringAttribute()
    {
        // Obtener los nombres de los items relacionados y unirlos con coma
        return $this->details->map(function ($detail) {
            return $detail->item->name; // Obtener el nombre del item desde la relación
        })->implode(', '); // Unir los nombres con coma
    }

    // Boot method para asignar center_id automáticamente
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->center_id) && Auth::check()) {
                $model->center_id = Auth::user()->center_id;
            }
        });
    }
}
