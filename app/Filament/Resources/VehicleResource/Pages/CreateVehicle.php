<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Filament\Resources\VehicleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateVehicle extends CreateRecord
{
    protected static string $resource = VehicleResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {

        $data['name'] = $data["matricula"];
        $data['type'] = "vehicle";

        return $data;
    }
}
