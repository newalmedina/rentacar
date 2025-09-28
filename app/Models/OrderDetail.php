<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OrderDetail extends Model
{
    protected $fillable = [
        'order_id',
        'item_id',
        'original_price',
        'price',
        'taxes',
        'quantity',
    ];

    protected $appends = [
        'total_base_price',
        'taxes_amount',
        'total_with_taxes',

        'total_kilometers',
        'gasoil_deficit',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function getProductNameFormattedAttribute(): string
    {
        $name = 'Sin nombre';

        if ($this->item_id && $this->item) {
            $name = $this->item->type === 'vehicle'
                ? $this->item->full_name
                : $this->item->name;
        } elseif (!empty($this->product_name)) {
            $name = $this->product_name;
        }

        return Str::limit($name, 200, '...');
    }


    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function getTotalBasePriceAttribute(): float
    {
        return round($this->price * $this->quantity, 2);
    }
    public function getTaxesAmountAttribute(): float
    {
        $price = round((float) $this->price, 2);
        $taxes = round((float) $this->taxes, 2);

        return round(($price * $taxes * $this->quantity) / 100, 2);
    }
    public function getTotalWithTaxesAttribute(): float
    {
        return round($this->total_base_price + $this->taxes_amount, 2);
    }
    public function getTotalKilometersAttribute(): ?float
    {
        if (
            is_numeric($this->start_kilometers) &&
            is_numeric($this->end_kilometers)
        ) {
            return round($this->end_kilometers - $this->start_kilometers, 2);
        }

        return null; // o 0 si prefieres devolver cero
    }
    public function getGasoilDeficitAttribute(): ?float
    {
        if (is_numeric($this->fuel_delivery) && is_numeric($this->fuel_return)) {
            return round($this->fuel_delivery - $this->fuel_return, 2);
        }

        return null;
    }
}
