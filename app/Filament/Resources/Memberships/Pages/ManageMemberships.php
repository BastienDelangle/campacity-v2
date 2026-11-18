<?php

namespace App\Filament\Resources\Memberships\Pages;

use App\Actions\Memberships\Memberships;
use App\Filament\Resources\Memberships\MembershipResource;
use App\Models\Membership;
use App\Models\User;
use App\Support\Settings;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Carbon;

class ManageMemberships extends ManageRecords
{
    protected static string $resource = MembershipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('record')
                ->label('Enregistrer une adhésion')
                ->icon('heroicon-o-plus')
                ->modalDescription('Pour une cotisation réglée hors ligne (espèces, chèque, virement). Les règles de période et de renouvellement s\'appliquent.')
                ->visible(fn () => auth()->user()->can('record', Membership::class))
                ->schema([
                    Select::make('user_id')->label('Membre')
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search) => User::whereNull('anonymized_at')
                            ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                            ->limit(20)->get()->mapWithKeys(fn (User $u) => [$u->id => "{$u->name} ({$u->email})"]))
                        ->getOptionLabelUsing(fn ($value) => User::find($value)?->name)
                        ->required(),
                    DatePicker::make('paid_at')->label('Date de paiement')->default(now())->maxDate(now())->required()->native(false),
                    TextInput::make('amount')->label('Montant (€)')->numeric()->minValue(0)->required()
                        ->default(fn () => app(Settings::class)->int('membership_fee_cents') / 100),
                    TextInput::make('reference')->label('Référence du règlement')->required()->maxLength(255),
                    Textarea::make('notes')->label('Notes')->maxLength(1000),
                ])
                ->action(function (array $data, Memberships $memberships): void {
                    $membership = $memberships->recordManual(
                        User::findOrFail($data['user_id']),
                        Carbon::parse($data['paid_at'])->setTimeFrom(now()),
                        (int) round(((float) $data['amount']) * 100),
                        $data['reference'],
                        auth()->user(),
                        $data['notes'] ?? null,
                    );

                    Notification::make()->success()
                        ->title('Adhésion enregistrée')
                        ->body("Valable du {$membership->starts_at->format('d/m/Y')} au {$membership->ends_at->format('d/m/Y')}.")
                        ->send();
                }),
        ];
    }
}
