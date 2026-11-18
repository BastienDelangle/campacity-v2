<?php

namespace App\Console\Commands;

use App\Actions\Publications\PublicationLifecycle;
use App\Enums\PublicationStatus;
use App\Models\Publication;
use App\Notifications\PublicationExpiringSoon;
use App\Support\Settings;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('publications:process-lifecycle')]
#[Description('Archive les événements terminés, expire les publications échues et envoie les rappels d\'expiration')]
class ProcessPublicationLifecycle extends Command
{
    public function handle(PublicationLifecycle $lifecycle, Settings $settings): int
    {
        $archived = 0;
        $expired = 0;
        $notified = 0;

        // 1. Événements terminés → archivés.
        Publication::query()
            ->where('status', PublicationStatus::Published)
            ->whereHas('type', fn ($query) => $query->where('is_event', true))
            ->whereHas('event', fn ($query) => $query->where(fn ($q) => $q
                ->where('ends_at', '<', now())
                ->orWhere(fn ($q) => $q->whereNull('ends_at')->where('starts_at', '<', now()->startOfDay()))))
            ->with('type')
            ->chunkById(100, function ($publications) use ($lifecycle, &$archived) {
                foreach ($publications as $publication) {
                    $lifecycle->archiveFinishedEvent($publication);
                    $archived++;
                }
            });

        // 2. Publications échues → expirées.
        Publication::query()
            ->where('status', PublicationStatus::Published)
            ->where('expires_at', '<=', now())
            ->chunkById(100, function ($publications) use ($lifecycle, &$expired) {
                foreach ($publications as $publication) {
                    $lifecycle->expire($publication);
                    $expired++;
                }
            });

        // 3. Rappel avant expiration (une seule fois par période).
        $noticeDays = $settings->int('publication_expiration_notice_days');

        Publication::query()
            ->where('status', PublicationStatus::Published)
            ->whereNull('expiration_notified_at')
            ->whereBetween('expires_at', [now(), now()->addDays($noticeDays)])
            ->whereDoesntHave('type', fn ($query) => $query->where('is_event', true))
            ->with('author')
            ->chunkById(100, function ($publications) use (&$notified) {
                foreach ($publications as $publication) {
                    if (! $publication->author->isAnonymized()) {
                        $publication->author->notify(new PublicationExpiringSoon($publication));
                    }
                    $publication->forceFill(['expiration_notified_at' => now()])->save();
                    $notified++;
                }
            });

        $this->info("Événements archivés : {$archived} · Publications expirées : {$expired} · Rappels envoyés : {$notified}");

        return self::SUCCESS;
    }
}
