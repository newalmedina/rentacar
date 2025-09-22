<table class="w-full border-collapse border border-gray-300">
    <thead>
       <tr class="font-bold">
            <th class="border border-gray-300 px-2 py-1 text-left">Producto</th>
            <th class="border border-gray-300 px-2 py-1 text-left">Cantidad</th>
            <th class="border border-gray-300 px-2 py-1 text-left">Precio (€)</th>
            <th class="border border-gray-300 px-2 py-1 text-left">Importe (€)</th>
            <th class="border border-gray-300 px-2 py-1 text-left">Iva (%)</th>
            <th class="border border-gray-300 px-2 py-1 text-left">Impuestos (€)</th>
            <th class="border border-gray-300 px-2 py-1 text-left">Total (€)</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($orderDetails as $detail)
            <tr>
                <td class="border border-gray-300 px-2 py-1">{{ $detail->product_name_formatted }}</td>
                <td class="border border-gray-300 px-2 py-1">{{ $detail->quantity }}</td>
                <td class="border border-gray-300 px-2 py-1">{{ number_format($detail->price, 2) }}</td>
                <td class="border border-gray-300 px-2 py-1">{{ number_format($detail->total_base_price, 2) }}</td>
                <td class="border border-gray-300 px-2 py-1">{{ number_format($detail->taxes, 2) }}</td>
                <td class="border border-gray-300 px-2 py-1">{{ number_format($detail->taxes_amount, 2) }}</td>
                <td class="border border-gray-300 px-2 py-1">{{ number_format($detail->total_with_taxes, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="border border-gray-300 px-2 py-1 text-center">Sin detalles</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr class="font-bold">
            <td colspan="3" class="border border-gray-300 px-2 py-1 text-right">Totales:</td>
            <td class="border border-gray-300 px-2 py-1">
                {{ number_format($orderDetails->sum('total_base_price'), 2) }}
            </td>
            <td></td>
            <td class="border border-gray-300 px-2 py-1">
                {{ number_format($orderDetails->sum('taxes_amount'), 2) }}
            </td>
            <td class="border border-gray-300 px-2 py-1">
                {{ number_format($orderDetails->sum('total_with_taxes'), 2) }}
            </td>
        </tr>
    </tfoot>
</table>
