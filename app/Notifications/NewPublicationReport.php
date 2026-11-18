<?php

namespace App\Notifications;

use App\Filament\Resources\PublicationReports\PublicationReportResource;
use App\Models\PublicationReport;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewPublicationReport extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly PublicationReport $report) {}

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
            ->title('Nouveau signalement')
            ->body($this->report->reason->label().' — '.$this->report->publication->title)
            ->icon('heroicon-o-flag')
            ->danger()
            ->actions([
                Action::make('open')
                    ->label('Traiter')
                    ->url(PublicationReportResource::getUrl('index', panel: 'admin'))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
