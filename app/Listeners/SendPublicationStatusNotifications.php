<?php

namespace App\Listeners;

use App\Enums\ModerationAction;
use App\Enums\Permission;
use App\Events\PublicationStatusChanged;
use App\Models\User;
use App\Notifications\NewPublicationToModerate;
use App\Notifications\PublicationApproved;
use App\Notifications\PublicationRejected;
use App\Notifications\PublicationSubmittedForReview;
use App\Notifications\PublicationSuspended;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Notification;

/**
 * E-mails à l'auteur et alertes aux modérateurs, après validation de la transaction.
 */
class SendPublicationStatusNotifications implements ShouldHandleEventsAfterCommit
{
    public function handle(PublicationStatusChanged $event): void
    {
        $publication = $event->publication;
        $author = $publication->author;

        $authorNotification = match ($event->action) {
            ModerationAction::Submitted, ModerationAction::EditResubmitted => new PublicationSubmittedForReview($publication),
            ModerationAction::Approved, ModerationAction::Reinstated => new PublicationApproved($publication),
            ModerationAction::Rejected => new PublicationRejected($publication, (string) $event->reason),
            ModerationAction::Suspended => new PublicationSuspended($publication, (string) $event->reason),
            default => null,
        };

        if ($authorNotification && $author && ! $author->isAnonymized()) {
            $author->notify($authorNotification);
        }

        if (in_array($event->action, [ModerationAction::Submitted, ModerationAction::EditResubmitted], true)) {
            Notification::send(
                User::activeWithPermission(Permission::ModeratePublications)->get(),
                new NewPublicationToModerate($publication),
            );
        }
    }
}
