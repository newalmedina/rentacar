<?php

namespace Database\Seeders;

use App\Models\Category; // Asegúrate de tener el modelo Category
use Illuminate\Database\Seeder;

class CategoryDataSeeder extends Seeder
{
    /**
     * Ejecuta las semillas de la base de datos.
     *
     * @return void
     */
    public function run(): void
    {
        // Categorías para alquiler de coches y servicios extra
        $categories = [
            [
                'name' => 'Confort y Pasajero',
                'description' => 'Servicios adicionales para la comodidad de los pasajeros, como sillas de bebé o GPS.',
            ],
            [
                'name' => 'Combustible y Mantenimiento',
                'description' => 'Gastos relacionados con combustible, revisiones, cambios de aceite y limpieza del vehículo.',
            ],
            [
                'name' => 'Seguros y Protección',
                'description' => 'Opciones de seguros adicionales y protecciones especiales para el coche y conductor.',
            ],
            [
                'name' => 'Penalizaciones y Extras Contractuales',
                'description' => 'Multas, penalizaciones por retraso o daños menores al vehículo.',
            ],
            [
                'name' => 'Accesorios y Servicios Opcionales',
                'description' => 'Portaequipajes, cadenas de nieve, WiFi portátil, kits de emergencia y otros extras.',
            ],
        ];

        // Crear o actualizar categorías evitando duplicados
        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']], // condición única
                ['description' => $category['description']] // campos a rellenar si no existe
            );
        }
    }
}
