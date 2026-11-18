<?php

namespace App\Filament\Resources\Payments;

use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\Pages\ManagePayments;
use App\Models\Payment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Journal des transactions (lecture seule).
 */
class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = 'Commercial';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'paiement';

    protected static ?string $pluralModelLabel = 'paiements';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'confirmer', 'payable']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')->label('Référence')->formatStateUsing(fn ($state) => "CAMP-{$state}")->searchable(),
                TextColumn::make('created_at')->label('Créé le')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('payable_type')->label('Objet')
                    ->formatStateUsing(fn (Payment $record) => $record->payable?->paymentDescription() ?? $record->payable_type)->limit(50),
                TextColumn::make('user.name')->label('Payeur')->searchable()->placeholder('—'),
                TextColumn::make('amount_cents')->label('Montant')->formatStateUsing(fn (Payment $record) => $record->formattedAmount()),
                TextColumn::make('provider')->label('Prestataire')->badge()->color('gray'),
                TextColumn::make('provider_reference')->label('Réf. prestataire')->placeholder('—')->toggleable(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('paid_at')->label('Payé le')->dateTime('d/m/Y H:i')->placeholder('—')->sortable(),
                TextColumn::make('confirmer.name')->label('Confirmé par')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(PaymentStatus::class),
                SelectFilter::make('provider')->label('Prestataire')->options(fn () => Payment::query()->distinct()->pluck('provider', 'provider')->all()),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePayments::route('/')];
    }
}
