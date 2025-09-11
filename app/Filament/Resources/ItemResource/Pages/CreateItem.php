<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Filament\Resources\ItemResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateItem extends CreateRecord
{
    protected static string $resource = ItemResource::class;
    // Este método se llama antes de crear el registro
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = "service";

        return $data;
    }
}
