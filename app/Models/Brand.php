<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Brand extends Model
{
    use HasFactory;

    // Permite asignación masiva a todos los campos
    protected $guarded = [];

    /**
     * Relación con Item (una marca puede tener muchos ítems)
     */
    public function items()
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Determina si la marca se puede eliminar
     */
    public function getCanDeleteAttribute(): bool
    {
        return $this->items()->doesntExist();
    }

    /**
     * Scope para filtrar marcas activas
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Boot method para generar slug automáticamente si no viene
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($brand) {
            if (empty($brand->slug)) {
                $brand->slug = Str::slug($brand->name);
            }
        });

        static::updating(function ($brand) {
            if (empty($brand->slug)) {
                $brand->slug = Str::slug($brand->name);
            }
        });
    }
}
