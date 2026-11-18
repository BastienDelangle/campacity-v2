<?php

namespace App\Http\Controllers\Account;

use App\Actions\Publications\PublicationLifecycle;
use App\Enums\PublicationStatus;
use App\Exceptions\InvalidPublicationTransition;
use App\Http\Controllers\Controller;
use App\Models\Publication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Actions de l'auteur sur le cycle de vie : soumettre, archiver, renouveler.
 */
class PublicationStatusController extends Controller
{
    public function __construct(private readonly PublicationLifecycle $lifecycle) {}

    public function submit(Request $request, Publication $publication): RedirectResponse
    {
        Gate::authorize('manage', $publication);

        return $this->attempt(function () use ($request, $publication) {
            $this->lifecycle->submit($publication, $request->user());

            return $publication->status === PublicationStatus::Published
                ? 'Votre publication est en ligne.'
                : 'Votre publication a été transmise à l\'équipe de modération. Vous serez prévenu(e) par e-mail.';
        }, $publication);
    }

    public function archive(Request $request, Publication $publication): RedirectResponse
    {
        Gate::authorize('manage', $publication);

        return $this->attempt(function () use ($request, $publication) {
            $this->lifecycle->archive($publication, $request->user());

            return 'Publication archivée : elle n\'est plus visible publiquement.';
        }, $publication);
    }

    public function renew(Request $request, Publication $publication): RedirectResponse
    {
        Gate::authorize('manage', $publication);

        return $this->attempt(function () use ($request, $publication) {
            $this->lifecycle->renew($publication, $request->user());

            return 'Publication renouvelée jusqu\'au '.$publication->expires_at->translatedFormat('j F Y').'.';
        }, $publication);
    }

    /**
     * @param  callable(): string  $action
     */
    private function attempt(callable $action, Publication $publication): RedirectResponse
    {
        try {
            $message = $action();
        } catch (InvalidPublicationTransition $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        }

        return redirect()->route('account.publications.preview', $publication)->with('status', $message);
    }
}
