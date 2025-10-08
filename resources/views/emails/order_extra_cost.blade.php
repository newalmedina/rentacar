@extends('emails.layouts.app')

@section('title', "Coste extra en tu reserva")

@section('content')
<tr>
    <td style="padding: 30px 40px; color: #333333; font-size: 16px; line-height: 1.5;">
        <h2>Hola {{ $order->center->name ?? 'Centro' }},</h2>
        <p>
            Se ha detectado un coste extra en tu reserva <strong>{{ $order->code }}</strong> , realizada por <strong> {{  $order->customer->name ??'' }}- {{  $order->customer->identification ??'' }} </strong> 
            para el vehículo <strong>{{ $order->orderDetails->first()->item->full_name ?? 'Vehículo' }}</strong>.
        </p>
        <p style="margin: 0; font-size: 15px; line-height: 1.6; font-family: Arial, sans-serif;">
        Por favor, revisa los detalles de tu reserva en tu panel de <strong>Amovens</strong> y actualízala en la plataforma <strong>{{ $center->name }}</strong>.  
        Puedes hacerlo fácilmente desde el siguiente 
        <a href="{{ url('admin/orders/' . $order->id . '/edit') }}" 
            style="color: {{ $order->center->primary_color ?? '#FFC107' }}; padding: 6px 10px; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: bold;">
            enlace
        </a>.
        </p>

    </td>
</tr>
@endsection
