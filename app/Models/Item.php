<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class Item extends Model
{
    use HasFactory;

    protected $guarded = [];  // Guarded to allow mass assignment
    protected $appends = ['total_kilometros_recorridos'];

    // Relationship with Category
    public function category()
    {
        return $this->belongsTo(Category::class);  // An item belongs to one category
    }

    // Relationship with Unit of Measure
    public function unitOfMeasure()
    {
        return $this->belongsTo(UnitOfMeasure::class);  // An item belongs to one unit of measure
    }


    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function getTotalKilometrosRecorridosAttribute(): ?float
    {
        // Solo aplica si el tipo es 'vehicle'
        if ($this->type !== 'vehicle') {
            return null;
        }

        // Suma el total de kilómetros de cada orderDetail
        return $this->orderDetails
            ->sum(function ($orderDetail) {
                return $orderDetail->total_kilometers ?? 0;
            });
    }


    public function canDelete(): bool
    {
        return !$this->orderDetails()->exists();
    }


    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }
    // Optional: Relationship with Brand (for products)
    public function brand()
    {
        return $this->belongsTo(Brand::class);  // An item belongs to one brand
    }
    public function carModel()
    {
        return $this->belongsTo(CarModel::class, 'model_id'); // un item pertenece a un modelo
    }

    public function modelVersion()
    {
        return $this->belongsTo(ModelVersion::class, 'car_version_id'); // un item pertenece a una versión
    }
    // Relación con Center
    public function center()
    {
        return $this->belongsTo(Center::class);
    }
    // Optional: Relationship with Supplier (for products)
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);  // An item belongs to one supplier
    }

    public function getTaxesAmountAttribute(): float
    {
        $price = round((float) $this->price, 2);
        $taxes = round((float) $this->taxes, 2);

        return round(($price * $taxes) / 100, 2);
    }



    public function getTotalPriceAttribute(): float
    {
        return round((float) $this->price + $this->taxes_amount, 2);
    }

    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }

        $path = "/{$this->image}";

        // Verifica si el archivo existe en el disco 'public'
        if (!Storage::disk('public')->exists($path)) {
            return null;
        }

        // Retorna la URL pública del archivo
        return Storage::url($path);
    }


    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
    public function scopeVehicle($query)
    {
        return $query->where('type', "vehicle");
    }
    // Scope para show_booking = true
    public function scopeShowBooking($query)
    {
        return $query->where('show_booking', true);
    }

    // Scope para show_booking_others = true
    public function scopeShowBookingOthers($query)
    {
        return $query->where('show_booking_others', true);
    }
    // Boot method para asignar center_id automáticamente
    public function scopeMyCenter($query)
    {
        $centerId = Auth::check() ? Auth::user()->center_id : null;

        if ($centerId) {
            return $query->where('center_id', $centerId);
        }

        // Si no hay usuario autenticado, devuelve todo o vacío
        return $query->whereRaw('1=0'); // Ningún resultado
    }
    public function getFullNameAttribute(): string
    {
        if ($this->type === 'vehicle') {
            $parts = [];

            if ($this->matricula) {
                $parts[] = $this->matricula . " - ";
            }

            if ($this->brand) {
                $parts[] = $this->brand->name;
            }

            if ($this->carModel) {
                $parts[] = $this->carModel->name;
            }

            if ($this->modelVersion) {
                $parts[] = $this->modelVersion->name;
            }
            if ($this->year) {
                $parts[] = '(' . $this->year . ')';
            }

            return implode(' ', $parts);
        }

        return $this->name ?? '';
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
        static::saving(function ($model) {


            // Si es propio, el propietario se setea a null
            if (!$model->gestion) {
                $model->owner_id = null;
            }
        });
    }
}
