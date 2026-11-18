<?php

namespace App\Filament\Resources\PublicationTypes;

use App\Filament\Resources\PublicationTypes\Pages\ManagePublicationTypes;
use App\Models\PublicationType;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PublicationTypeResource extends Resource
{
    protected static ?string $model = PublicationType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Référentiels';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'type de publication';

    protected static ?string $pluralModelLabel = 'types de publication';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->label('Nom')->required()->maxLength(255),
                TextInput::make('sort_order')->label('Ordre d\'affichage')->numeric()->integer()->default(0),
                TextInput::make('description')->label('Description')->maxLength(255)->columnSpanFull(),
                Toggle::make('is_event')->label('Type « événement »')->helperText('Active les champs date, lieu, programme, inscription et l\'archivage automatique après la fin.'),
                Toggle::make('is_active')->label('Actif')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('publications'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable(),
                IconColumn::make('is_event')->label('Événement')->boolean(),
                TextColumn::make('publications_count')->label('Publications'),
                IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePublicationTypes::route('/'),
        ];
    }
}
