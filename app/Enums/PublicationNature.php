<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Nature d'une publication, indépendante de son type.
 */
enum PublicationNature: string implements HasLabel
{
    case Offer = 'offer';
    case Request = 'request';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Offer => 'Offre',
            self::Request => 'Demande',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Offer => 'Je propose quelque chose',
            self::Request => 'Je recherche quelque chose',
        };
    }
}
