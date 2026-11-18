<?php

namespace App\Filament\Resources\NotificationLogs;

use App\Filament\Resources\NotificationLogs\Pages\ManageNotificationLogs;
use App\Models\NotificationLog;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Historique des notifications envoyées (lecture seule, sans contenu).
 */
class NotificationLogResource extends Resource
{
    protected static ?string $model = NotificationLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'notification envoyée';

    protected static ?string $pluralModelLabel = 'notifications envoyées';

    /**
     * @var array<string, string>
     */
    private const LABELS = [
        'WelcomeNotification' => 'Bienvenue',
        'PublicationSubmittedForReview' => 'Publication en attente',
        'PublicationApproved' => 'Publication en ligne',
        'PublicationRejected' => 'Publication refusée',
        'PublicationSuspended' => 'Publication suspendue',
        'PublicationExpiringSoon' => 'Publication bientôt expirée',
        'NewContactMessage' => 'Message de contact',
        'NewPublicationToModerate' => 'Alerte modération',
        'NewPublicationReport' => 'Alerte signalement',
        'PaymentConfirmed' => 'Confirmation de paiement',
        'MembershipConfirmed' => 'Confirmation d\'adhésion',
        'MembershipExpiryReminder' => 'Rappel d\'expiration d\'adhésion',
        'VerifyEmail' => 'Vérification de l\'e-mail',
        'ResetPassword' => 'Réinitialisation du mot de passe',
    ];

    public static function label(string $class): string
    {
        return self::LABELS[class_basename($class)] ?? class_basename($class);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('notifiable'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('notification')->label('Notification')->formatStateUsing(fn (string $state) => self::label($state)),
                TextColumn::make('notifiable')->label('Destinataire')
                    ->getStateUsing(fn (NotificationLog $record) => $record->notifiable instanceof User ? $record->notifiable->email : '—'),
                TextColumn::make('channel')->label('Canal')->badge()->color('gray')->placeholder('—'),
                TextColumn::make('sent_at')->label('Envoyée le')->dateTime('d/m/Y H:i')->placeholder('En file d\'attente'),
            ])
            ->filters([
                SelectFilter::make('notification')->label('Type')
                    ->options(fn () => NotificationLog::query()->distinct()->pluck('notification')->mapWithKeys(fn ($c) => [$c => self::label($c)])->all()),
                SelectFilter::make('channel')->label('Canal')->options(['mail' => 'E-mail', 'database' => 'Administration']),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageNotificationLogs::route('/')];
    }
}
