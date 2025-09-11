<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CarModelResource\Pages;
use App\Models\CarModel;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\BulkAction;
use Maatwebsite\Excel\Facades\Excel;

class CarModelResource extends Resource
{
    protected static ?string $model = CarModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Tablas de sistemas';
    protected static ?int $navigationSort = 13;

    public static function getModelLabel(): string
    {
        return 'Modelo';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Modelos';
    }
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

                        // Selección de Marca
                        Forms\Components\Select::make('brand_id')
                            ->label('Marca')
                            ->relationship('brand', 'name')
                            ->required()
                            ->preload()
                            ->searchable()
                            ->columnSpanFull(),

                        // Nombre del modelo
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        // Descripción
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->columnSpanFull(),

                        // Activo
                        Forms\Components\Toggle::make('active')
                            ->inline(false)
                            ->label('¿Activo?')
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('brand.name')
                    ->label('Marca')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),

                IconColumn::make('active')
                    ->label('¿Activo?')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Fecha creación')
                    ->dateTime()
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format('d-m-Y H:i'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('')->tooltip('Editar'),
                Tables\Actions\DeleteAction::make()->label('')->tooltip('Eliminar'),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     BulkAction::make('export')
                //         ->label('Exportar ' . self::getPluralModelLabel())
                //         ->icon('heroicon-m-arrow-down-tray')
                //         ->action(function ($records) {
                //             $fileName = self::getPluralModelLabel() . '-' . now()->format('d-m-Y') . '.xlsx';
                //             $query = CarModel::whereIn('id', $records->pluck('id'));
                //             return Excel::download(new \App\Exports\CarModelExport($query), $fileName);
                //         }),
                // ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Aquí puedes agregar la relación con ModelVersion si quieres verlas en Filament
            // \App\Filament\Resources\ModelVersionResource\RelationManagers\ModelVersionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCarModels::route('/'),
            // 'create' => Pages\CreateCarModel::route('/create'),
            // 'edit' => Pages\EditCarModel::route('/{record}/edit'),
        ];
    }
}
