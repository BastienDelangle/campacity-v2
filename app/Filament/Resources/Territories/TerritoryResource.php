<?php

namespace App\Filament\Resources\Territories;

use App\Filament\Resources\Territories\Pages\ManageTerritories;
use App\Models\Territory;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class TerritoryResource extends Resource
{
    protected static ?string $model = Territory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|UnitEnum|null $navigationGroup = 'Référentiels';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'territoire';

    protected static ?string $pluralModelLabel = 'territoires';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->label('Nom')->required()->maxLength(255),
                Toggle::make('is_active')->label('Actif')->default(true)->inline(false),
                Textarea::make('description')->label('Description')->rows(3)->columnSpanFull(),
                TextInput::make('center_latitude')->label('Latitude du centre (carte)')->numeric()->minValue(-90)->maxValue(90),
                TextInput::make('center_longitude')->label('Longitude du centre (carte)')->numeric()->minValue(-180)->maxValue(180),
                TextInput::make('default_zoom')->label('Zoom par défaut')->numeric()->integer()->minValue(1)->maxValue(18),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('communes'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('communes_count')->label('Communes')->sortable(),
                IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription('Les communes rattachées seront conservées, sans territoire.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTerritories::route('/'),
        ];
    }
}
