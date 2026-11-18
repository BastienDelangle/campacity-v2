<?php

namespace App\Filament\Resources\Territories\Pages;

use App\Filament\Resources\Territories\TerritoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTerritories extends ManageRecords
{
    protected static string $resource = TerritoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
