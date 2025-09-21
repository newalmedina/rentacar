<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\AppointmentResource;
use App\Mail\AppointmentChangeStatusMail;
use App\Mail\AppointmentChangeStatusToWorkerMail;
use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Filament\Forms\Components\Button;
use Illuminate\Support\Facades\Auth;
use Saade\FilamentFullCalendar\Actions;

class CalendarWidgetOld extends FullCalendarWidget
{
    public Model|string|null $model = Appointment::class;

    public ?string $selectedStatus = null;

    public ?int $selectedWorker = null; // almacenar el user_id seleccionado


    public function fetchEvents(array $fetchInfo): array
    {
        $fechaInicio = Carbon::parse($fetchInfo['start'])->format("Y-m-d");
        $fechaFin = Carbon::parse($fetchInfo['end'])->format("Y-m-d");

        $query = Appointment::whereBetween('date', [$fechaInicio, $fechaFin]);

        // Filtrar por estado
        if (!empty($this->selectedStatus)) {
            $query->where('status', $this->selectedStatus);
        }
        if ($this->isAdminPanel()) {
            // Filtrar por trabajador
            if (!empty($this->selectedWorker)) {
                $query->where('worker_id', $this->selectedWorker);
            }
        } else {
            $query->where('worker_id', Auth::user()->id);
        }


        return $query->get()->map(function (Appointment $appointment) {
            $title = $appointment->worker->name;
            if ($appointment->requester_name) {
                $title .= " - " . $appointment->requester_name;
            }
            if ($appointment->item?->name) {
                $title .= " | " . $appointment->item->name;
            }
            return [
                'id'          => $appointment->id,
                'title'       => $title,
                'start'       => $appointment->start_date?->toDateTimeString(),
                'end'         => $appointment->end_date?->toDateTimeString(),
                'status'      => $appointment->status,
                'allDay'      => false,
                'color'       => $appointment->status_color,
                'textColor'   => '#ffffff',
                'borderColor' => $appointment->status_color,
                'itemName'    => $appointment->item?->name,
            ];
        })->all();
    }





    // 👇 Nuevo método helper
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
        // Mostrar en cualquier página excepto en /admin
        return !request()->is('admin');
    }
}
