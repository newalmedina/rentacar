<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ModelVersionResource\Pages;
use App\Models\ModelVersion;
use App\Models\CarModel;
use App\Models\Brand;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
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

class ModelVersionResource extends Resource
{
    protected static ?string $model = ModelVersion::class;

    // protected static ?string $navigationIcon = 'heroicon-o-collection';
    protected static ?string $navigationGroup = 'Tablas de sistemas';
    protected static ?int $navigationSort = 14;

    public static function getModelLabel(): string
    {
        return 'Versión';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Versiones';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(12)
                    ->schema([

                        // Selección de Marca
                        Select::make('brand_id')
                            ->label('Marca')
                            ->options(Brand::active()->pluck('name', 'id'))
                            ->required()
                            ->preload()
                            ->searchable()
                            ->reactive() // Permite que el siguiente select se actualice
                            ->afterStateUpdated(fn($set) => $set('model_id', null))
                            ->columnSpanFull(),

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
                            ->columnSpanFull(),

                        TextInput::make('name')
                            ->label('Nombre de la versión')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        // TextInput::make('slug')
                        //     ->label('Slug')
                        //     ->disabled()
                        //     ->maxLength(255)
                        //     ->helperText('Se generará automáticamente si está vacío.')
                        //     ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Descripción')
                            ->columnSpanFull(),

                        Toggle::make('active')
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

                TextColumn::make('model.name')
                    ->label('Modelo')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Versión')
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
                    ->sortable(),
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
                //             $query = ModelVersion::whereIn('id', $records->pluck('id'));
                //             return Excel::download(new \App\Exports\ModelVersionExport($query), $fileName);
                //         }),
                // ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListModelVersions::route('/'),
            // 'create' => Pages\CreateModelVersion::route('/create'),
            // 'edit' => Pages\EditModelVersion::route('/{record}/edit'),
        ];
    }
}
