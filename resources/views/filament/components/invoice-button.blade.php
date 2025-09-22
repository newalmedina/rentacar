@if ($record)
    <form method="POST" action="{{ route('orders.toggleInvoice', $record->id) }}">
        @csrf
        <button type="submit"
            class="px-4 py-2 rounded font-medium
            {{ $record->invoiced ? 'bg-red-500 text-white border-red-500' : 'bg-green-500 text-white border-green-500' }}">
            {{ $record->invoiced ? 'Revertir facturación' : 'Facturar' }}
        </button>
    </form>
@endif
