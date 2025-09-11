<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CenterResource\Pages;
use App\Models\Center;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Customer;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Forms\Components\Markdown;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;

class CenterResource extends Resource
{
    protected static ?string $model = Center::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office'; // un ícono más representativo

    protected static ?string $navigationGroup = 'Tablas de sistemas';
    protected static ?int $navigationSort = 6;

    protected static ?string $label = 'Centro';
    protected static ?string $pluralLabel = 'Centros';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can_show_general_resource == true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(12)
                    ->schema([
                        // Columna para la imagen
                        Section::make('Imagen')
                            ->columnSpan(3)
                            ->schema([
                                Forms\Components\FileUpload::make('image')
                                    ->label('Imagen')
                                    ->image()
                                    ->disk('public')
                                    ->directory('centers')
                                    ->columnSpanFull(),
                            ]),

                        // Columna para los demás campos
                        Section::make('Información general')
                            ->columnSpan(9)
                            ->schema([
                                Grid::make(2) // aquí definimos que todo adentro tendrá 2 columnas
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nombre')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('nif')
                                            ->label('NIF/CIF')->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('email')
                                            ->label('Correo')->required()
                                            ->email()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('phone')
                                            ->label('Teléfono')
                                            ->tel()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('address')
                                            ->label('Dirección')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('postal_code')
                                            ->label('Código postal')
                                            ->maxLength(255),

                                        Forms\Components\Select::make('country_id')
                                            ->label('País')
                                            ->options(fn(): Collection => Country::where('is_active', 1)->pluck('name', 'id'))
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set) {
                                                $set('state_id', null);
                                                $set('city_id', null);
                                            }),

                                        Forms\Components\Select::make('state_id')
                                            ->label('Estado')
                                            ->options(fn(Get $get): Collection => State::where('country_id', $get('country_id'))->pluck('name', 'id'))
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(fn(Set $set) => $set('city_id', null)),

                                        Forms\Components\Select::make('city_id')
                                            ->label('Ciudad')
                                            ->options(fn(Get $get): Collection => City::where('state_id', $get('state_id'))->pluck('name', 'id'))
                                            ->searchable()
                                            ->preload(),

                                        Forms\Components\TextInput::make('bank_name')
                                            ->label('Entidad bancaria')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('bank_number')
                                            ->label('Número de cuenta')
                                            ->maxLength(255),



                                        Forms\Components\Toggle::make('active')
                                            ->label('¿Activo?')
                                            ->required()
                                            ->columnSpanFull(), // este ocupa toda la fila
                                    ]),
                            ]),

                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Imagen'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),

                Tables\Columns\TextColumn::make('nif')
                    ->label('NIF/CIF')
                    ->searchable(),

                Tables\Columns\TextColumn::make('country.name')
                    ->label('País'),

                Tables\Columns\TextColumn::make('state.name')
                    ->label('Estado'),

                Tables\Columns\TextColumn::make('city.name')
                    ->label('Ciudad'),

                Tables\Columns\TextColumn::make('postal_code')
                    ->label('Código postal'),

                Tables\Columns\TextColumn::make('address')
                    ->label('Dirección'),


                Tables\Columns\IconColumn::make('active')
                    ->label('Activo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format('d-m-Y H:i'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                TableAction::make('importDefaultData')
                    ->label('')
                    ->color('warning')
                    ->icon('heroicon-o-server')
                    // Cambia el icono aquí
                    ->modalHeading('Importación de datos')
                    ->tooltip('Importación de datos generales')
                    ->modalWidth('md')
                    ->form([
                        Placeholder::make('notice')
                            ->content('<⚠️ Vas a importar los datos por defecto a este centro')
                            ->columnSpanFull(),

                        Grid::make(12)->schema([
                            CheckboxList::make('import_options')
                                ->label('Selecciona qué importar')
                                ->options([
                                    'clients' => 'Clientes',
                                    'expenses' => 'Gastos Items',
                                    'category' => 'Categorías',
                                ])
                                // ->required()
                                ->columnSpanFull(),
                        ]),
                    ])
                    ->action(function (array $data) {
                        $user = auth()->user();
                        $centerId = $user?->center?->id;

                        if (!$centerId) {
                            Notification::make()
                                ->title('Error')
                                ->danger()
                                ->body('No tienes un centro asignado.')
                                ->send();
                            return;
                        }


                        // Importar clientes
                        if (in_array('clients', $data['import_options'])) {
                            $defaultCustomers = \App\Models\Customer::where('default', 1)->get();

                            foreach ($defaultCustomers as $customer) {
                                \App\Models\Customer::firstOrCreate(
                                    [
                                        'center_id' => $centerId,
                                        'name' => $customer->name, // O el campo que quieras que sea único
                                        'email' => $customer->email,
                                    ],
                                    [
                                        'phone' => $customer->phone,
                                        'address' => $customer->address,
                                        'postal_code' => $customer->postal_code,
                                        'country_id' => $customer->country_id,
                                        'state_id' => $customer->state_id,
                                        'city_id' => $customer->city_id,
                                        'allow_appointment' => $customer->allow_appointment,
                                        'has_home' => $customer->has_home,
                                        'bank_name' => $customer->bank_name,
                                        'bank_number' => $customer->bank_number,
                                        'nif' => $customer->nif,
                                        'active' => $customer->active,
                                    ]
                                );
                            }

                            Notification::make()
                                ->title('Clientes importados correctamente')
                                ->success()
                                ->send();
                        }

                        // Importar items de gastos
                        if (in_array('expenses', $data['import_options'])) {
                            $defaultItems = \App\Models\OtherExpenseItem::where('default', 1)->get();

                            foreach ($defaultItems as $item) {
                                \App\Models\OtherExpenseItem::firstOrCreate(
                                    [
                                        'center_id' => $centerId,
                                        'name' => $item->name,
                                    ],
                                    [
                                        'default' => $item->default,
                                    ]
                                );
                            }

                            Notification::make()
                                ->title('Items importados correctamente')
                                ->success()
                                ->send();
                        }

                        // Importar categorías
                        if (in_array('category', $data['import_options'])) {
                            $defaultCategories = \App\Models\Category::where('default', 1)->get();

                            foreach ($defaultCategories as $category) {
                                \App\Models\Category::firstOrCreate(
                                    [
                                        'center_id' => $centerId,
                                        'name' => $category->name,
                                    ],
                                    [
                                        'description' => $category->description,
                                    ]
                                );
                            }

                            Notification::make()
                                ->title('Categorías importadas correctamente')
                                ->success()
                                ->send();
                        }

                        Notification::make()
                            ->title('Importación completada')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make()->label('')->tooltip('Editar'),
                Tables\Actions\DeleteAction::make()->label('')->tooltip('Eliminar'),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCenters::route('/'),
            'create' => Pages\CreateCenter::route('/create'),
            'edit' => Pages\EditCenter::route('/{record}/edit'),
        ];
    }
}
