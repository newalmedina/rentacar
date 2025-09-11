<?php

namespace App\Filament\Resources\OtherExpenseResource\Pages;

use App\Filament\Resources\OtherExpenseResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditOtherExpense extends EditRecord
{
    protected static string $resource = OtherExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = Auth::user();

        if (isset($this->record) && $user && $this->record->center_id !== $user->center_id) {
            abort(403, 'No tienes permisos para editar este registro.');
        }

        return parent::mutateFormDataBeforeFill($data);
    }
    protected function getCancelFormAction(): Action
    {
        return Action::make('cancel')
            ->label(__('filament-panels::resources/pages/edit-record.form.actions.cancel.label'))
            ->url($this->getResource()::getUrl('index'))
            ->color('gray');
    }
}
