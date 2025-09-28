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
        <p>
            Por favor, revisa los detalles de tu reserva en tu panel de Amovens  actualzalo en la plataforma {{ $center->name }}.
        </p>
    </td>
</tr>
@endsection
