<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Niveau de précision de la localisation affichée publiquement.
 */
enum LocationPrecision: string implements HasLabel
{
    /** Seule la commune est affichée (centre de la commune sur la carte). */
    case Commune = 'commune';
    /** Position arrondie (environ 1 km), adresse masquée. */
    case Approximate = 'approximate';
    /** Adresse et position exactes. */
    case Exact = 'exact';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Commune => 'Commune uniquement',
            self::Approximate => 'Position approximative (≈ 1 km)',
            self::Exact => 'Adresse exacte',
        };
    }
}
