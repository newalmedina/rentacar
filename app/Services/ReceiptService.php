<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

class ReceiptService
{
    public function generate(Order $order)
    {
        // Obtener settings y mapearlos a array plano
        // $settings = Setting::first();
        // $generalSettings = $settings?->general;
        $user = Auth::user();
        $generalSettings = $user->center; // Relación con el centro


        // Cargar relaciones
        $order->load('orderDetails', 'customer');

        // Generar PDF
        return Pdf::loadView('pdf.factura', [
            'order' => $order,
            'generalSettings' => $generalSettings
        ])->setPaper('A4', 'portrait');
    }
}
