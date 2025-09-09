<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\ModelVersion;

class CarDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = public_path('docs/car-data');

        // Buscar todos los CSV en la carpeta
        $csvFiles = File::files($path);

        foreach ($csvFiles as $file) {
            if ($file->getExtension() !== 'csv') continue;

            $handle = fopen($file->getRealPath(), 'r');
            $header = fgetcsv($handle); // Leer cabecera

            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($header, $row);

                $year = $data['year'];
                $make = $data['make'];
                $modelName = $data['model'];
                $bodyStyles = json_decode($data['body_styles'], true);

                // 1️⃣ Crear o buscar marca
                $brand = Brand::firstOrCreate(
                    ['name' => $make],
                    [
                        'slug' => \Str::slug($make),
                        'description' => null,
                        'active' => true,
                    ]
                );

                // 2️⃣ Crear o buscar modelo
                $carModel = CarModel::firstOrCreate(
                    [
                        'brand_id' => $brand->id,
                        'name' => $modelName,
                    ],
                    [
                        'slug' => \Str::slug($modelName),
                        'description' => null,
                        'active' => true,
                    ]
                );

                // 3️⃣ Crear versiones (body_styles)
                if (is_array($bodyStyles)) {
                    foreach ($bodyStyles as $style) {
                        ModelVersion::firstOrCreate(
                            [
                                'model_id' => $carModel->id,
                                'name' => $style,
                            ],
                            [
                                'slug' => \Str::slug($style),
                                'description' => null,
                                'active' => true,
                            ]
                        );
                    }
                }
            }

            fclose($handle);
        }
    }
}
