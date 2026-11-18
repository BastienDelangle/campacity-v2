<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum OrganizationType: string implements HasLabel
{
    case Association = 'association';
    case Company = 'company';
    case LocalAuthority = 'local_authority';
    case Producer = 'producer';
    case Freelancer = 'freelancer';
    case Other = 'other';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Association => 'Association',
            self::Company => 'Entreprise',
            self::LocalAuthority => 'Collectivité',
            self::Producer => 'Producteur',
            self::Freelancer => 'Indépendant',
            self::Other => 'Autre',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $type) => [$type->value => $type->label()])->all();
    }
}
