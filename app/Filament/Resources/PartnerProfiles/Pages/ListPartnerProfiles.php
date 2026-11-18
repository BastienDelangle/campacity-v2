<?php

namespace App\Filament\Resources\PartnerProfiles\Pages;

use App\Filament\Resources\PartnerProfiles\PartnerProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPartnerProfiles extends ListRecords
{
    protected static string $resource = PartnerProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
