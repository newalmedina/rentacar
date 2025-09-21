<x-filament-panels::page>

    {{-- Leyenda de colores --}}
    <div class="flex flex-wrap space-x-4 mt-4 mb-4">
     

        @foreach ($statusListColors as $status => $color)
            @php
                $label = (new \App\Models\Appointment(['status' => $status]))->status_name_formatted;
            @endphp
            <div class="flex items-center space-x-1 mb-2">
                <span class="w-4 h-4 rounded-full" style="background-color: {{ $color }}; margin-right:10px"></span>
                <span class="text-sm" style="margin-right:20px">{{ $label }}</span>
            </div>
        @endforeach
    </div>

{{-- Filtros: Estado, Cliente y Vehiculos --}}
<div class="flex flex-wrap mb-4 -mx-2">
    <div class="w-full sm:w-1/2 lg:w-1/4 px-2 mb-2 lg:mb-0">
        <x-filament-forms::field-wrapper.label>Estado</x-filament-forms::field-wrapper.label>
        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="selectedStatus">
                <option value="">Seleccione estado</option>
                @foreach ($statusList as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>
    </div>

    <div class="w-full sm:w-1/2 lg:w-1/4 px-2 mb-2 lg:mb-0">
        <x-filament-forms::field-wrapper.label>Cliente</x-filament-forms::field-wrapper.label>
        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="selectedCustomer">
                <option value="">Seleccione Cliente</option>
                @foreach ($customerList as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>
    </div>

    <div class="w-full sm:w-1/2 lg:w-1/4 px-2 mb-2 lg:mb-0">
        <x-filament-forms::field-wrapper.label>Vehiculos</x-filament-forms::field-wrapper.label>
        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="selectedItem">
                <option value="">Seleccione vehiculo</option>
                @foreach ($itemsList as $item)
                    <option value="{{ $item->id }}">{{ $item->full_name }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>
    </div>
</div>



    {{-- Widget del calendario --}}
    @livewire(\App\Filament\Widgets\CalendarWidget::class, [
        'selectedStatus' => $selectedStatus,
        'selectedCustomer' => $selectedCustomer,
        'selectedItem' => $selectedItem,
    ], key(str()->random()))

</x-filament-panels::page>
