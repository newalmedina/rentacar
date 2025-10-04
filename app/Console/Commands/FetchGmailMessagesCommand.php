<?php

namespace App\Console\Commands;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Console\Command;
use App\Services\GmailService;
use Carbon\Carbon;
use Illuminate\Support\Str;

class FetchGmailMessagesCommand extends Command
{
    // protected $signature = 'actualizar_reservas {tiempo?}';
    protected $signature = 'actualizar_reservas {tiempo?} {--error=false}';

    protected $description = 'Obtiene los correos recientes de Gmail, opcionalmente filtrando por tiempo en minutos';
    public $error = false;
    //php artisan actualizar_reservas 9000
    //php artisan actualizar_reservas 

    public function handle()
    {
        try {
            $tiempo = $this->argument('tiempo') ?? 60; // valor por defecto: 60 minutos
            $this->error = filter_var($this->option('error'), FILTER_VALIDATE_BOOLEAN);

            $gmailService = new GmailService($tiempo);
            $messages = $gmailService->fetchRecentMessages();


            $this->info("Se encontraron " . count($messages) . " mensajes recientes:");

            foreach ($messages as $msg) {
                try {
                    $data = $this->getInfo($msg);
                    if ($data["tipo_mensaje"] == "desconocido") {
                        continue;
                    }

                    $cars = Item::with('center')->vehicle()
                        ->where('matricula', $data['matricula_coche'])
                        ->whereHas('center', fn($q) => $q->where('active', true)
                            ->where('mail_enable_integration', true))
                        ->get();

                    if ($cars->isEmpty()) {
                        $this->info("No se encontraron coches activos para la matrícula {$data['matricula_coche']}");
                        continue;
                    }

                    foreach ($cars as $car) {
                        if (!$car->center) {
                            $this->info("Coche {$car->matricula} sin centro asociado activo, se omite.");
                            continue;
                        }

                        $centerId = $car->center_id;

                        switch ($data['tipo_mensaje']) {
                            case 'confirmacion':
                                $this->handleConfirmation($data, $car, $centerId, $msg);
                                break;

                            case 'comienzo':
                                $this->handleStart($data, $msg, $car, $centerId);
                                break;

                            case 'ampliacion':
                                $this->handleAmpliacion($data, $msg, $car, $centerId);
                                break;

                            case 'devolucion':
                                $this->handleReturn($data, $msg, $car, $centerId);
                                break;

                            case 'cancelacion':
                                $this->handleCancellation($data, $centerId, $msg);
                                break;

                            default:
                                $this->info("Tipo de mensaje desconocido: {$data['tipo_mensaje']}");
                                break;
                        }
                    }
                } catch (\Throwable $th) {
                    if ($this->error) {
                        dd($th);
                    }
                    $this->info("Error" . $th);
                }
            }

            $this->info("-------");
            $this->info("Proceso terminado correctamente.");
        } catch (\Exception $e) {
            $this->error("Error general: " . $e->getMessage());
        }
    }

    /**
     * Nota sobre handleConfirmation, handleStart, handleReturn:
     * - Elimina todos los `return` prematuros.
     * - Si no se encuentra algo (orden, matrícula, etc.), solo se loguea y se continúa.
     * - Nunca se interrumpe el foreach principal de mensajes ni de coches.
     */

    private function handleConfirmation(array $data, Item $car, int $centerId, $msg)
    {
        $existingOrder = Order::where('reserva_id', $data['id_reserva'])
            ->where('center_id', $centerId)
            ->first();


        if ($existingOrder) {
            $this->info("Orden ya existente: " . $data['id_reserva']);
            return; // solo informamos y seguimos, no cortamos foreach principal
        }

        try {
            $order = new Order([
                'reserva_id' => $data['id_reserva'],
                'center_id' => $centerId,
                'date' => isset($data['fecha_inicio']) ? Carbon::parse($data['fecha_inicio'])->format('Y-m-d') : now()->format('Y-m-d'),
                'start_date' => isset($data['fecha_inicio']) ? Carbon::parse($data['fecha_inicio']) : now(),
                'end_date' => isset($data['fecha_fin']) ? Carbon::parse($data['fecha_fin']) : now(),
                'is_renting' => 1,
                'type' => "sale",
            ]);
            $order->save();

            $orderDetail = new OrderDetail([
                'item_id' => $car->id,
                'original_price' => isset($data['ingresos']) ? str_replace(',', '.', $data['ingresos']) : 0,
                'price' => isset($data['ingresos']) ? str_replace(',', '.', $data['ingresos']) : 0,
                'taxes' => 0,
                'quantity' => 1,
            ]);
            $order->orderDetails()->save($orderDetail);

            $this->addOrderStatus($order, $data['tipo_mensaje'], $msg);

            $this->info("Orden insertada: " . $data['id_reserva']);
        } catch (\Exception $e) {
            $this->error("Error al crear orden {$data['id_reserva']}: " . $e->getMessage());
        }
    }

    private function handleStart(array $data, $msg, Item $car, int $centerId)
    {
        preg_match('#/accounts/rentals/(\d+)#', $msg->body, $matches);
        $data['id_reserva'] = $matches[1] ?? null;

        if (!$data['id_reserva']) {
            $this->info("No se pudo extraer ID de reserva del mensaje para el coche {$car->matricula}");
        }

        $customer = Customer::firstOrCreate(
            ['center_id' => $centerId, 'identification' => $data['arrendatario_carnet'] ?? null],
            [
                'name' => $data['arrendatario_nombre'] ?? "Nombre Desconocido",
                'email' => $data['arrendatario_email'] ?? ($data['arrendatario_nombre'] . "@correoDesconocido.com"),
                'phone' => $data['arrendatario_telefono'] ?? null,
            ]
        );

        $order = Order::where('reserva_id', $data['id_reserva'])
            ->where('center_id', $centerId)
            ->first();

        if ($order) {
            try {
                $order->update(['customer_id' => $customer->id]);

                $orderDetail = $order->orderDetails()->where('item_id', $car->id)->first();
                if ($orderDetail) {
                    $orderDetail->start_kilometers = $data['km_entrega'] ?? $orderDetail->start_kilometers;
                    $orderDetail->end_kilometers = $data['km_entrega'] ?? $orderDetail->end_kilometers;
                    $orderDetail->fuel_delivery = $this->parseFuel($data['combustible_entrega'] ?? null);
                    $orderDetail->save();
                }

                $this->addOrderStatus($order, $data['tipo_mensaje'], $msg);

                $this->info("Orden actualizada (inicio): " . $data['id_reserva']);

                $this->sendStartMessage($order);
            } catch (\Exception $e) {
                $this->error("Error al actualizar orden (inicio) {$data['id_reserva']}: " . $e->getMessage());
            }
        } else {
            $this->info("No se encontró orden para actualizar inicio: " . $data['id_reserva']);
        }
    }

    private function handleAmpliacion(array $data, $msg, Item $car, int $centerId)
    {
        //la reserva id es nueva el cual hay que buscar el vehiculo por fecha y matricula
        $order = Order::where('reserva_id', $data['id_reserva'])
            ->where('center_id', $centerId)
            ->first();



        if (!$order) {
            $order = $this->getOrderByMatriculaDate($data, $centerId);
            $order->reserva_id =  $data['id_reserva'];
            $order->save();
        }

        if ($order) {
            try {

                // $order->start_date = isset($data['fecha_inicio']) ? Carbon::parse($data['fecha_inicio']) : now();
                if (isset($data['fecha_fin'])) {

                    $order->end_date = Carbon::parse($data['fecha_fin']);
                }

                $orderDetail = $order->orderDetails()->where('item_id', $car->id)->first();
                if ($orderDetail) {

                    $orderDetail->price = isset($data['ingresos']) ? str_replace(',', '.', $data['ingresos']) : 0;
                    // $orderDetail->price = isset($data['ingresos']) ? str_replace(',', '.', $data['ingresos']) : 0;
                }
                $order->save();
                $orderDetail->save();

                $this->addOrderStatus($order, $data['tipo_mensaje'], $msg);

                $this->info("Orden ampliada: " . $data['id_reserva']);
            } catch (\Exception $e) {
                $this->error("Error al registrar la ampliacion para {$data['id_reserva']}: " . $e->getMessage());
            }
        } else {
            $this->info("No se encontró orden para ampliar: " . $data['id_reserva']);
        }
    }
    private function getOrderByMatriculaDate($data, $center_id)
    {
        // Validar que existan los índices necesarios

        try {
            $fecha_inicio = Carbon::parse($data['fecha_inicio'])
                ->startOfSecond()
                ->format('Y-m-d H:i:s');

            $fecha_fin = Carbon::parse($data['fecha_inicio'])
                ->endOfSecond()
                ->format('Y-m-d H:i:s');
            return Order::select('orders.*')
                ->join('order_details', 'order_details.order_id', '=', 'orders.id')
                ->join('items', 'order_details.order_id', '=', 'orders.id')
                ->where('orders.center_id', $center_id)
                ->where('items.matricula', $data["matricula_coche"])
                ->where('orders.start_date', '>=', $fecha_inicio)
                ->where('orders.start_date', '<=', $fecha_fin)
                ->orderBy('orders.start_date', 'desc')
                ->first();
        } catch (\Exception $e) {
            if ($this->error) {
                dd($e);
            }
            // Retorna null si la fecha no es válida
            return null;
        }
    }

    private function handleReturn(array $data, $msg, Item $car, int $centerId)
    {
        preg_match('#/accounts/rentals/(\d+)#', $msg->body, $matches);
        $data['id_reserva'] = $matches[1] ?? null;

        if (!$data['id_reserva']) {

            $this->info("No se pudo extraer ID de reserva del mensaje de devolución para el coche {$car->matricula}");
        }

        $order = Order::where('reserva_id', $data['id_reserva'])
            ->where('center_id', $centerId)
            ->first();

        if ($order) {
            try {
                $orderDetail = $order->orderDetails()->where('item_id', $car->id)->first();
                if ($orderDetail) {
                    $orderDetail->end_kilometers = ($orderDetail->start_kilometers ?? 0) + ($data['km_recorridos'] ?? 0);
                    $orderDetail->fuel_return = $this->parseFuel($data['combustible_devolucion'] ?? null);
                    $orderDetail->save();
                }

                $isNewStatus = $this->addOrderStatus($order, $data['tipo_mensaje'], $msg);

                if (($data['coste_extra'] ?? false) && $isNewStatus) {
                    $this->sendExtraCostNotification($order);
                }

                $this->info("Orden actualizada (devolución): " . $data['id_reserva']);
            } catch (\Exception $e) {
                $this->error("Error al actualizar orden (devolución) {$data['id_reserva']}: " . $e->getMessage());
            }
        } else {
            $this->info("No se encontró orden para actualizar devolución: " . $data['id_reserva']);
        }
    }

    private function handleCancellation(array $data, int $centerId, $msg)
    {
        $order = Order::where('reserva_id', $data['id_reserva'])
            ->where('center_id', $centerId)
            ->first();

        if ($order) {
            try {
                $order->invoiced_automatic = false;
                $order->block_order = true;
                $order->invoiced = false;
                $order->save();
                $this->addOrderStatus($order, $data['tipo_mensaje'], $msg);
                $this->info("Orden cancelada: " . $data['id_reserva']);
            } catch (\Exception $e) {
                $this->error("Error al registrar cancelación para {$data['id_reserva']}: " . $e->getMessage());
            }
        } else {
            $this->info("No se encontró orden para cancelar: " . $data['id_reserva']);
        }
    }


    private function parseFuel(?string $value): ?float
    {
        if (!$value) return null;
        $cleanValue = str_replace('%', '', trim($value));
        return is_numeric($cleanValue) ? floatval($cleanValue) : null;
    }

    private function getInfo($msg): array
    {
        $text = $msg->clean_body;
        $lower = strtolower($text);

        $tipo = match (true) {
            str_contains($lower, 'reserva confirmada') => 'Confirmación',
            str_contains($lower, 'contrato de alquiler - comienzo') => 'Comienzo',
            str_contains($lower, 'contrato de alquiler - devolución') => 'Devolución',
            str_contains($lower, 'ha cancelado tu reserva') => 'Cancelación',
            str_contains($lower, 'amplió el alquiler') => 'Ampliación', // Nuevo tipo
            default => 'Desconocido',
        };

        $data = [];

        if ($tipo === 'Confirmación') {
            preg_match_all('/\d{1,2}\.\s*\w+.*?\d{1,2}:\d{2}/', $text, $fechas);
            $data['fecha_inicio'] = $fechas[0][0] ?? null;
            $data['fecha_fin'] = $fechas[0][1] ?? null;
            preg_match('/Ingresos totales\s*([\d.,]+)/', $text, $m) && $data['ingresos'] = $m[1];
            preg_match('/ID de reserva\s*(\d+)/', $text, $m) && $data['id_reserva'] = $m[1];
            preg_match('/\b[A-Z0-9]{4,8}\b/', $text, $m) && $data['matricula_coche'] = $m[0];
        }
        if ($tipo === 'Ampliación') {
            // Extraer fechas actualizadas
            preg_match_all('/\d{1,2}\.\s*\w+.*?\d{1,2}:\d{2}/', $text, $fechas);
            $data['fecha_inicio'] = $fechas[0][0] ?? null;
            $data['fecha_fin'] = $fechas[0][1] ?? null;

            // Extraer nuevos ingresos
            preg_match('/Nuevos ingresos totales\s*([\d.,]+)/', $text, $m) && $data['ingresos'] = $m[1];

            // Extraer ID de reserva
            preg_match('/ID de reserva\s*(\d+)/', $text, $m) && $data['id_reserva'] = $m[1];

            // Extraer matrícula
            preg_match('/\b[A-Z0-9]{4,8}\b/', $text, $m) && $data['matricula_coche'] = $m[0];
        }

        if ($tipo === 'Comienzo' || $tipo === 'Devolución' || $tipo === 'Cancelación') {
            preg_match('/\b[A-Z0-9]{4,8}\b/', $text, $m) && $data['matricula_coche'] = $m[0];
        }

        // Extraer campos adicionales
        if ($tipo === 'Comienzo') {
            preg_match('/Kilometraje a la entrega\s*(\d+)/', $text, $m) && $data['km_entrega'] = $m[1];
            preg_match('/Nivel a la entrega\s*(\d+%)/', $text, $m) && $data['combustible_entrega'] = $m[1];
            preg_match('/Arrendatario\s+Nombre\s+(.+?)\s+Teléfono/s', $text, $m) && $data['arrendatario_nombre'] = trim($m[1]);
            preg_match('/Teléfono\s+(\d+)/', $text, $m) && $data['arrendatario_telefono'] = $m[1];
            preg_match('/Email\s+(\S+)/', $text, $m) && $data['arrendatario_email'] = $m[1];
            preg_match('/Número del carne de conducir\s+(\S+)/', $text, $m) && $data['arrendatario_carnet'] = $m[1];
        }

        if ($tipo === 'Devolución') {
            preg_match('/Kilómetros recorridos\s*(\d+)/', $text, $m) && $data['km_recorridos'] = $m[1];
            preg_match('/Nivel a la devolución\s*(\d+%)/', $text, $m) && $data['combustible_devolucion'] = $m[1];
            $data['coste_extra'] = preg_match('/Costes extra/i', $text) ? true : false;
        }

        if ($tipo === 'Cancelación') {
            preg_match('/ID de reserva\s*(\d+)/', $text, $m) && $data['id_reserva'] = $m[1];
        }

        $data['tipo_mensaje'] = Str::slug($tipo);

        return $data;
    }

    private function addOrderStatus(Order $order, string $slug, $msg): bool
    {
        $exists = $order->onlineStatuses()
            ->where('status', $slug)
            ->exists();

        $date = isset($msg->received_at) ? Carbon::parse($msg->received_at) : now();

        if (!$exists) {
            $order->onlineStatuses()->create([
                'status' => $slug,
                'date' => $date,
                'reserva_id' => $order->reserva_id, // 👈 nuevo campo
            ]);

            $this->info("Estado '{$slug}' registrado para la orden {$order->id}");
            return true; // Nuevo status creado
        }

        return false; // Status ya existía
    }

    private function sendExtraCostNotification(Order $order)
    {
        try {
            // Verifica que la orden tenga un centro y que tenga email
            if (!$order->center || empty($order->center->email)) {
                $this->error("No se puede enviar mail: centro inexistente o sin email para la orden {$order->code}");
                return;
            }

            // Envía el mail usando el Mailable que creamos
            \Mail::to($order->customer->email)
                ->cc($order->center->email) // copia al centro
                ->send(
                    (new \App\Mail\OrderExtraCostNotification($order))
                        ->from($order->center->email, $order->center->name) // desde el centro
                );


            $this->info("Notificación de coste extra enviada para la orden {$order->code}");
        } catch (\Exception $e) {
            // En caso de error en el envío, lo loguea y no rompe la ejecución
            $this->error("Error al enviar notificación de coste extra: " . $e->getMessage());
        }
    }

    private function sendStartMessage(Order $order)
    {
        // Verifica si el centro tiene habilitado el mensaje
        if (!$order->center->enable_start_message) {
            $this->info("El centro {$order->center->name} no tiene habilitado el start message.");
            return;
        }
        // Verifica si ya se ha enviado la notificación de fin

        if (!empty($order->start_notification_sent_at)) {
            $this->info("La orden {$order->id} ya tiene enviada la notificación de inicio.");
            return;
        }

        try {
            // Envía el mail usando el Mailable que creamos
            \Mail::to($order->customer->email)
                ->cc($order->center->email)
                ->send(
                    (new \App\Mail\StartMessageNotification($order))
                        ->from($order->center->email, $order->center->name)
                );

            $order->start_notification_sent_at = now();
            $order->save();

            $this->info("Notificación start message enviada para la reserva {$order->code}");
        } catch (\Exception $e) {
            // Loguea el error sin romper la ejecución
            $this->error("Error al enviar notificación start message: " . $e->getMessage());
        }
    }
}
