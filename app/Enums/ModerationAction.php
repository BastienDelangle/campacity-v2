<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ModerationAction: string implements HasColor, HasLabel
{
    case Submitted = 'submitted';
    case AutoPublished = 'auto_published';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Suspended = 'suspended';
    case Reinstated = 'reinstated';
    case EditResubmitted = 'edit_resubmitted';
    case Archived = 'archived';
    case Renewed = 'renewed';
    case Expired = 'expired';

    public function getLabel(): string
    {
        return match ($this) {
            self::Submitted => 'Soumise à modération',
            self::AutoPublished => 'Publiée directement (utilisateur de confiance)',
            self::Approved => 'Validée',
            self::Rejected => 'Refusée',
            self::Suspended => 'Suspendue',
            self::Reinstated => 'Rétablie',
            self::EditResubmitted => 'Modifiée, renvoyée en modération',
            self::Archived => 'Archivée',
            self::Renewed => 'Renouvelée',
            self::Expired => 'Expirée',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Approved, self::AutoPublished, self::Reinstated, self::Renewed => 'success',
            self::Rejected, self::Suspended => 'danger',
            self::Submitted, self::EditResubmitted => 'warning',
            default => 'gray',
        };
    }
}
