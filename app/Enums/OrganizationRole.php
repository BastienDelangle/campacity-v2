<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Rôle d'un utilisateur au sein d'une organisation.
 * Owner : gère la fiche et les responsables. Editor : gère la fiche.
 */
enum OrganizationRole: string implements HasLabel
{
    case Owner = 'owner';
    case Editor = 'editor';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Propriétaire',
            self::Editor => 'Éditeur',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $role) => [$role->value => $role->label()])->all();
    }
}
