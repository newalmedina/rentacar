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

         @php
                // Filtramos solo los items tipo 'vehicle'
                $vehicleDetails = $order->orderDetails->filter(fn($detail) => $detail->item && $detail->item->type === 'vehicle');
            @endphp

            @if($vehicleDetails->isNotEmpty())
                <p style="font-weight: bold; margin-top: 15px;">
                    Detalle vehículo reservado:
                </p>
               <br>

                <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        @foreach($vehicleDetails as $index => $detail)
                            @php $vehicle = $detail->item; @endphp
                            <td width="50%" valign="top" style="padding:5px;">
                                <table width="100%" cellpadding="10" cellspacing="0" style="border:1px solid #ddd; border-radius:5px;">
                                    <tr>
                                        <td >
                                            <strong style="margin:20px 10px;">{{ $vehicle->full_name }}</strong><br>
                                            <hr style="border:none; border-top:1px solid #ddd; margin:5px 0;">
                                            <div style="padding:20px;">
                                                    Marca: <strong>{{ $vehicle->brand?->name ?? 'N/A' }}</strong><br>
                                                    Modelo: <strong>{{ $vehicle->carModel?->name ?? 'N/A' }}</strong><br>
                                                    Versión: <strong>{{ $vehicle->modelVersion?->name ?? 'N/A' }}</strong><br>
                                                    Matrícula: <strong>{{ $vehicle->matricula ?? 'N/A' }}</strong><br>
                                                    Combustible: <strong>{{ $vehicle->fuelType?->name ?? 'N/A' }}</strong><br>
                                                    Año: <strong>{{ $vehicle->year ?? 'N/A' }}</strong>

                                            </div>

                                        </td>
                                    </tr>
                                </table>
                            </td>

                            {{-- Cada 2 cards, cerramos la fila y abrimos una nueva --}}
                            @if(($index + 1) % 2 == 0)
                                </tr><tr>
                            @endif
                        @endforeach
                    </tr>
                </table>
            @endif
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
