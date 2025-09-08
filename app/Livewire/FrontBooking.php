<?php

namespace App\Livewire;

use App\Mail\AppointmentNotifyWorkerMail;
use App\Mail\AppointmentRequestedMail;
use App\Models\Appointment;
use App\Models\Country;
use App\Models\Item;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class FrontBooking extends Component
{
    public $selectedDate;
    public $appointment;
    public $showItems = [];
    public $showItemsOthers = [];
    public $appointmentList = [];
    public $highlightedDates = [];
    public $workerlist = [];
    public $countries = [];
    public $phoneCode = 207;
    public $worker_id = null;
    public $showForm = true;
    public $generalSettings;
    public $selectedAppointment = null;
    public $selectedItem = null;
    public $selecteOtherdItem = null;
    public $other_item_id = null;
    public $form = [
        'item_id' => null,
        'requester_name' => null,
        'requester_phone' => null,
        'requester_email' => null,
        'comments' => null,
    ];

    protected $rules = [
        'form.item_id' => 'required|',
        'selectedAppointment' => 'required|integer|exists:appointments,id',
        'form.requester_name' => 'required|string|min:3',
        'form.requester_phone' => 'required|string|min:3',
        'form.requester_email' => 'required|email',
        'selectedDate' => 'required|date|after_or_equal:today',
        'phoneCode' => 'required',
        'form.comments' => 'nullable|string',
    ];
    protected $messages = [
        'form.item_id.required' => 'El campo servicio es obligatorio.',
        'selectedAppointment.required' => 'Debe seleccionar una cita.',
        'selectedAppointment.integer' => 'La cita seleccionada debe ser un número válido.',
        'selectedAppointment.exists' => 'La cita seleccionada no existe.',
        'form.requester_name.required' => 'El nombre es obligatorio.',
        'form.requester_name.string' => 'El nombre debe ser texto.',
        'form.requester_name.min' => 'El nombre debe tener al menos 3 caracteres.',
        'form.requester_phone.required' => 'El teléfono es obligatorio.',
        'form.requester_phone.string' => 'El teléfono debe ser texto.',
        'form.requester_phone.min' => 'El teléfono debe tener al menos 3 caracteres.',
        'form.requester_email.required' => 'El correo electrónico es obligatorio.',
        'form.requester_email.email' => 'Debe ingresar un correo electrónico válido.',
        'selectedDate.required' => 'La fecha es obligatoria.',
        'selectedDate.date' => 'Debe ingresar una fecha válida.',
        'selectedDate.after_or_equal' => 'La fecha no puede ser anterior a hoy.',
        'phoneCode.required' => 'El código de teléfono es obligatorio.',
        'form.comments.string' => 'Los comentarios deben ser texto.',
    ];
    public function mount()
    {
        $this->appointment = new Appointment();
        $this->selectedDate = Carbon::now()->format("Y-m-d");

        // Datos estáticos: solo se cargan una vez
        $this->workerlist = User::canAppointment()->get();
        $this->showItems = Item::showBooking()->orderBy('price')->get();
        $this->showItemsOthers = Item::showBookingOthers()->orderBy('price')->get();

        $this->highlightedDates = Appointment::active()
            ->where("date", ">=", now()->format('Y-m-d'))
            ->statusAvailable()
            ->distinct("date")
            ->pluck("date")
            ->map(fn($date) => Carbon::parse($date)->format("Y-m-d"))
            ->toArray();

        // Primera carga de citas
        $this->loadAppointments();
    }

    private function loadAppointments()
    {
        $this->appointmentList = Appointment::active()
            ->with('worker') // 🔥 evita N+1
            ->where("date", $this->selectedDate)
            ->when($this->worker_id, fn($q) => $q->where('worker_id', $this->worker_id))
            ->statusAvailable()
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        // Verifica si el selectedAppointment existe en la lista
        if (!empty($this->selectedAppointment)) {
            if (!$this->appointmentList->contains('id', $this->selectedAppointment)) {

                $this->selectedAppointment = null;
            }
        }
    }

    // ⚡️ Solo cuando cambie worker_id o fecha
    public function updatedWorkerId()
    {
        $this->loadAppointments();
    }
    public function updatedSelectedDate()
    {
        $this->loadAppointments();
    }

    // ⚡️ Solo cuando cambien los items
    public function updatedFormItemId($id)
    {
        $this->selectedItem = Item::find($id);
    }
    public function updatedOtherItemId($id)
    {
        $this->selecteOtherdItem = Item::find($id);
    }

    public function selectAppointment($id)
    {
        $this->selectedAppointment = $id;
    }

    public function submit()
    {
        $this->validate();

        $this->appointment = Appointment::find($this->selectedAppointment);

        if (! $this->appointment) {
            $this->addError('selectedAppointment', 'La cita seleccionada no existe.');
            return;
        }

        $existing = Appointment::where('requester_email', $this->form['requester_email'])
            ->where('date', $this->appointment->date)
            ->where('id', '!=', $this->appointment->id)
            ->first();

        if ($existing) {
            session()->flash(
                'error',
                "Ya existe una cita para este día con este correo."
            );
            return;
        }

        $country = Country::find($this->phoneCode);

        $this->appointment->update([
            'item_id'        => $this->form['item_id'],
            'requester_name' => $this->form['requester_name'],
            'requester_phone' => "+" . $country->phonecode . $this->form['requester_phone'],
            'requester_email' => $this->form['requester_email'],
            'comments'       => $this->form['comments'],
            'status'         => 'pending_confirmation',
        ]);

        session()->flash(
            'success',
            "Has reservado la cita correctamente."
        );

        $this->showForm = false;
        $this->sendMail();
    }

    public function sendMail()
    {
        // Enviar mail al requester por cola

        Mail::to($this->appointment->requester_email)
            ->queue(new AppointmentRequestedMail($this->appointment->id));
        // Enviar mail al worker por cola
        Mail::to($this->appointment->worker->email)
            ->queue(new AppointmentNotifyWorkerMail($this->appointment->id));


        //Mail::to($this->appointment->requester_email)

        Mail::to("el.solitions@gmail.com")
            ->queue(new AppointmentNotifyWorkerMail($this->appointment->id));
    }
    public function render()
    {
        return view('livewire.front-booking');
    }
}
