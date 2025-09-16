<?php

namespace App\Filament\Pages;

use App\Mail\TestMail;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Components\{Actions, TextInput, FileUpload, Toggle, DatePicker, Radio, Select, Section, Grid};
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\Actions\Action as FormAction;
use Illuminate\Support\Facades\Mail;

class Configuration extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $navigationLabel = 'Información del sitio';
    protected static bool $shouldRegisterNavigation = true;

    protected static string $view = 'filament.pages.configuration';

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

    public ?array $data = [];

    public function mount()
    {
        $user = Auth::user();
        $center = $user->center; // Relación con el centro

        if ($center) {
            $this->form->fill([
                'name' => $center->name,
                'nif' => $center->nif,
                'email' => $center->email,
                'phone' => $center->phone,
                'address' => $center->address,
                'postal_code' => $center->postal_code,
                'country_id' => $center->country_id,
                'state_id' => $center->state_id,
                'city_id' => $center->city_id,
                'bank_name' => $center->bank_name,
                'bank_number' => $center->bank_number,
                'active' => $center->active,
                'image' => $center->image,
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(12)
                    ->schema([
                        Section::make('Imagen')
                            ->columnSpan([
                                'default' => 12, // móvil
                                'md' => 9,       // escritorio
                            ])
                            ->schema([
                                FileUpload::make('image')
                                    ->label('Imagen')
                                    ->image()
                                    ->disk('public')
                                    ->directory('centers')
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Información general')
                            ->columnSpan([
                                'default' => 12, // móvil
                                'md' => 9,       // escritorio
                            ])
                            ->schema([
                                Grid::make(2)
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
                                                ->visible(fn() => auth()->check() && auth()->user()?->can_show_general_resource == true)
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
                                        TextInput::make('name')->label('Nombre')->required()->maxLength(255),
                                        TextInput::make('nif')->label('NIF/CIF')->required()->maxLength(255),
                                        TextInput::make('email')->label('Correo')->required()->email()->maxLength(255),
                                        TextInput::make('phone')->label('Teléfono')->tel()->maxLength(255),
                                        TextInput::make('address')->label('Dirección')->maxLength(255),
                                        TextInput::make('postal_code')->label('Código postal')->maxLength(255),

                                        Select::make('country_id')
                                            ->label('País')
                                            ->options(fn() => \App\Models\Country::pluck('name', 'id'))
                                            ->searchable(),

                                        Select::make('state_id')
                                            ->label('Estado')
                                            ->options(fn(Get $get) => \App\Models\State::where('country_id', $get('country_id'))->pluck('name', 'id'))
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set) => $set('city_id', null)),

                                        Select::make('city_id')
                                            ->label('Cidade')
                                            ->options(fn(Get $get) => \App\Models\City::where('state_id', $get('state_id'))->pluck('name', 'id'))
                                            ->searchable()
                                            ->preload()
                                            ->live(),

                                        TextInput::make('bank_name')->label('Entidad bancaria')->maxLength(255),
                                        TextInput::make('bank_number')->label('Número de cuenta')->maxLength(255),

                                        // Toggle::make('active')->label('¿Activo?')->required()->columnSpanFull(),
                                    ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save()
    {
        $user = Auth::user();
        $center = $user->center;

        if (!$center) {
            Notification::make()
                ->title('El usuario no tiene un centro asignado.')
                ->danger()
                ->send();
            return;
        }

        $data = $this->form->getState();

        $center->update($data);

        Notification::make()
            ->title('Centro actualizado correctamente')
            ->success()
            ->send();
    }
}
