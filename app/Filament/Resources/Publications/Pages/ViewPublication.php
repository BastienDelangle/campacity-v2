<?php

namespace App\Filament\Resources\Publications\Pages;

use App\Filament\Resources\Publications\Actions\ModerationActions;
use App\Filament\Resources\Publications\PublicationResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPublication extends ViewRecord
{
    protected static string $resource = PublicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...ModerationActions::all(),
            Action::make('public')
                ->label('Page publique')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn () => route('publications.show', $this->getRecord()->slug), shouldOpenInNewTab: true),
            EditAction::make(),
        ];
    }
}
