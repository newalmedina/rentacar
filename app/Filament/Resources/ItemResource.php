<?php

namespace App\Filament\Resources;

use App\Exports\ItemExport;
use App\Filament\Resources\ItemResource\Pages;
use App\Filament\Resources\ItemResource\RelationManagers;
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

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $navigationIcon = 'fas-box';
    protected static ?string $navigationGroup = 'Tablas de sistemas';
    protected static ?int $navigationSort = 7;
    public static function getModelLabel(): string
    {
        return 'Servicio';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Servicios';
    }

    public static function getEloquentQuery(): Builder
    {

        $query = parent::getEloquentQuery();

        $user = Auth::user();
        // Obtener el ID del panel actual

        $query->where('center_id', $user->center?->id)->where("type", "service");


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
                                    ->directory('services')
                                    ->visibility('public')
                                    ->label('Imagen')
                                    ->helperText('Resolución recomendada: 1000 × 667 píxeles')

                                    ->imageEditor()                        // habilita editor
                                    ->imageResizeMode('cover')              // recorta para llenar el tamaño
                                    ->imageCropAspectRatio('3:2')          // relación 1000x667 ≈ 3:2
                                    ->imageResizeTargetWidth(1000)         // ancho final
                                    ->imageResizeTargetHeight(667),      // alto final
                            ]),

                        Section::make('Información general')
                            ->columnSpan([
                                'default' => 12, // móvil
                                'md' => 9,       // escritorio
                            ])
                            ->schema([
                                Grid::make(12)->schema([
                                    // Fila 1
                                    Forms\Components\TextInput::make('name')
                                        ->label("Nombre")
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
                                    Select::make('category_id')
                                        ->label('Categoría')
                                        ->searchable()
                                        ->preload()
                                        ->options(function () {
                                            return \App\Models\Category::myCenter()->pluck('name', 'id')->toArray();
                                        })
                                        ->required()
                                         ->columnSpan([
                                            'default' => 12, // móvil
                                            'md' => 6,       // escritorio
                                        ]),
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

                                    // Fila 3
                                    Forms\Components\Textarea::make('description')
                                        ->label("Descripción")
                                        ->columnSpan(12),
                                ])
                            ])


                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Imagen')
                    ->size(50) // Tamaño de la imagen en píxeles
                    ->circular() // Hace la imagen circular
                    ->disk('public'), // Especifica el disco 'public'
                Tables\Columns\TextColumn::make('type')
                    ->label("Tipo"),
                Tables\Columns\TextColumn::make('name')
                    ->label("nombre")
                    ->searchable(),



                Tables\Columns\TextColumn::make('price')
                    ->label("Precio")
                    ->money()
                    ->searchable()
                    ->formatStateUsing(fn($state) => number_format($state, 2) . '€')
                    ->sortable(),



                Tables\Columns\TextColumn::make('total_price')  // Utilizando el atributo total_price
                    ->label("Precio Total")
                    // ->searchable()
                    ->formatStateUsing(fn($state) => number_format($state, 2) . '€')  // Formateamos el valor con dos decimales
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->sortable()
                    ->label("Categoría")
                    // ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\IconColumn::make('active')
                    ->label("¿Activo?")

                    ->boolean(),


                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label("Fecha creación")
                    ->formatStateUsing(function ($state) {
                        return Carbon::parse($state)->format('d-m-Y h:i');
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('updated_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('active')
                    ->label("¿Activo?")
                    ->options([
                        '1' => 'Activo',
                        '0' => 'No activo',
                    ]),
                // SelectFilter::make('show_booking')
                //     ->label("Mostrar en reservas")
                //     ->options([
                //         '1' => 'Mostrar',
                //         '0' => 'No mostrar',
                //     ]),
                // SelectFilter::make('show_booking_others')
                //     ->label("Mostrar en reservas de otros")
                //     ->options([
                //         '1' => 'Mostrar',
                //         '0' => 'No mostrar',
                //     ]),
                // SelectFilter::make('type')
                //     ->label("Tipo")
                //     ->options([
                //         'service' => 'Servicios',
                //         'product' => 'Productos',
                //     ]),
                SelectFilter::make('category_id')
                    ->label('Categoría')
                    ->options(fn() => \App\Models\Category::myCenter()->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->preload(),

                // SelectFilter::make('brand_id')
                //     ->relationship(name: 'brand', titleAttribute: 'name')
                //     ->searchable()
                //     ->label("Marca")
                //     ->preload(),
                // SelectFilter::make('supplier_id')
                //     ->relationship(name: 'supplier', titleAttribute: 'name')
                //     ->searchable()
                //     ->label("Proveedor")
                //     ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('')->tooltip('Editar'),
                Tables\Actions\DeleteAction::make()->label('')->tooltip('Eliminar')->visible(fn($record) => $record->canDelete()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //Tables\Actions\DeleteBulkAction::make(),
                ]),
                BulkAction::make('export')->label('Exportar ' . self::getPluralModelLabel())->icon('heroicon-m-arrow-down-tray')
                    ->action(function ($records) {

                        $modelLabel = self::getPluralModelLabel();
                        // Puedes agregar la fecha o cualquier otro dato para personalizar el nombre
                        $fileName = $modelLabel . '-' . now()->format('d-m-Y') . '.xlsx'; // Ejemplo: "Marcas-2025-03-14.xlsx"

                        // Preparamos la consulta para exportar
                        $query = \App\Models\Item::whereIn('id', $records->pluck('id'));

                        // Llamamos al método Excel::download() y pasamos el nombre dinámico del archivo
                        return Excel::download(new ItemExport($query), $fileName);
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
            'index' => Pages\ListItems::route('/'),
            'create' => Pages\CreateItem::route('/create'),
            'edit' => Pages\EditItem::route('/{record}/edit'),
        ];
    }
}
