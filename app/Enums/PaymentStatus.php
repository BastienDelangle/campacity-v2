<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Succeeded => 'Payé',
            self::Failed => 'Échoué',
            self::Cancelled => 'Annulé',
            self::Refunded => 'Remboursé',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Succeeded => 'success',
            self::Failed, self::Cancelled => 'danger',
            self::Refunded => 'gray',
        };
    }

    public function isFinal(): bool
    {
        return $this !== self::Pending;
    }
}
