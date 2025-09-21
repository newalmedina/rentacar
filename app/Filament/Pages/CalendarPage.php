<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Item;
use App\Models\User;

class CalendarPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.calendar-orders';
    public array $statusList = [];
    public  $itemsList = [];
    public array $statusListColors = [];
    public ?string $selectedStatus = null;
    protected static ?string $title = 'Calendario Alquileres';
    public array $customerList = [];
    public ?int $selectedCustomer = null;
    public ?int $selectedItem = null;

    public function mount()
    {
        // Estados

        $this->statusList = [
            'Pendiente'  => 'Pendiente',
            'En curso'   => 'En curso',
            'Completado' => 'Completado',
        ];

        $this->statusListColors = [
            'Pendiente' => '#adb5bd',  // Gris claro (secondary)
            'En curso'  => '#0dcaf0',  // Info
            'Completado' => '#198754', // Success
        ];

        // Trabajadores
        $this->customerList = Customer::active()->myCenter()->pluck('name', 'id')->toArray();
        $this->itemsList = Item::active()
            ->vehicle()
            ->myCenter()->get();

        $this->selectedStatus = '';
        $this->selectedItem = null;
        $this->selectedCustomer = null;
    }
}
