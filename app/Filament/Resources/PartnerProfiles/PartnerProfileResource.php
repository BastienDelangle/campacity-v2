<?php

namespace App\Filament\Resources\PartnerProfiles;

use App\Filament\Resources\PartnerProfiles\Pages\CreatePartnerProfile;
use App\Filament\Resources\PartnerProfiles\Pages\EditPartnerProfile;
use App\Filament\Resources\PartnerProfiles\Pages\ListPartnerProfiles;
use App\Models\PartnerProfile;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PartnerProfileResource extends Resource
{
    protected static ?string $model = PartnerProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|UnitEnum|null $navigationGroup = 'Commercial';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'partenaire premium';

    protected static ?string $pluralModelLabel = 'partenaires premium';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Vitrine')
                    ->columnSpanFull()
                    ->columns(2)
                    ->components([
                        Select::make('organization_id')->label('Organisation')
                            ->relationship('organization', 'name', fn (Builder $query, ?PartnerProfile $record) => $query->where(fn ($q) => $q->doesntHave('partnerProfile')->when($record, fn ($q) => $q->orWhere('id', $record->organization_id))))
                            ->searchable()->preload()->required()
                            ->disabledOn('edit'),
                        TextInput::make('activity')->label('Activité')->maxLength(255),
                        TextInput::make('headline')->label('Accroche')->maxLength(160)->columnSpanFull(),
                        Textarea::make('presentation')->label('Présentation')->rows(8)->maxLength(8000)->columnSpanFull(),
                        FileUpload::make('cover_path')->label('Image de couverture')->image()->disk('public')->directory('partners')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(5120)->columnSpanFull(),
                    ]),
                Section::make('Période de visibilité')
                    ->description('Prolongée automatiquement à chaque achat payé. Peut être fixée ici pour un partenariat hors offre payante.')
                    ->columnSpanFull()
                    ->columns(2)
                    ->components([
                        DateTimePicker::make('visible_from')->label('Visible à partir du')->native(false),
                        DateTimePicker::make('visible_until')->label('Jusqu\'au')->native(false)->after('visible_from')
                            ->helperText('Vide : sans date de fin.'),
                        Toggle::make('show_on_home')->label('Affiché sur l\'accueil')->default(true),
                        TextInput::make('sort_order')->label('Ordre dans l\'annuaire')->numeric()->integer()->default(0),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('organization'))
            ->defaultSort('visible_until', 'desc')
            ->columns([
                TextColumn::make('organization.name')->label('Organisation')->searchable(),
                IconColumn::make('visible')->label('En ligne')->boolean()->getStateUsing(fn (PartnerProfile $record) => $record->isVisible()),
                TextColumn::make('visible_from')->label('Depuis')->date('d/m/Y')->placeholder('—'),
                TextColumn::make('visible_until')->label('Jusqu\'au')->date('d/m/Y')->placeholder('Sans fin')->sortable(),
                IconColumn::make('show_on_home')->label('Accueil')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('visible')->label('En ligne')
                    ->queries(true: fn ($q) => $q->visible(), false: fn ($q) => $q->whereNotIn('id', PartnerProfile::visible()->select('id')), blank: fn ($q) => $q),
            ])
            ->recordActions([
                Action::make('public')->label('Vitrine')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (PartnerProfile $record) => route('partners.show', $record->organization), shouldOpenInNewTab: true),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPartnerProfiles::route('/'),
            'create' => CreatePartnerProfile::route('/create'),
            'edit' => EditPartnerProfile::route('/{record}/edit'),
        ];
    }
}
