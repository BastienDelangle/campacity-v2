<?php

namespace App\Filament\Resources\VisibilityProducts;

use App\Enums\VisibilityKind;
use App\Filament\Resources\VisibilityProducts\Pages\ManageVisibilityProducts;
use App\Models\VisibilityProduct;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
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

class VisibilityProductResource extends Resource
{
    protected static ?string $model = VisibilityProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Commercial';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'offre de visibilité';

    protected static ?string $pluralModelLabel = 'offres de visibilité';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->label('Nom')->required()->maxLength(255),
                Select::make('kind')->label('Type d\'offre')->options(VisibilityKind::class)->required()
                    ->disabled(fn (?VisibilityProduct $record) => $record?->purchases()->exists() ?? false)
                    ->helperText('Non modifiable une fois l\'offre achetée.'),
                TextInput::make('price_cents')->label('Prix TTC (€)')->numeric()->minValue(0)->required()
                    ->formatStateUsing(fn (?int $state) => $state !== null ? $state / 100 : null)
                    ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100))
                    ->helperText('Les achats déjà passés conservent leur prix.'),
                TextInput::make('duration_days')->label('Durée (jours)')->numeric()->integer()->minValue(1)->maxValue(366)->required(),
                TextInput::make('ranking_weight')->label('Poids dans le tri')->numeric()->integer()->minValue(0)->maxValue(1000)->default(0)
                    ->helperText('Plus il est élevé, plus la publication remonte dans les résultats (ex. boost 20 > mise en avant 10).'),
                TextInput::make('sort_order')->label('Ordre d\'affichage')->numeric()->integer()->default(0),
                Textarea::make('description')->label('Description')->rows(3)->columnSpanFull(),
                Toggle::make('is_active')->label('Proposée à l\'achat')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('purchases'))
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable(),
                TextColumn::make('kind')->label('Type')->badge(),
                TextColumn::make('price_cents')->label('Prix')->formatStateUsing(fn (VisibilityProduct $record) => $record->formattedPrice()),
                TextColumn::make('duration_days')->label('Durée')->suffix(' j'),
                TextColumn::make('ranking_weight')->label('Poids'),
                TextColumn::make('purchases_count')->label('Achats'),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageVisibilityProducts::route('/')];
    }
}
