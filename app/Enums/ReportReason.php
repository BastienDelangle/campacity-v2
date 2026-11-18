<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ReportReason: string implements HasLabel
{
    case Inappropriate = 'inappropriate';
    case Fraud = 'fraud';
    case Spam = 'spam';
    case Misleading = 'misleading';
    case PersonalData = 'personal_data';
    case Other = 'other';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Inappropriate => 'Contenu inapproprié ou offensant',
            self::Fraud => 'Arnaque ou tentative de fraude',
            self::Spam => 'Spam ou publicité abusive',
            self::Misleading => 'Informations fausses ou trompeuses',
            self::PersonalData => 'Données personnelles exposées',
            self::Other => 'Autre motif',
        };
    }
}
