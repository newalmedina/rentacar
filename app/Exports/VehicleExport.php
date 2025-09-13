<?php

namespace App\Exports;

use App\Models\Vehicle;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class VehicleExport implements FromCollection, WithHeadings, WithMapping
{
    protected $query;

    // Recibe la query para filtrar/exportar
    public function __construct($query)
    {
        $this->query = $query;
    }

    // Ejecuta la query y devuelve la colección
    public function collection()
    {
        return $this->query->get();
    }

    // Mapea cada fila para el Excel
    public function map($vehicle): array
    {
        return [
            $vehicle->matricula,
            $vehicle->active ? 'Sí' : 'No',
            $vehicle->price,
            $vehicle->kilometros,
            $vehicle->brand?->name,
            $vehicle->carModel?->name,
            $vehicle->modelVersion?->name,
            $vehicle->gestion ? 'Sí' : 'No',
            $vehicle->year,
            $vehicle->owner?->name ?? 'N/A',
            $vehicle->description,
        ];
    }

    // Encabezados del Excel
    public function headings(): array
    {
        return [
            'Matrícula',
            'Activo',
            'Precio (€)',
            'Kilómetros',
            'Marca',
            'Modelo',
            'Versión',
            'Vehículo gestión',
            'Año',
            'Propietario',
            'Descripción',
        ];
    }
}
