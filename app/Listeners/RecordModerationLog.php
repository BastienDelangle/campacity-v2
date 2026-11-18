<?php

namespace App\Listeners;

use App\Events\PublicationStatusChanged;
use App\Models\ModerationLog;

class RecordModerationLog
{
    public function handle(PublicationStatusChanged $event): void
    {
        ModerationLog::create([
            'publication_id' => $event->publication->id,
            'user_id' => $event->actor?->id,
            'action' => $event->action,
            'from_status' => $event->from,
            'to_status' => $event->to,
            'reason' => $event->reason,
        ]);
    }
}
