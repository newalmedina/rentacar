@extends('emails.layouts.app')

@section('title', 'Factura generada automáticamente')

@section('content')
<tr>
    <td style="padding: 30px 40px; color: #333333; font-size: 16px; line-height: 1.5;">
        <h2 style="margin-top: 0;">Factura generada automáticamente</h2>
        <p>La orden con ID <strong>{{ $order->code }}</strong> ha sido facturada automáticamente.</p>
        <p>Se adjunta la factura correspondiente en formato PDF para su registro.</p>
    </td>
</tr>
@endsection
