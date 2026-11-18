<?php

namespace App\Filament\Resources\VisibilityProducts\Pages;

use App\Filament\Resources\VisibilityProducts\VisibilityProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVisibilityProducts extends ManageRecords
{
    protected static string $resource = VisibilityProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
