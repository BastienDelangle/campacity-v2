<?php

namespace App\Filament\Resources\VisibilityPurchases;

use App\Actions\Visibility\VisibilityPurchases;
use App\Enums\PaymentStatus;
use App\Enums\PurchaseStatus;
use App\Enums\VisibilityKind;
use App\Filament\Resources\VisibilityPurchases\Pages\ManageVisibilityPurchases;
use App\Models\VisibilityPurchase;
use App\Payments\PaymentManager;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use UnitEnum;

class VisibilityPurchaseResource extends Resource
{
    protected static ?string $model = VisibilityPurchase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'Commercial';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'achat de visibilité';

    protected static ?string $pluralModelLabel = 'achats de visibilité';

    public static function getNavigationBadge(): ?string
    {
        $pending = VisibilityPurchase::where('status', PurchaseStatus::PendingPayment)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'publication', 'partnerProfile.organization', 'payments']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Commandé le')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('product_name')->label('Offre')->searchable(),
                TextColumn::make('kind')->label('Type')->badge()->toggleable(),
                TextColumn::make('target')->label('Pour')->limit(40)
                    ->getStateUsing(fn (VisibilityPurchase $record) => $record->publication?->title ?? $record->partnerProfile?->organization?->name),
                TextColumn::make('user.name')->label('Acheteur')->searchable()->description(fn (VisibilityPurchase $record) => $record->user->email),
                TextColumn::make('amount_cents')->label('Montant')->formatStateUsing(fn (VisibilityPurchase $record) => $record->formattedAmount()),
                TextColumn::make('reference')->label('Référence')->getStateUsing(fn (VisibilityPurchase $record) => ($p = $record->payments->sortByDesc('id')->first()) ? "CAMP-{$p->id}" : '—')->copyable(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('starts_at')->label('Début')->date('d/m/Y')->placeholder('—')->sortable(),
                TextColumn::make('ends_at')->label('Fin')->date('d/m/Y')->placeholder('—')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(PurchaseStatus::class),
                SelectFilter::make('kind')->label('Type')->options(VisibilityKind::class),
            ])
            ->recordActions([
                Action::make('confirmPayment')
                    ->label('Paiement reçu')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->modalHeading('Confirmer la réception du paiement')
                    ->modalDescription('L\'offre sera activée immédiatement et l\'acheteur recevra une confirmation.')
                    ->visible(fn (VisibilityPurchase $record) => $record->status === PurchaseStatus::PendingPayment
                        && $record->latestPayment()?->status === PaymentStatus::Pending
                        && $record->latestPayment()?->provider === 'manual'
                        && auth()->user()->can('confirmPayment', $record))
                    ->schema([
                        TextInput::make('reference')->label('Référence du règlement (n° de virement, de chèque…)')->required()->maxLength(255),
                        Textarea::make('note')->label('Note interne')->maxLength(1000),
                    ])
                    ->action(function (VisibilityPurchase $record, array $data, PaymentManager $payments): void {
                        try {
                            $payments->markSucceeded($record->latestPayment(), 'manual-'.$data['reference'], auth()->user(), $data['note'] ?? null);
                            Notification::make()->success()->title('Paiement confirmé, offre activée')->send();
                        } catch (InvalidArgumentException $exception) {
                            Notification::make()->danger()->title($exception->getMessage())->send();
                        }
                    }),
                Action::make('cancel')
                    ->label('Annuler')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (VisibilityPurchase $record) => $record->status === PurchaseStatus::PendingPayment && auth()->user()->can('cancel', $record))
                    ->action(function (VisibilityPurchase $record, VisibilityPurchases $purchases, PaymentManager $payments): void {
                        try {
                            $purchases->cancel($record);
                            if ($payment = $record->latestPayment()) {
                                $payments->markFailed($payment, PaymentStatus::Cancelled, 'Annulé par l\'administration');
                            }
                            Notification::make()->success()->title('Commande annulée')->send();
                        } catch (ValidationException $exception) {
                            Notification::make()->danger()->title(collect($exception->errors())->flatten()->first())->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageVisibilityPurchases::route('/')];
    }
}
