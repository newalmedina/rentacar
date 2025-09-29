<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\EndMessageNotification;

class SendEndMessageNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-end-messages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía notificaciones de recordatorio cuando falta 1 hora para que termine el alquiler';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        $target = $now->copy()->addHour(); // dentro de 1 hora

        $orders = Order::where('is_renting', true)
            ->whereNull('end_notification_sent_at') // aún no se envió
            ->whereBetween('end_date', [
                $target->copy()->subMinutes(5), // tolerancia -5 min
                $target->copy()->addMinutes(5)  // tolerancia +5 min
            ])
            ->with(['customer', 'center'])
            ->get();

        if ($orders->isEmpty()) {
            $this->info("No hay reservas para notificar.");
            return;
        }

        foreach ($orders as $order) {
            $this->sendEndMessage($order);
        }
    }

    private function sendEndMessage(Order $order)
    {
        if (!$order->center->enable_end_message) {
            $this->info("El centro {$order->center->name} no tiene habilitado el end message.");
            return;
        }

        try {
            \Mail::to($order->customer->email)
                ->cc($order->center->email)
                ->send(
                    (new \App\Mail\EndMessageNotification($order))
                        ->from($order->center->email, $order->center->name)
                );

            $order->update([
                'end_notification_sent_at' => now(),
            ]);

            $this->info("Notificación de fin enviada para la reserva {$order->code}");
        } catch (\Exception $e) {
            $this->error("Error al enviar notificación end message: " . $e->getMessage());
        }
    }
}
