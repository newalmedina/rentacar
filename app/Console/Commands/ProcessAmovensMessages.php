<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\Item;
use App\Models\Customer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderExtraCostNotification;

class ProcessAmovensMessages extends Command
{
    protected $signature = 'amovens:process-messages';
    protected $description = 'Procesa mensajes de Amovens y actualiza órdenes, detalles y estados';

    public function handle()
    {
        $messages = app(\App\Services\GmailService::class)->fetchRecentMessages();

        foreach ($messages as $msg) {
            $data = $this->getDataFromMessage($msg);
            $matricula = $data['matricula_coche'] ?? null;
            if (!$matricula) continue;

            $cars = Item::where('matricula', $matricula)->get();
            if ($cars->isEmpty()) continue;

            $centerId = auth()->check() ? auth()->user()->center_id : null;
            if (!$centerId) continue;

            foreach ($cars as $car) {
                switch ($data['tipo_mensaje'] ?? '') {
                    case 'confirmacion':
                        $this->handleConfirmation($data, $car, $centerId);
                        break;
                    case 'comienzo':
                        $this->handleStart($data, $msg, $car, $centerId);
                        break;
                    case 'devolucion':
                        $this->handleReturn($data, $msg, $car, $centerId);
                        break;
                }
            }
        }

        $this->info('Procesamiento de mensajes finalizado.');
        return Command::SUCCESS;
    }

    private function handleReturn(array $data, $msg, Item $car, int $centerId)
    {
        if (!preg_match('#/accounts/rentals/(\d+)#', $msg->body, $matches)) return;
        $data['id_reserva'] = $matches[1];

        $order = Order::where('reserva_id', $data['id_reserva'])
            ->where('center_id', $centerId)
            ->first();

        if (!$order) return;

        $orderDetail = $order->orderDetails()->where('item_id', $car->id)->first();
        if ($orderDetail) {
            $orderDetail->end_kilometers = ($orderDetail->start_kilometers ?? 0) + ($data['km_recorridos'] ?? 0);
            $orderDetail->fuel_return = $this->parseFuel($data['combustible_devolucion'] ?? null);
            $orderDetail->save();
        }

        $statusExists = $order->onlineStatuses()->where('status', 'devolucion')->exists();
        $this->addOrderStatus($order, 'devolucion', $msg);

        // Si hay coste extra y el status no existía previamente, enviar mail
        if (!empty($data['coste_extra']) && !$statusExists) {
            $this->sendExtraCostNotification($order);
        }
    }

    private function sendExtraCostNotification(Order $order)
    {
        try {
            if (!$order->customer || !$order->customer->email) return;

            Mail::to($order->customer->email)
                ->send(new OrderExtraCostNotification($order));

            $this->info("Notificación de coste extra enviada para la orden {$order->reserva_id}");
        } catch (\Exception $e) {
            $this->error("Error al enviar mail de coste extra: " . $e->getMessage());
        }
    }

    private function parseFuel($value)
    {
        try {
            if ($value !== null) {
                $clean = str_replace('%', '', trim($value));
                return is_numeric($clean) ? floatval($clean) : null;
            }
        } catch (\Exception $e) {
        }
        return null;
    }

    private function addOrderStatus(Order $order, string $slug, $msg)
    {
        $exists = $order->onlineStatuses()->where('status', $slug)->exists();
        if (!$exists) {
            $date = $msg->received_at ?? now();
            $order->onlineStatuses()->create([
                'status' => $slug,
                'date' => Carbon::parse($date),
            ]);
            $this->info("Estado '{$slug}' registrado para la orden {$order->reserva_id}");
        }
    }

    private function getDataFromMessage($msg)
    {
        // Parsear body y retornar array con los campos requeridos
        return [];
    }
}
