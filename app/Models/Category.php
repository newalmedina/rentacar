<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Category extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Optional: Define any relationships, if needed.
    // Inverse relationship with Items
    public function items()
    {
        return $this->hasMany(Item::class);  // A category can have many items
    }
    public function getCanDeleteAttribute(): bool
    {
        return $this->items()->doesntExist();
    }
    public function center()
    {
        return $this->belongsTo(Center::class);
    }

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
