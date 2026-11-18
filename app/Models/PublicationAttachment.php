<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Number;

/**
 * Pièce jointe stockée sur le disque privé `local` : elle est servie par un
 * contrôleur qui vérifie que la publication est consultable.
 */
#[Fillable(['path', 'original_name', 'mime_type', 'size'])]
class PublicationAttachment extends Model
{
    /**
     * @return BelongsTo<Publication, $this>
     */
    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }

    public function humanSize(): string
    {
        return Number::fileSize($this->size, precision: 1);
    }
}
