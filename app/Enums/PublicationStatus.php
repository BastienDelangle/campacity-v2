<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PublicationStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';
    case Archived = 'archived';
    case Expired = 'expired';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Pending => 'En attente de modération',
            self::Published => 'Publiée',
            self::Rejected => 'Refusée',
            self::Archived => 'Archivée',
            self::Expired => 'Expirée',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Pending => 'warning',
            self::Published => 'success',
            self::Rejected => 'danger',
            self::Archived, self::Expired => 'gray',
        };
    }

    /**
     * Statuts dont le contenu peut être modifié par l'auteur.
     */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Rejected, self::Published, self::Pending], true);
    }

    /**
     * Statuts « terminés » : la page publique répond 410 (contenu retiré).
     */
    public function isGone(): bool
    {
        return in_array($this, [self::Archived, self::Expired], true);
    }
}
