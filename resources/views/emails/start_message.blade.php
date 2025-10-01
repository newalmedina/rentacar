@extends('emails.layouts.app')

@section('title', "Información importante sobre tu reserva")

@section('content')
<tr>
    <td style="padding: 30px 40px; color: #333333; font-size: 16px; line-height: 1.5;">
        <h2>Hola {{ $order->customer->name ?? '' }},</h2>
        <p style=" font-weight: bold;">
            Antes y durante tu alquiler, queremos asegurarnos de que tengas toda la información importante. 
            A continuación, encontrarás los detalles que debes conocer:
        </p>
        <div>
             @php
            // Filtramos solo los items tipo 'vehicle'
            $vehicleDetails = $order->orderDetails->filter(fn($detail) => $detail->item && $detail->item->type === 'vehicle');
        @endphp

        <tr>
    <td style="padding: 30px 40px; color: #333333; font-size: 16px; line-height: 1.5;">
        <h2>Hola {{ $order->customer->name ?? '' }},</h2>

        @php
            // Filtramos solo los items tipo 'vehicle'
            $vehicleDetails = $order->orderDetails->filter(fn($detail) => $detail->item && $detail->item->type === 'vehicle');
        @endphp

        @if($vehicleDetails->isNotEmpty())
            <p style="font-weight: bold; margin-top: 15px;">
                Detalle vehículo reservado:
            </p>
            @foreach($vehicleDetails as $detail)
                @php $vehicle = $detail->item; @endphp
                <div style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 5px;">
                    <strong>{{ $vehicle->full_name }}</strong><br>
                    Marca: {{ $vehicle->brand->name ?? 'N/A' }}<br>
                    Modelo: {{ $vehicle->carModel->name ?? 'N/A' }}<br>
                    Versión: {{ $vehicle->modelVersion->name ?? 'N/A' }}<br>
                    Matrícula: {{ $vehicle->matricula ?? 'N/A' }}<br>
                    Combustible: {{ $vehicle->fuelType->name ?? 'N/A' }}<br>
                    Año: {{ $vehicle->year ?? 'N/A' }}
                </div>
            @endforeach
        @endif
        </div>
        <div style="margin-top: 15px; ">
            {!! $center->start_message !!}
        </div>
        <p style="margin-top: 20px;  font-weight: bold; ">
            Te recomendamos revisar cuidadosamente esta información para disfrutar de tu experiencia de manera segura y sin inconvenientes.
        </p>
        <p style=" font-weight: bold;">
            ¡Gracias por confiar en nosotros!
        </p>
    </td>
</tr>
@endsection
