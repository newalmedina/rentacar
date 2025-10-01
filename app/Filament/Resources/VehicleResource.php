<?php

namespace App\Filament\Resources;

use App\Exports\ItemExport;
use App\Exports\VehicleExport;
use App\Filament\Resources\VehicleResource\Pages;
use App\Filament\Resources\VehicleResource\RelationManagers;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Item;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class VehicleResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Tablas de sistemas';
    protected static ?int $navigationSort = 6;
    public static function getModelLabel(): string
    {
        return 'Vehiculo';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Vehiculos';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = Auth::user();
        // Obtener el ID del panel actual

        $query->where('center_id', $user->center?->id)->where("type", "vehicle");


        return $query;
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(12) // Definimos un Grid con 12 columnas en total
                    ->schema([
                        Section::make()
                            ->columnSpan([
                                'default' => 12, // móvil
                                'md' => 3,       // escritorio
                            ])
                            ->schema([
                                FileUpload::make('image')
                                    ->image()
                                    ->directory('vehicles')
                                    ->visibility('public')
                                    ->label('Imagen')
                                    ->helperText('Resolución recomendada: 1536 × 1024 píxeles')

                                    ->imageEditor()                        // habilita editor
                                    ->imageResizeMode('cover')              // recorta para llenar el tamaño
                                    ->imageCropAspectRatio('3:2')          // relación 1000x667 ≈ 3:2
                                    ->imageResizeTargetWidth(1536)         // ancho final
                                    ->imageResizeTargetHeight(1024),    // alto final
                            ]),

                        Section::make('Información general')
                            ->columnSpan([
                                'default' => 12, // móvil
                                'md' => 9,       // escritorio
                            ])
                            ->schema([
                                Grid::make(12)->schema([
                                    // Fila 1
                                    Forms\Components\TextInput::make('matricula')
                                        ->label("Matricula")
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan([
                                            'default' => 12, // móvil
                                            'md' => 6,       // escritorio
                                        ]), // mitad del ancho

                                    Forms\Components\Toggle::make('active')
                                        ->inline(false)
                                        ->label("¿Activo?")
                                        ->required()
                                        ->columnSpan([
                                            'default' => 12, // móvil
                                            'md' => 6,       // escritorio
                                        ]), // mitad del ancho

                                    // Fila 2
                                    Forms\Components\TextInput::make('price')
                                        ->label("Precio")
                                        ->numeric()
                                        ->prefix('€')
                                        ->reactive()
                                        ->debounce(750)
                                        ->afterStateUpdated(fn($state, $get, $set) => self::updateCalculatedFields($get, $set))
                                        ->columnSpan([
                                            'default' => 12, // móvil
                                            'md' => 6,       // escritorio
                                        ]),

                                    Grid::make(12)
                                        ->columnSpan([
                                            'default' => 12, // móvil
                                        ])->schema([
                                            Forms\Components\TextInput::make('kilometros')
                                                ->label("Kilómetros")
                                                ->numeric()
                                                ->nullable()
                                                ->columnSpan([
                                                    'default' => 12, // móvil
                                                    'md' => 6,       // escritorio
                                                ]),
                                            Forms\Components\TextInput::make('kilometros_recorridos')
                                                ->label('Kilómetros recorridos en alquileres')
                                                ->disabled() // Solo lectura
                                                ->columnSpan([
                                                    'default' => 12,
                                                    'md' => 6,       // mitad de ancho en escritorio
                                                ])
                                                ->afterStateHydrated(function ($component, $state, $record) {
                                                    $value = $record?->total_kilometros_recorridos;
                                                    // Si hay valor, formatea con 2 decimales y añade ' km', si no, muestra '-'
                                                    $component->state($value !== null ? number_format($value, 2) . ' km' : '-');
                                                })

                                        ]),

                                    // Fila 3
                                    Select::make('brand_id')
                                        ->label('Marca')
                                        ->options(Brand::active()->pluck('name', 'id'))
                                        ->required()
                                        ->preload()
                                        ->searchable()
                                        ->reactive() // Permite que el siguiente select se actualice
                                        ->afterStateUpdated(fn($set) => $set('model_id', null))
                                        ->columnSpan([
                                            'default' => 12, // móvil
                                            'md' => 4,       // escritorio
                                        ]),

                                    // Selección de Modelo filtrado por marca
                                    Select::make('model_id')
                                        ->label('Modelo')
                                        ->options(function (callable $get) {
                                            $brandId = $get('brand_id');
                                            if (!$brandId) return [];
                                            return CarModel::where('brand_id', $brandId)->pluck('name', 'id');
                                        })
                                        ->required()
                                        ->preload()
                                        ->searchable()
                                        ->columnSpan([
                                            'default' => 12, // móvil
                                            'md' => 4,       // escritorio
                                        ]),

                                    Forms\Components\Select::make('car_version_id')
                                        ->label("Versión")
                                        ->relationship('modelVersion', 'name', function ($query, callable $get) {
                                            $modelId = $get('model_id');

                                            $query->where('model_id', $modelId);
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->columnSpan([
                                            'default' => 12, // móvil
                                            'md' => 4,       // escritorio
                                        ]),
                                    Forms\Components\Select::make('fuel_type_id')
                                        ->label('Tipo de combustible')
                                        ->relationship('fuelType', 'name', fn($query) => $query->active())
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->columnSpan([
                                            'default' => 12, // móvil
                                            'md' => 4,       // escritorio
                                        ]),

                                    // Fila 2
                                    Forms\Components\TextInput::make('year')
                                        ->label("Año")
                                        ->numeric()
                                        ->reactive()
                                        ->columnSpan([
                                            'default' => 12, // móvil
                                            'md' => 4,       // escritorio
                                        ]),
                                    // Fila 4
                                    Forms\Components\Toggle::make('gestion')
                                        ->inline(false)
                                        ->label("¿Coche gestión?")
                                        ->default(false) // Valor por defecto
                                        ->reactive() // <--- Esto es clave
                                        ->columnSpan([
                                            'default' => 12, // móvil
                                            'md' => 6,       // escritorio
                                        ]),

                                    Forms\Components\Select::make('owner_id')
                                        ->label("Propietario")
                                        ->options(function () {
                                            return \App\Models\Owner::query()
                                                ->where('active', true)
                                                ->where('center_id', Auth::user()->center_id)

                                                ->get()
                                                ->mapWithKeys(fn($owner) => [
                                                    $owner->id => $owner->name . ' (' . $owner->identification . ')'
                                                ])
                                                ->toArray();
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->nullable()
                                        ->columnSpan([
                                            'default' => 12, // móvil
                                            'md' => 6,       // escritorio
                                        ])
                                        ->visible(fn($get) => $get('gestion')) // visible solo si no soy propietario
                                        ->required(fn($get) => $get('gestion')), // obligatorio solo si no soy propietario



                                    Forms\Components\Textarea::make('description')
                                        ->label("Descripción")
                                        ->columnSpan([
                                            'default' => 12, // móvil
                                            'md' => 12,       // escritorio
                                        ]), // ocupa todo el ancho
                                ])
                            ])



                    ]),
            ]);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Imagen
                Tables\Columns\ImageColumn::make('image')
                    ->label('Imagen')
                    ->size(50)
                    ->circular()
                    ->disk('public'),

                // Tipo de item
                Tables\Columns\TextColumn::make('type')
                    ->label("Tipo"),

                // Matricula
                Tables\Columns\TextColumn::make('matricula')
                    ->label("Matricula")
                    ->searchable(),

                // Precio
                Tables\Columns\TextColumn::make('price')
                    ->label("Precio")
                    ->formatStateUsing(fn($state) => $state ? number_format($state, 2) . '€' : '-')
                    ->sortable()
                    ->searchable(),

                // // Precio total
                // Tables\Columns\TextColumn::make('total_price')
                //     ->label("Precio Total")
                //     ->formatStateUsing(fn($state) => $state ? number_format($state, 2) . '€' : '-')
                //     ->sortable(),
                Tables\Columns\TextColumn::make('total_kilometros_recorridos')
                    ->label('Kilómetros recorridos')
                    ->formatStateUsing(fn($state) => $state !== null ? number_format($state, 2) . ' km' : '-')
                    ->toggleable(),
                // Marca, Modelo y Versión
                Tables\Columns\TextColumn::make('brand.name')
                    ->label("Marca")
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('carModel.name')
                    ->label("Modelo")
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('modelVersion.name')
                    ->label("Versión")
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fuelType.name')
                    ->label("Tipo Combustible")
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('year')
                    ->label("Año")
                    ->searchable()
                    ->sortable(),

                // Activo
                Tables\Columns\IconColumn::make('active')
                    ->label("¿Activo?")
                    ->boolean(),

                // Gestión / Propietario
                Tables\Columns\IconColumn::make('gestion')
                    ->label("¿Propietario?")
                    ->boolean(),

                // Propietario
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Propietario')
                    ->formatStateUsing(fn($record) => $record->owner ? $record->owner->name . ' (' . $record->owner->identification . ')' : '-')
                    ->toggleable(),

                // Fecha de creación
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label("Fecha creación")
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format('d-m-Y H:i'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('active')
                    ->label("¿Activo?")
                    ->options([
                        '1' => 'Activo',
                        '0' => 'No activo',
                    ]),
                // SelectFilter::make('brand_id')
                //     ->relationship('brand', 'name')
                //     ->searchable()
                //     ->label("Marca")
                //     ->preload(),
                // SelectFilter::make('model_id')
                //     ->relationship('carModel', 'name')
                //     ->searchable()
                //     ->label("Modelo")
                //     ->preload(),
                // SelectFilter::make('car_version_id')
                //     ->relationship('modelVersion', 'name')
                //     ->searchable()
                //     ->label("Versión")
                //     ->preload(),
                SelectFilter::make('fuel_type_id')
                    ->label('Tipo de combustible')
                    ->relationship('fuelType', 'name', fn($query) => $query->active())
                    ->searchable()
                    ->preload(),
                SelectFilter::make('gestion')
                    ->label('Gestión')
                    ->options([
                        1 => 'Sí',
                        0 => 'No',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('')->tooltip('Editar'),
                Tables\Actions\DeleteAction::make()->label('')->tooltip('Eliminar')
            ])
            ->bulkActions([
                BulkAction::make('export')
                    ->label('Exportar ' . self::getPluralModelLabel())
                    ->icon('heroicon-m-arrow-down-tray')
                    ->action(function ($records) {
                        $fileName = self::getPluralModelLabel() . '-' . now()->format('d-m-Y') . '.xlsx';
                        $query = \App\Models\Item::whereIn('id', $records->pluck('id'));
                        return Excel::download(new VehicleExport($query), $fileName);
                    }),
            ]);
    }



    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Función para actualizar los campos calculados en tiempo real
     */
    public static function updateCalculatedFields($get, $set)
    {
        $price = round((float) $get('price'), 2);
        $taxes = round((float) $get('taxes'), 2);

        $taxAmount = round(($price * $taxes) / 100, 2);
        $totalPrice = round($price + $taxAmount, 2);

        $set('taxes_amount', $taxAmount);
        $set('total_price', $totalPrice);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}
