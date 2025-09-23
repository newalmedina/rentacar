<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use Carbon\Carbon;

class ProcessAutomaticInvoices extends Command
{
    protected $signature = 'invoices:process-automatic';
    protected $description = 'Procesa las órdenes que deben facturarse automáticamente';

    public function handle()
    {
        $this->info('Buscando órdenes para facturación automática...');

        $orders = Order::where('invoiced_automatic', 1)
            ->where('invoiced', 0)
            ->where('end_date', '<=', Carbon::now()->subHours(12))
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No se encontraron órdenes para procesar.');
            return 0;
        }

        foreach ($orders as $order) {
            // Aquí va la lógica que necesites (ej. marcar como facturado, generar factura, etc.)
            $this->info("Procesando orden ID: {$order->id}");

            // Ejemplo: marcar como facturado
            $order->invoiced = 1;
            $order->save();
        }

        $this->info('Facturación automática completada.');
        return 0;
    }
}
