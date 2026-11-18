<?php

namespace App\Actions\Publications;

use App\Enums\ModerationAction;
use App\Enums\PublicationStatus;
use App\Events\PublicationStatusChanged;
use App\Exceptions\InvalidPublicationTransition;
use App\Models\Publication;
use App\Models\User;
use App\Support\Settings;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Transitions de statut d'une publication. Toute la logique du cycle de vie
 * est ici : espace utilisateur, administration et tâches planifiées l'appellent.
 * Chaque transition émet PublicationStatusChanged (historique, notifications).
 */
class PublicationLifecycle
{
    /**
     * Champs dont la modification, sur une publication publiée, peut la renvoyer
     * en modération (paramètre publication_edits_require_moderation).
     */
    public const SENSITIVE_FIELDS = [
        'title', 'description', 'publication_type_id', 'category_id', 'nature', 'origin', 'address', 'images',
    ];

    public function __construct(private readonly Settings $settings) {}

    /**
     * Soumission par l'auteur : publication immédiate pour un utilisateur de
     * confiance, sinon mise en attente de modération.
     */
    public function submit(Publication $publication, User $actor): void
    {
        $this->guard($publication, [PublicationStatus::Draft, PublicationStatus::Rejected], 'soumettre');

        $publication->submitted_at = now();
        $publication->rejection_reason = null;

        if ($actor->canPublishWithoutModeration()) {
            $this->markPublished($publication);
            $this->transition($publication, PublicationStatus::Published, ModerationAction::AutoPublished, $actor);
        } else {
            $this->transition($publication, PublicationStatus::Pending, ModerationAction::Submitted, $actor);
        }
    }

    /**
     * Validation par un modérateur.
     */
    public function publish(Publication $publication, User $moderator): void
    {
        $this->guard($publication, [PublicationStatus::Pending], 'valider');

        $this->markPublished($publication);
        $this->transition($publication, PublicationStatus::Published, ModerationAction::Approved, $moderator);
    }

    public function reject(Publication $publication, User $moderator, string $reason): void
    {
        $this->guard($publication, [PublicationStatus::Pending], 'refuser');

        $publication->rejection_reason = $reason;
        $this->transition($publication, PublicationStatus::Rejected, ModerationAction::Rejected, $moderator, $reason);
    }

    /**
     * Retrait par la modération, à tout moment. L'auteur ne peut ni modifier,
     * ni renouveler, ni archiver une publication suspendue.
     */
    public function suspend(Publication $publication, User $moderator, string $reason): void
    {
        $this->guard($publication, [PublicationStatus::Published, PublicationStatus::Pending, PublicationStatus::Expired, PublicationStatus::Archived], 'suspendre');

        $publication->rejection_reason = $reason;
        $this->transition($publication, PublicationStatus::Suspended, ModerationAction::Suspended, $moderator, $reason);
    }

    public function reinstate(Publication $publication, User $moderator): void
    {
        $this->guard($publication, [PublicationStatus::Suspended], 'rétablir');

        $publication->rejection_reason = null;
        $this->markPublished($publication);
        $this->transition($publication, PublicationStatus::Published, ModerationAction::Reinstated, $moderator);
    }

    public function archive(Publication $publication, ?User $actor = null): void
    {
        $this->guard($publication, [PublicationStatus::Published, PublicationStatus::Pending, PublicationStatus::Rejected, PublicationStatus::Expired], 'archiver');

        $publication->archived_at = now();
        $this->transition($publication, PublicationStatus::Archived, ModerationAction::Archived, $actor);
    }

    /**
     * Renouvellement : nouvelle période complète à partir d'aujourd'hui.
     * Le contenu ayant déjà été validé, il n'y a pas de nouvelle modération.
     */
    public function renew(Publication $publication, User $actor): void
    {
        if (! $this->canBeRenewed($publication)) {
            throw InvalidPublicationTransition::from($publication->status, 'renouveler');
        }

        $publication->expires_at = $this->expirationFrom(now());
        $publication->expiration_notified_at = null;
        $publication->archived_at = null;
        $this->transition($publication, PublicationStatus::Published, ModerationAction::Renewed, $actor);
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

        $publication->submitted_at = now();
        $this->transition($publication, PublicationStatus::Pending, ModerationAction::EditResubmitted, $actor);
    }

    /**
     * Tâche planifiée : passage en « expirée » des publications échues.
     */
    public function expire(Publication $publication): void
    {
        $this->transition($publication, PublicationStatus::Expired, ModerationAction::Expired);
    }

    /**
     * Tâche planifiée : archivage d'un événement terminé.
     */
    public function archiveFinishedEvent(Publication $publication): void
    {
        $this->archive($publication);
    }

    /**
     * @param  list<PublicationStatus>  $allowed
     */
    private function guard(Publication $publication, array $allowed, string $action): void
    {
        if (! in_array($publication->status, $allowed, true)) {
            throw InvalidPublicationTransition::from($publication->status, $action);
        }
    }

    private function transition(Publication $publication, PublicationStatus $to, ModerationAction $action, ?User $actor = null, ?string $reason = null): void
    {
        $from = $publication->getOriginal('status');
        $from = $from instanceof PublicationStatus ? $from : PublicationStatus::tryFrom((string) $from);

        DB::transaction(function () use ($publication, $to, $action, $actor, $reason, $from) {
            $publication->status = $to;
            $publication->save();

            PublicationStatusChanged::dispatch($publication, $action, $from, $to, $actor, $reason);
        });
    }

    private function markPublished(Publication $publication): void
    {
        $publication->published_at ??= now();

        // Une publication revalidée garde sa période en cours.
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
