<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CarModel extends Model
{
    use HasFactory;

    protected $table = 'models';

    // Permitimos asignación masiva a todos los campos excepto 'id'
    protected $guarded = ['id'];

    /**
     * Relación con Brand (muchos a uno)
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
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

    /**
     * Relación con ModelVersion (uno a muchos)
     */
    public function versions()
    {
        return $this->hasMany(ModelVersion::class, 'model_id');
    }
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

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });

        static::updating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }
}
