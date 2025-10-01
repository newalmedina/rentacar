<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FuelType;

class FuelTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fuelTypes = [
            ['name' => 'Gasoil',       'active' => true],
            ['name' => 'Gasolina 95',  'active' => true],
            ['name' => 'Gasolina 98',  'active' => true],
            ['name' => 'Eléctrico',    'active' => true],
            ['name' => 'Híbrido',      'active' => true],
            ['name' => 'GLP',          'active' => true],
            ['name' => 'GNC',          'active' => true],
            ['name' => 'Otros',        'active' => true],
        ];

        foreach ($fuelTypes as $type) {
            FuelType::firstOrCreate(['name' => $type['name']], $type);
        }
    }
}
