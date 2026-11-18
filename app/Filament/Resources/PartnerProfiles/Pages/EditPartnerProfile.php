<?php

namespace App\Filament\Resources\PartnerProfiles\Pages;

use App\Filament\Resources\PartnerProfiles\PartnerProfileResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPartnerProfile extends EditRecord
{
    protected static string $resource = PartnerProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
