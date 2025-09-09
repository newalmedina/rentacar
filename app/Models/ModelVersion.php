<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelVersion extends Model
{
    use HasFactory;

    protected $table = 'model_versions';

    protected $fillable = [
        'model_id',
        'slug',
        'name',
        'description',
        'active',
    ];

    /**
     * Relación con CarModel (muchos a uno).
     */
    public function carModel()
    {
        return $this->belongsTo(CarModel::class, 'model_id');
    }
    public function getCanDeleteAttribute(): bool
    {
        return $this->items()->doesntExist();
    }
    public function items()
    {
        return $this->hasMany(Item::class);  // A category can have many items
    }
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
