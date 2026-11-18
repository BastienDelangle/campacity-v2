<?php

namespace App\Models;

use App\Enums\ModerationAction;
use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historique des transitions de statut d'une publication (journal immuable).
 */
#[Fillable(['publication_id', 'user_id', 'action', 'from_status', 'to_status', 'reason'])]
class ModerationLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => ModerationAction::class,
            'from_status' => PublicationStatus::class,
            'to_status' => PublicationStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Publication, $this>
     */
    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
