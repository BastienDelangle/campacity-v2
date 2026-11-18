<?php

namespace App\Filament\Resources\Publications;

use App\Enums\PublicationStatus;
use App\Filament\Resources\Publications\Pages\EditPublication;
use App\Filament\Resources\Publications\Pages\ListPublications;
use App\Filament\Resources\Publications\Pages\ViewPublication;
use App\Filament\Resources\Publications\RelationManagers\ModerationLogsRelationManager;
use App\Filament\Resources\Publications\RelationManagers\ReportsRelationManager;
use App\Filament\Resources\Publications\Schemas\PublicationForm;
use App\Filament\Resources\Publications\Schemas\PublicationInfolist;
use App\Filament\Resources\Publications\Tables\PublicationsTable;
use App\Models\Publication;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PublicationResource extends Resource
{
    protected static ?string $model = Publication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'Contenus';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'publication';

    protected static ?string $pluralModelLabel = 'publications';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationBadge(): ?string
    {
        $pending = Publication::where('status', PublicationStatus::Pending)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'En attente de modération';
    }

    public static function infolist(Schema $schema): Schema
    {
        return PublicationInfolist::configure($schema);
    }

    /**
     * @return list<class-string>
     */
    public static function getRelations(): array
    {
        return [
            ModerationLogsRelationManager::class,
            ReportsRelationManager::class,
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return PublicationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PublicationsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        // Les publications sont créées par les utilisateurs depuis leur espace.
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPublications::route('/'),
            'view' => ViewPublication::route('/{record}'),
            'edit' => EditPublication::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
