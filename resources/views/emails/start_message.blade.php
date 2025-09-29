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
