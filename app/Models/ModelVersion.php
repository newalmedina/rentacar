<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ModelVersion extends Model
{
    // Permitimos asignación masiva en todos los campos excepto los protegidos
    protected $guarded = ['id'];

    /**
     * Genera el slug automáticamente a partir del nombre si no se proporciona
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($version) {
            if (empty($version->slug)) {
                $version->slug = Str::slug($version->name);
            }
        });

        static::updating(function ($version) {
            if (empty($version->slug)) {
                $version->slug = Str::slug($version->name);
            }
        });
    }

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
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
    /**
     * Relación con el modelo principal (CarModel)
     */
    public function model()
    {
        return $this->belongsTo(\App\Models\CarModel::class, 'model_id');
    }

    /**
     * Relación con la marca
     */
    public function brand()
    {
        return $this->belongsTo(\App\Models\Brand::class, 'brand_id');
    }
}
