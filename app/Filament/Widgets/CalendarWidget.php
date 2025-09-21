<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Illuminate\Support\Facades\Auth;

class CalendarWidget extends FullCalendarWidget
{
    public ?string $selectedStatus = null;
    public ?int $selectedCustomer = null;
    public ?int $selectedItem = null;

    public function fetchEvents(array $fetchInfo): array
    {
        $fechaInicio = \Carbon\Carbon::parse($fetchInfo['start'])->format("Y-m-d");
        $fechaFin = \Carbon\Carbon::parse($fetchInfo['end'])->format("Y-m-d");

        $query = Order::with([
            'assignedUser',
            'customer',
            'orderDetails' => fn($q) => $q->whereHas('item', fn($q2) => $q2->where('type', 'vehicle'))->with('item')
        ])
            ->withVehicles()
            ->myCenter()
            ->whereBetween('date', [$fechaInicio, $fechaFin]);

        // Filtrar por estado
        if (!empty($this->selectedStatus)) {
            $now = now()->toDateString();

            switch ($this->selectedStatus) {
                case 'Pendiente':
                    $query->where(function ($q) use ($now) {
                        $q->whereNull('start_date')
                            ->orWhereNull('end_date')
                            ->orWhere('start_date', '>', $now);
                    });
                    break;

                case 'En curso':
                    $query->whereDate('start_date', '<=', $now)
                        ->whereDate('end_date', '>=', $now);
                    break;

                case 'Completado':
                    $query->whereDate('end_date', '<', $now);
                    break;
            }
        }

        // Filtrar por customer
        if (!empty($this->selectedCustomer)) {
            $query->where('customer_id', $this->selectedCustomer);
        }

        // Filtrar por vehículo específico
        if (!empty($this->selectedItem)) {
            // dd($this->selectedItem);
            $query->whereHas('orderDetails', fn($q2) => $q2->where('item_id', $this->selectedItem));
        }

        return $query->get()->map(fn($order) => [
            'id'          => $order->id,
            'title'       => collect([

                $order->orderDetails
                    ->map(fn($d) => $d->item?->full_name)
                    ->filter()
                    ->implode(', '),
                $order->customer?->name
            ])->filter()->implode(' | ') ?: 'Sin vehículo',
            'start'       => $order->start_date ? \Carbon\Carbon::parse($order->start_date)->toDateTimeString() : null,
            'end'         => $order->end_date ? \Carbon\Carbon::parse($order->end_date)->toDateTimeString() : null,
            'status'      => $order->status,
            'allDay'      => false,
            'color'       => $order->status_color,
            'textColor'   => '#ffffff',
            'borderColor' => $order->status_color,
        ])->all();
    }



    private function isAdminPanel(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'admin';
    }

    protected function headerActions(): array
    {
        return [];
    }

    protected function modalActions(): array
    {
        return [];
    }

    public static function canView(): bool
    {
        return !request()->is('admin');
    }
}
