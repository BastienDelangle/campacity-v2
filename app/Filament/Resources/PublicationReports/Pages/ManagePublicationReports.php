<?php

namespace App\Filament\Resources\PublicationReports\Pages;

use App\Filament\Resources\PublicationReports\PublicationReportResource;
use Filament\Resources\Pages\ManageRecords;

class ManagePublicationReports extends ManageRecords
{
    protected static string $resource = PublicationReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
