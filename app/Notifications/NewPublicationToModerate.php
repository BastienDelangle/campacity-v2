<?php

namespace App\Notifications;

use App\Filament\Resources\Publications\PublicationResource;
use App\Models\Publication;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Alerte dans l'administration (cloche Filament) pour les modérateurs.
 */
class NewPublicationToModerate extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Publication $publication) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Publication à modérer')
            ->body($this->publication->title)
            ->icon('heroicon-o-newspaper')
            ->warning()
            ->actions([
                Action::make('open')
                    ->label('Examiner')
                    ->url(PublicationResource::getUrl('view', ['record' => $this->publication], panel: 'admin'))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
