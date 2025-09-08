<?php

namespace App\Filament\Pages\Settings;

use App\Mail\TestMail;
use App\Models\City;
use App\Models\Country;
use App\Models\OtherExpenseItem;
use App\Models\Setting;
use App\Models\State;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Outerweb\FilamentSettings\Filament\Pages\Settings as BaseSettings;

class Settings extends BaseSettings
{

    protected static ?string $navigationGroup = 'Configuraciones';
    protected static ?int $navigationSort = 80;
    public static function getNavigationLabel(): string
    {
        return 'Información del sitio';
    }
    public function getTitle(): string
    {
        return 'Información del sitio';
    }
    public function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label("Guardar")
                ->submit('data')
                ->keyBindings(['mod+s'])
        ];
    }
    protected function getSavedNotificationTitle(): ?string
    {
        return "Guardado";
    }
    public function schema(): array|Closure
    {

        return [
            Grid::make(12) // Definimos un Grid con 12 columnas en total
                ->schema([
                    // Columna 1: FileUpload, ocupa 3 columnas
                    Section::make()
                        ->columnSpan(3) // Ocupa 3 columnas de las 12 disponibles
                        ->schema([
                            FileUpload::make('general.image') // Suponiendo que el campo de archivo se llama 'file'
                                ->label('Imagen')
                                ->disk('public') // Asegúrate de ajustar el disco que utilizarás
                                ->directory('settings')
                        ]),

                    // Columna 2: Tabs, ocupa 9 columnas
                    Tabs::make('Settings')
                        ->columnSpan(9)
                        ->schema([
                            Tabs\Tab::make('General')
                                ->schema([
                                    Actions::make([
                                        FormAction::make('sendTestEmail')
                                            ->label('Enviar correo de prueba')
                                            ->icon('heroicon-o-envelope')
                                            ->color('warning') // azul
                                            ->action(function (): void {
                                                $email = Auth()->user()->email;

                                                Mail::to(Auth()->user()->email)->send(new TestMail(Auth()->user()));

                                                Notification::make()
                                                    ->title('Correo de prueba enviado a ' . $email)
                                                    ->success()
                                                    ->send();
                                            }),
                                        FormAction::make('downloadOldBackup')
                                            ->label('Descargar BBDD Antigua')
                                            ->icon('heroicon-o-arrow-down')
                                            ->extraAttributes([
                                                'style' => 'background-color: #6B21A8; color: white;' // púrpura y texto blanco
                                            ])
                                            ->visible(fn() => auth()->check() && auth()->user()->email === 'el.solitions@gmail.com')
                                            //  ->visible(false)

                                            ->form([
                                                TextInput::make('filename')
                                                    ->label('Nombre del archivo')
                                                    ->required()
                                                    ->placeholder('Ejemplo: laravel-backup/2025-07-22-19-54-30.zip'),
                                            ])
                                            ->action(function (array $data, $livewire) {
                                                $filename = $data['filename'];

                                                // Validar o sanitizar filename si quieres

                                                // Ruta de descarga generada
                                                $url = route('filament.backups.download', ['filepath' => $filename]);

                                                // Redirigir usando Livewire
                                                $livewire->redirect($url);
                                            }),



                                    ]),

                                    TextInput::make('general.brand_name')->label("Nombre del sitio")
                                        ->required()
                                        ->columnSpan(2), // Ocupa 2 columnas

                                    TextInput::make('general.email')
                                        ->email()
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan(2), // Ocupa 2 columnas

                                    TextInput::make('general.phone')
                                        ->maxLength(255)
                                        ->label('Teléfono')
                                        ->columnSpan(2), // Ocupa 2 columnas

                                    TextInput::make('general.nif')
                                        ->maxLength(255)
                                        ->label('NIF/CIF')
                                        ->columnSpan(2), // Ocupa 2 columnas

                                    TextInput::make('general.bank_name')
                                        ->maxLength(255)
                                        ->label('Entidad Bancaria')
                                        ->helperText('Este campo se usa únicamente para generar facturas.')
                                        ->columnSpan(2), // Ocupa 2 columnas

                                    TextInput::make('general.bank_number')
                                        ->maxLength(255)
                                        ->label('Número cuenta de banco')
                                        ->helperText('Este campo se usa únicamente para generar facturas.')
                                        ->columnSpan(2), // Ocupa 2 columnas
                                    Select::make('general.country_id')
                                        ->options(fn(Get $get): Collection => Country::query()
                                            ->where('is_active', 1)
                                            ->pluck('name', 'id'))
                                        ->searchable()
                                        ->label("País")
                                        ->preload()
                                        ->live()->columnSpan(2)
                                        ->afterStateUpdated(function (Set $set) {
                                            $set('general.state_id', null);
                                            $set('general.city_id', null);
                                        }),

                                    Select::make('general.state_id')
                                        ->options(fn(Get $get): Collection => State::query()
                                            ->where('country_id', $get('general.country_id'))
                                            ->pluck('name', 'id'))
                                        ->searchable()
                                        ->label("Estado")->columnSpan(2)
                                        ->preload()
                                        ->live()
                                        ->afterStateUpdated(fn(Set $set) => $set('general.city_id', null)),

                                    Select::make('general.city_id')
                                        ->options(fn(Get $get): Collection => City::query()
                                            ->where('state_id', $get('general.state_id'))
                                            ->pluck('name', 'id'))
                                        ->searchable()
                                        ->label("Ciudad")->columnSpan(2)
                                        ->preload(),


                                    TextInput::make('general.postal_code')
                                        ->label("Código postal")
                                        ->columnSpan(2), // Ocupa 2 columnas

                                    TextInput::make('general.address')
                                        ->label("Dirección")
                                        ->columnSpan(2), // Ocupa 2 columnas
                                    Toggle::make('general.allow_appointment')
                                        ->inline(false)
                                        ->label("Permitir citas")
                                        ->required(),
                                ]),
                        ]),


                ]),
        ];
    }
}
