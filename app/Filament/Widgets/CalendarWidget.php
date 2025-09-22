<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use App\Models\Customer;
use App\Models\Item;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Html;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\Action;
use Filament\Forms\Components\ButtonAction;
use Filament\Notifications\Notification;


class CalendarWidget extends FullCalendarWidget
{
    public ?string $selectedStatus = null;
    public ?int $selectedCustomer = null;
    public ?int $selectedItem = null;

    public Model|string|null $model = Order::class;

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



    public function getFormSchema(): array
    {
        return [
            Grid::make(12)->schema([
                // ViewField::make('invoice_button')
                //     ->label('')
                //     ->view('filament.components.invoice-button', [
                //         'record' => $this->record,
                //     ])
                //     ->columnSpan(12)
                //     ->extraAttributes(['class' => 'mb-2']),

                ViewField::make('status_badge')
                    ->label('Estado')
                    ->columnSpan([
                        'default' => 12,
                    ])
                    ->view('filament.components.status-badge'),

                DateTimePicker::make('start_date')
                    ->label('Fecha de inicio')
                    ->required()
                    ->columnSpan([
                        'default' => 12, // móvil
                        'md' => 6,       // escritorio
                    ])
                    ->withoutSeconds(),

                DateTimePicker::make('end_date')
                    ->label('Fecha de fin')
                    ->required()
                    ->columnSpan([
                        'default' => 12,
                        'md' => 6,
                    ])
                    ->withoutSeconds(),

                TextInput::make('duration')
                    ->label('Duración')
                    ->disabled() // solo lectura
                    ->dehydrated(false) // no se guarda en la DB
                    ->afterStateHydrated(fn($set, $record) => $set('duration', $record?->duration))
                    ->columnSpan([
                        'default' => 12, // móvil
                        'md' => 6,       // escritorio
                    ]),
                Select::make('customer_id')
                    ->label('Cliente')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->required()
                    ->columnSpan([
                        'default' => 12,
                        'md' => 6,
                    ]),
                Grid::make(12)->schema([

                    TextInput::make('sub_total')
                        ->label('Importe')
                        ->disabled()
                        ->dehydrated(false)
                        ->afterStateHydrated(fn($set, $record) => $set('sub_total', $record?->sub_total . "€"))
                        ->columnSpan([
                            'default' => 12,
                            'md' => 3,
                        ]),
                    TextInput::make('iva')
                        ->label('iva')
                        ->disabled()
                        ->dehydrated(false)
                        ->afterStateHydrated(fn($set, $record) => $set('iva', $record?->iva . "%"))
                        ->columnSpan([
                            'default' => 12,
                            'md' => 3,
                        ]),
                    TextInput::make('impuestos')
                        ->label('Impuestos')
                        ->disabled()
                        ->dehydrated(false)
                        ->afterStateHydrated(fn($set, $record) => $set('impuestos', $record?->impuestos . "€"))
                        ->columnSpan([
                            'default' => 12,
                            'md' => 3,
                        ]),
                    TextInput::make('total')
                        ->label('Precio total')
                        ->disabled()
                        ->dehydrated(false)
                        ->afterStateHydrated(fn($set, $record) => $set('total', $record?->total . "€"))
                        ->columnSpan([
                            'default' => 12,
                            'md' => 3,
                        ]),


                ]),

                ViewField::make('order_details_view')
                    ->label('Detalles del pedido')
                    ->view('filament.components.order-details', [
                        'orderDetails' => $this->record?->orderDetails ?? [],
                    ])
                    ->columnSpan(12),

            ]),
        ];
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
