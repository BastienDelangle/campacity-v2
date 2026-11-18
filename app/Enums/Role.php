<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Rôles de base de la plateforme. Les permissions associées sont en base
 * (spatie/laravel-permission) et peuvent évoluer sans modifier le code.
 */
enum Role: string implements HasLabel
{
    case Admin = 'admin';
    case Moderator = 'moderator';
    case User = 'user';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrateur',
            self::Moderator => 'Modérateur',
            self::User => 'Utilisateur',
        };
    }

    /**
     * Permissions attribuées par défaut lors du seeding.
     * L'administrateur passe tous les contrôles via Gate::before.
     *
     * @return list<Permission>
     */
    public function defaultPermissions(): array
    {
        return match ($this) {
            self::Admin => Permission::cases(),
            self::Moderator => [
                Permission::AccessAdmin,
                Permission::ModeratePublications,
                Permission::ManageReports,
            ],
            self::User => [],
        };
    }
}
