@extends('emails.layouts.app')

@section('title', "Tu reserva está por terminar")

@section('content')
<tr>
    <td style="padding: 30px 40px; color: #333333; font-size: 16px; line-height: 1.5;">
        <h2>Hola {{ $order->customer->name ?? '' }},</h2>
        <p style="font-weight: bold;">
            Queremos recordarte que tu alquiler finaliza próximamente ({{ \Carbon\Carbon::parse($order->end_date)->format('d/m/Y H:i') }}).
        </p>

        <div style="margin-top: 15px;">
            {!! $center->end_message !!}
        </div>
        <p style="margin-top: 20px; font-weight: bold;">
            Gracias por confiar en nosotros. ¡Te esperamos de nuevo pronto!
        </p>
    </td>
</tr>
@endsection
