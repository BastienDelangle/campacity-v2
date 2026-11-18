<?php

namespace App\Filament\Resources\Communes;

use App\Filament\Resources\Communes\Pages\ManageCommunes;
use App\Models\Commune;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class CommuneResource extends Resource
{
    protected static ?string $model = Commune::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Référentiels';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'commune';

    protected static ?string $pluralModelLabel = 'communes';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->label('Nom')->required()->maxLength(255),
                Select::make('territory_id')->label('Territoire')->relationship('territory', 'name')->searchable()->preload(),
                TextInput::make('postal_code')->label('Code postal')->length(5)->regex('/^\d{5}$/'),
                TextInput::make('insee_code')->label('Code INSEE')->length(5)->regex('/^[0-9AB]{5}$/')->unique(ignoreRecord: true),
                TextInput::make('latitude')->label('Latitude')->numeric()->minValue(-90)->maxValue(90),
                TextInput::make('longitude')->label('Longitude')->numeric()->minValue(-180)->maxValue(180),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('territory'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('postal_code')->label('Code postal')->searchable(),
                TextColumn::make('insee_code')->label('INSEE')->searchable()->toggleable(),
                TextColumn::make('territory.name')->label('Territoire')->sortable(),
                TextColumn::make('latitude')->label('Lat.')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('longitude')->label('Long.')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('territory_id')->label('Territoire')->relationship('territory', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCommunes::route('/'),
        ];
    }
}
