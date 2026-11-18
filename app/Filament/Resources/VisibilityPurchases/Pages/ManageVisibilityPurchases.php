<?php

namespace App\Filament\Resources\VisibilityPurchases\Pages;

use App\Filament\Resources\VisibilityPurchases\VisibilityPurchaseResource;
use Filament\Resources\Pages\ManageRecords;

class ManageVisibilityPurchases extends ManageRecords
{
    protected static string $resource = VisibilityPurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
