<?php

namespace App\Events;

use App\Enums\ModerationAction;
use App\Enums\PublicationStatus;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Émis par PublicationLifecycle à chaque transition de statut.
 */
class PublicationStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Publication $publication,
        public readonly ModerationAction $action,
        public readonly ?PublicationStatus $from,
        public readonly PublicationStatus $to,
        public readonly ?User $actor = null,
        public readonly ?string $reason = null,
    ) {}
}
