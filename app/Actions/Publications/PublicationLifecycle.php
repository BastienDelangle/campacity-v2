<?php

namespace App\Actions\Publications;

use App\Enums\PublicationStatus;
use App\Exceptions\InvalidPublicationTransition;
use App\Models\Publication;
use App\Models\User;
use App\Support\Settings;
use Carbon\CarbonInterface;

/**
 * Transitions de statut d'une publication. Toute la logique du cycle de vie
 * est ici : les contrôleurs, l'administration et les tâches planifiées l'appellent.
 */
class PublicationLifecycle
{
    /**
     * Champs dont la modification, sur une publication publiée, peut la renvoyer
     * en modération (paramètre publication_edits_require_moderation).
     */
    public const SENSITIVE_FIELDS = [
        'title', 'description', 'publication_type_id', 'category_id', 'nature', 'origin', 'address',
    ];

    public function __construct(private readonly Settings $settings) {}

    /**
     * Soumission par l'auteur : publication immédiate pour un utilisateur de
     * confiance, sinon mise en attente de modération.
     */
    public function submit(Publication $publication, User $actor): void
    {
        if (! in_array($publication->status, [PublicationStatus::Draft, PublicationStatus::Rejected], true)) {
            throw InvalidPublicationTransition::from($publication->status, 'soumettre');
        }

        $publication->submitted_at = now();
        $publication->rejection_reason = null;

        if ($actor->canPublishWithoutModeration()) {
            $this->markPublished($publication);
        } else {
            $publication->status = PublicationStatus::Pending;
        }

        $publication->save();
    }

    /**
     * Mise en ligne (validation par un modérateur ou publication directe).
     */
    public function publish(Publication $publication): void
    {
        if ($publication->status !== PublicationStatus::Pending) {
            throw InvalidPublicationTransition::from($publication->status, 'publier');
        }

        $this->markPublished($publication);
        $publication->save();
    }

    public function archive(Publication $publication): void
    {
        if (in_array($publication->status, [PublicationStatus::Archived, PublicationStatus::Draft], true)) {
            throw InvalidPublicationTransition::from($publication->status, 'archiver');
        }

        $publication->status = PublicationStatus::Archived;
        $publication->archived_at = now();
        $publication->save();
    }

    /**
     * Renouvellement : nouvelle période complète à partir d'aujourd'hui.
     * Le contenu ayant déjà été validé, il n'y a pas de nouvelle modération.
     */
    public function renew(Publication $publication): void
    {
        if (! $this->canBeRenewed($publication)) {
            throw InvalidPublicationTransition::from($publication->status, 'renouveler');
        }

        $publication->status = PublicationStatus::Published;
        $publication->expires_at = $this->expirationFrom(now());
        $publication->expiration_notified_at = null;
        $publication->archived_at = null;
        $publication->save();
    }

    public function canBeRenewed(Publication $publication): bool
    {
        if ($publication->published_at === null) {
            return false;
        }

        if ($publication->isEvent() && $publication->event && $publication->event->endsAtOrEndOfStartDay()->isPast()) {
            return false;
        }

        return in_array($publication->status, [PublicationStatus::Published, PublicationStatus::Expired, PublicationStatus::Archived], true);
    }

    /**
     * À appeler après la modification du contenu par son auteur.
     *
     * @param  list<string>  $changedFields
     */
    public function afterEdit(Publication $publication, User $actor, array $changedFields): void
    {
        if ($publication->status !== PublicationStatus::Published
            || ! $this->settings->bool('publication_edits_require_moderation')
            || $actor->canPublishWithoutModeration()
            || array_intersect($changedFields, self::SENSITIVE_FIELDS) === []) {
            return;
        }

        $publication->status = PublicationStatus::Pending;
        $publication->submitted_at = now();
        $publication->save();
    }

    /**
     * Tâche planifiée : passage en « expirée » des publications échues.
     */
    public function expire(Publication $publication): void
    {
        $publication->status = PublicationStatus::Expired;
        $publication->save();
    }

    /**
     * Tâche planifiée : archivage d'un événement terminé.
     */
    public function archiveFinishedEvent(Publication $publication): void
    {
        $this->archive($publication);
    }

    private function markPublished(Publication $publication): void
    {
        $publication->status = PublicationStatus::Published;
        $publication->published_at ??= now();

        // Une publication revalidée après modification garde sa période en cours.
        if ($publication->expires_at === null || $publication->expires_at->isPast()) {
            $publication->expires_at = $this->expirationFrom(now());
            $publication->expiration_notified_at = null;
        }
    }

    private function expirationFrom(CarbonInterface $start): CarbonInterface
    {
        return $start->copy()->addDays($this->settings->int('publication_duration_days'));
    }
}
