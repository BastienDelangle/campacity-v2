<?php

namespace App\Filament\Resources\Memberships;

use App\Enums\MembershipStatus;
use App\Filament\Resources\Memberships\Pages\ManageMemberships;
use App\Models\Membership;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class MembershipResource extends Resource
{
    protected static ?string $model = Membership::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'Association';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'adhésion';

    protected static ?string $pluralModelLabel = 'membres et adhésions';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'payments']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')->label('Membre')->searchable()->description(fn (Membership $record) => $record->user->email),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('starts_at')->label('Début')->date('d/m/Y')->sortable()->placeholder('—'),
                TextColumn::make('ends_at')->label('Fin')->date('d/m/Y')->sortable()->placeholder('—'),
                TextColumn::make('paid_at')->label('Payée le')->date('d/m/Y')->sortable()->placeholder('—'),
                TextColumn::make('amount_cents')->label('Montant')->formatStateUsing(fn (Membership $record) => $record->formattedAmount()),
                TextColumn::make('source')->label('Origine')->badge()->color('gray')
                    ->formatStateUsing(fn (string $state) => $state === 'manual' ? 'Enregistrée' : 'En ligne'),
                TextColumn::make('reference')->label('Référence')->toggleable()
                    ->getStateUsing(fn (Membership $record) => $record->payments->sortByDesc('id')->first()?->provider_reference ?? '—'),
                TextColumn::make('notes')->label('Notes')->limit(40)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(MembershipStatus::class),
                Filter::make('current')->label('Adhérents à jour')->query(fn (Builder $query) => $query->current()),
                Filter::make('expiring')->label('Expire dans les 30 jours')
                    ->query(fn (Builder $query) => $query->current()->where('ends_at', '<=', now()->addDays(30))),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageMemberships::route('/')];
    }
}
