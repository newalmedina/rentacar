<?php

namespace App\Models;

use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

class Order extends Model
{
    use SoftDeletes;

    protected $guarded = [];
    protected $appends = [
        'subtotal',
        'impuestos',
        'total',
        'products',
        'status',
        'status_color',
        'invoiced_label',
        'invoiced_color',
    ];

    protected $casts = [
        'date' => 'date',
        'is_renting' => 'boolean',
    ];


    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    // Relación con Center
    public function center()
    {
        return $this->belongsTo(Center::class);
    }


    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    // Appointment.php

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }


    public function getProductsAttribute(): string
    {
        return $this->orderDetails
            ->map(fn($detail) => $detail->product_name_formatted)
            ->implode(', ');
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

    public function getDisabledSalesAttribute(): bool
    {
        return $this->invoiced == 1;
    }
    private static function generateCode($order)
    {
        if ($order->type !== 'sale') {
            return null;
        }

        $prefix = 'ORD';
        $datePart = Carbon::now()->format('ymd');

        // Obtener el último código creado (incluyendo soft deleted)
        $latest = self::withTrashed()
            ->where('type', 'sale')
            ->whereDate('created_at', Carbon::today())
            ->whereNotNull('code')
            ->orderBy('id', 'desc')
            ->first();

        if ($latest && Str::startsWith($latest->code, $prefix . $datePart)) {
            // Extraer la secuencia numérica del código (últimos 3 dígitos)
            $lastSequence = (int) substr($latest->code, -3);
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }

        // Buscar un código que no exista (incluyendo soft deleted)
        do {
            $code = $prefix . $datePart . str_pad($nextSequence, 3, '0', STR_PAD_LEFT);

            $exists = self::withTrashed()
                ->where('code', $code)
                ->exists();

            if ($exists) {
                $nextSequence++;
            } else {
                break;
            }
        } while (true);

        return $code;
    }

    protected static function booted(): void
    {
        // Asigna automáticamente el usuario autenticado al crear
        static::creating(function ($order) {
            if (Auth::check()) {
                $order->created_by = Auth::id();
                $order->code = self::generateCode($order);
            }
        });

        // Asigna automáticamente el usuario que elimina
        static::deleting(function ($order) {
            if (Auth::check() && !$order->isForceDeleting()) {
                $order->deleted_by = Auth::id();
                $order->save();
            }
        });
    }


    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function getSubtotalAttribute(): float
    {
        return round($this->orderDetails->sum('total_base_price'), 2);
    }

    public function getImpuestosAttribute(): float
    {
        return round($this->orderDetails->sum('taxes_amount'), 2);
    }

    public function getTotalAttribute(): float
    {
        return round($this->subtotal + $this->impuestos, 2);
    }
    public function scopeWithCalculatedTotals(Builder $query): Builder
    {
        return $query
            ->select('orders.*') // Muy importante para que no se sobrescriba la query base
            ->selectSub(
                'SELECT COALESCE(SUM(price * quantity), 0)
             FROM order_details
             WHERE order_details.order_id = orders.id',
                'subtotal'
            )
            ->selectSub(
                'SELECT COALESCE(SUM((price * taxes * quantity) / 100), 0)
             FROM order_details
             WHERE order_details.order_id = orders.id',
                'impuestos'
            )
            ->selectSub(
                'SELECT COALESCE(SUM((price * quantity) + ((price * taxes * quantity) / 100)), 0)
             FROM order_details
             WHERE order_details.order_id = orders.id',
                'total'
            );
    }

    public function scopeSales($query)
    {
        return $query->where('type', "sale");
    }
    public function scopeInvoiced($query)
    {
        return $query->where('invoiced', 1);
    }

    public function getStatusAttribute(): ?string
    {
        if (!$this->is_renting) {
            return null;
        }

        $now = now();

        if (!$this->start_date || !$this->end_date) {
            return 'Pendiente';
        }

        if ($now->lt($this->start_date)) {
            return 'Pendiente';
        }

        if ($now->between($this->start_date, $this->end_date)) {
            return 'En curso';
        }

        return 'Completado';
    }


    public function getDurationAttribute(): ?string
    {
        if (!$this->start_date || !$this->end_date) {
            return null;
        }

        $start = Carbon::parse($this->start_date);
        $end   = Carbon::parse($this->end_date);

        // Asegurarse que end >= start; si no, intercambiamos (opcional)
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        // DateInterval con días totales, horas, minutos, segundos
        $diff = $start->diff($end);

        $days    = (int) $diff->days; // días totales
        $hours   = (int) $diff->h;
        $minutes = (int) $diff->i;
        $seconds = (int) $diff->s;

        $parts = [];

        if ($days > 0) {
            $parts[] = $days . ' ' . Str::plural('día', $days);
        }
        if ($hours > 0) {
            $parts[] = $hours . ' ' . Str::plural('hora', $hours);
        }
        if ($minutes > 0) {
            $parts[] = $minutes . ' ' . Str::plural('minuto', $minutes);
        }

        // Si no hay días/horas/minutos, mostramos segundos
        if (empty($parts) && $seconds > 0) {
            $parts[] = $seconds . ' ' . Str::plural('segundo', $seconds);
        }

        return implode(' ', $parts);
    }



    public function getStatusColorAttribute(): ?string
    {
        if (!$this->is_renting || is_null($this->status)) {
            return null;
        }

        return match ($this->status) {
            'Pendiente' => '#adb5bd',  // Gris claro (secondary)
            'En curso'  => '#0dcaf0',  // Info
            'Completado' => '#198754', // Success
            default => null,
        };
    }

    public function getInvoicedLabelAttribute(): string
    {
        return $this->invoiced ? 'Facturado' : 'Pendiente de facturar';
    }

    public function getInvoicedColorAttribute(): string
    {
        return $this->invoiced
            ? '#198754'   // Bootstrap "success" (verde)
            : '#ffc107';  // Bootstrap "warning" (amarillo)
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
