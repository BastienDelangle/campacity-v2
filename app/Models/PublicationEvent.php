<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['starts_at', 'ends_at', 'venue', 'organizer', 'program', 'registration_url'])]
class PublicationEvent extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
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
     * Fin effective de l'événement : date de fin, sinon fin de la journée de début.
     */
    public function endsAtOrEndOfStartDay(): CarbonInterface
    {
        return $this->ends_at ?? $this->starts_at->copy()->endOfDay();
    }
}
