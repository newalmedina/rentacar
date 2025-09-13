<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Owner extends Model
{
    use HasFactory;

    protected $guarded = [];
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
    public function state()
    {
        return $this->belongsTo(State::class);
    }
    public function city()
    {
        return $this->belongsTo(City::class);
    }
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
    // Relación con Center
    public function center()
    {
        return $this->belongsTo(Center::class);
    }
    // Relación inversa: un Owner tiene muchos Items
    public function items()
    {
        return $this->hasMany(Item::class);
    }

    // Método para saber si puede eliminarse
    public function canDelete(): bool
    {
        // Si tiene items relacionados, no puede eliminarse
        return !$this->items()->exists();
    }
    public function scopeMyCenter($query)
    {
        $centerId = Auth::check() ? Auth::user()->center_id : null;

        if ($centerId) {
            return $query->where('center_id', $centerId);
        }

        // Si no hay usuario autenticado, devuelve todo o vacío
        return $query->whereRaw('1=0'); // Ningún resultado
    }
    public function getFullAddressAttribute()
    {
        $parts = [
            $this->address,
            $this->postal_code,
            optional($this->city)->name,
            optional(optional($this->city)->state)->name,
            optional($this->country)->name,
        ];

        // Filtramos nulos/vacíos y unimos con coma
        return implode(', ', array_filter($parts));
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
