<?php

namespace App\Exceptions;

use App\Enums\PublicationStatus;
use RuntimeException;

class InvalidPublicationTransition extends RuntimeException
{
    public static function from(PublicationStatus $status, string $action): self
    {
        return new self("Impossible de « {$action} » une publication au statut « {$status->label()} ».");
    }
}
