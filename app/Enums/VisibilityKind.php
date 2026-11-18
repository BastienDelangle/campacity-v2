<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Familles d'offres de visibilité. Les prix, durées et poids sont administrables
 * (VisibilityProduct) ; seules les familles sont fixées dans le code.
 */
enum VisibilityKind: string implements HasColor, HasLabel
{
    /** Mise en avant d'une publication. */
    case Featured = 'featured';
    /** Visibilité renforcée d'une publication (règles détaillées à définir). */
    case Boost = 'boost';
    /** Vitrine d'une organisation partenaire premium. */
    case PremiumPartner = 'premium_partner';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Featured => 'Mise en avant',
            self::Boost => 'Boost',
            self::PremiumPartner => 'Partenaire premium',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Featured => 'Mise en avant',
            self::Boost => 'Boostée',
            self::PremiumPartner => 'Partenaire premium',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Featured => 'warning',
            self::Boost => 'danger',
            self::PremiumPartner => 'success',
        };
    }

    public function targetsPublication(): bool
    {
        return $this !== self::PremiumPartner;
    }
}
