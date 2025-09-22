<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Toggle invoice status of an order.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleInvoice(Order $order)
    {
        dd(234);
        // Cambiar el estado de facturado
        $order->invoiced = !$order->invoiced;
        $order->save();

        // Mensaje de éxito
        $message = $order->invoiced
            ? 'La orden ha sido facturada correctamente.'
            : 'La facturación de la orden ha sido revertida.';

        return redirect()->back()->with('success', $message);
    }
}
