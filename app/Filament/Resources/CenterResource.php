<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CenterResource\Pages;
use App\Models\Center;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class CenterResource extends Resource
{
    protected static ?string $model = Center::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office'; // un ícono más representativo

    protected static ?string $navigationGroup = 'Tablas de sistemas';
    protected static ?int $navigationSort = 6;

    protected static ?string $label = 'Centro';
    protected static ?string $pluralLabel = 'Centros';

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
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('email')
                                    ->label('Correo')
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

                                Forms\Components\TextInput::make('nif')
                                    ->label('NIF/CIF')
                                    ->maxLength(255),

                                Forms\Components\Toggle::make('active')
                                    ->label('¿Activo?')
                                    ->required(),
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
