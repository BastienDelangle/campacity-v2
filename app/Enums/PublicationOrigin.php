<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Origine territoriale d'une publication (rubriques « Côté urbain » / « Côté rural »).
 */
enum PublicationOrigin: string implements HasLabel
{
    case Urban = 'urban';
    case Rural = 'rural';
    case Both = 'both';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Urban => 'Urbain',
            self::Rural => 'Rural',
            self::Both => 'Entre les deux',
        };
    }
}
