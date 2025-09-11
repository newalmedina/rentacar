<?php

namespace Database\Seeders;

use App\Models\OtherExpenseItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OtherExpenseItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            ['name' => 'Ajustes por duración', "default" => 1],
            ['name' => 'Gastos de gestión', "default" => 1],
            ['name' => 'Gestoría', "default" => 1],
            ['name' => 'Limpieza de auto', "default" => 1],
            ['name' => 'Combustible', "default" => 1],
            ['name' => 'Cambio de aceite', "default" => 1],
            ['name' => 'Revisión de frenos', "default" => 1],
            ['name' => 'Neumáticos', "default" => 1],
            ['name' => 'Alineación y balanceo', "default" => 1],
            ['name' => 'Seguros', "default" => 1],
            ['name' => 'Multas de tráfico', "default" => 1],
            ['name' => 'Peajes', "default" => 1],
            ['name' => 'Lavado y detallado', "default" => 1],
            ['name' => 'Accesorios o repuestos', "default" => 1],
            ['name' => 'Asistencia en carretera', "default" => 1],
            ['name' => 'Otros', "default" => 1],
        ];


        foreach ($items as $item) {
            OtherExpenseItem::firstOrCreate($item);
        }
    }
}
