<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarModel extends Model
{
    use HasFactory;

    protected $table = 'models';

    protected $fillable = [
        'brand_id',
        'slug',
        'name',
        'description',
        'active',
    ];

    /**
     * Relación con Brand (muchos a uno).
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * Relación con ModelVersion (uno a muchos).
     */
    public function versions()
    {
        return $this->hasMany(ModelVersion::class, 'model_id');
    }
}
