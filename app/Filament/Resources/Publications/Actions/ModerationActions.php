<?php

namespace App\Filament\Resources\Publications\Actions;

use App\Actions\Publications\PublicationLifecycle;
use App\Enums\PublicationStatus;
use App\Exceptions\InvalidPublicationTransition;
use App\Models\Publication;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

/**
 * Actions de modération, partagées entre la liste et la fiche détaillée.
 */
class ModerationActions
{
    /**
     * @return list<Action>
     */
    public static function all(): array
    {
        return [self::approve(), self::reject(), self::suspend(), self::reinstate()];
    }

    public static function approve(): Action
    {
        return Action::make('approve')
            ->label('Valider')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Valider et mettre en ligne ?')
            ->visible(fn (Publication $record) => $record->status === PublicationStatus::Pending && self::canModerate($record))
            ->action(fn (Publication $record, PublicationLifecycle $lifecycle) => self::run(
                fn () => $lifecycle->publish($record, auth()->user()),
                'Publication validée et mise en ligne',
            ));
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label('Refuser')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->modalHeading('Refuser la publication')
            ->modalDescription('Le motif est envoyé à l\'auteur, qui pourra corriger et soumettre à nouveau.')
            ->schema([self::reasonField()])
            ->visible(fn (Publication $record) => $record->status === PublicationStatus::Pending && self::canModerate($record))
            ->action(fn (Publication $record, array $data, PublicationLifecycle $lifecycle) => self::run(
                fn () => $lifecycle->reject($record, auth()->user(), $data['reason']),
                'Publication refusée',
            ));
    }

    public static function suspend(): Action
    {
        return Action::make('suspend')
            ->label('Suspendre')
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->modalHeading('Suspendre la publication')
            ->modalDescription('La publication est retirée immédiatement. L\'auteur est prévenu et ne peut plus la modifier ni la remettre en ligne.')
            ->schema([self::reasonField()])
            ->visible(fn (Publication $record) => in_array($record->status, [PublicationStatus::Published, PublicationStatus::Pending, PublicationStatus::Expired, PublicationStatus::Archived], true) && self::canModerate($record))
            ->action(fn (Publication $record, array $data, PublicationLifecycle $lifecycle) => self::run(
                fn () => $lifecycle->suspend($record, auth()->user(), $data['reason']),
                'Publication suspendue',
            ));
    }

    public static function reinstate(): Action
    {
        return Action::make('reinstate')
            ->label('Rétablir')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (Publication $record) => $record->status === PublicationStatus::Suspended && self::canModerate($record))
            ->action(fn (Publication $record, PublicationLifecycle $lifecycle) => self::run(
                fn () => $lifecycle->reinstate($record, auth()->user()),
                'Publication rétablie',
            ));
    }

    private static function reasonField(): Textarea
    {
        return Textarea::make('reason')
            ->label('Motif (transmis à l\'auteur)')
            ->required()
            ->minLength(10)
            ->maxLength(2000)
            ->rows(4);
    }

    private static function canModerate(Publication $record): bool
    {
        return auth()->user()?->can('moderate', $record) ?? false;
    }

    private static function run(callable $callback, string $success): void
    {
        try {
            $callback();
            Notification::make()->success()->title($success)->send();
        } catch (InvalidPublicationTransition $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();
        }
    }
}
