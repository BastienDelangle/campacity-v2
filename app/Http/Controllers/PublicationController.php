<?php

namespace App\Http\Controllers;

use App\Models\Publication;
use App\Models\PublicationAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicationController extends Controller
{
    public function show(Request $request, string $slug): View|Response
    {
        $publication = Publication::withTrashed()->where('slug', $slug)->firstOrFail();

        // Contenu retiré (archivé, expiré, supprimé) : 410 pour que les moteurs le désindexent.
        if ($publication->trashed() || $publication->status->isGone()) {
            return response()->view('publications.gone', ['publication' => $publication], 410);
        }

        // Brouillon, en attente, refusé : inexistant pour le public.
        abort_unless(Gate::allows('view', $publication), 404);

        $publication->load(['type', 'category.parent', 'commune.territory', 'organization', 'author', 'event', 'images', 'attachments', 'activeVisibility']);

        return view('publications.show', [
            'publication' => $publication,
            'preview' => ! $publication->isPubliclyVisible(),
            'canRenew' => false,
        ]);
    }

    public function attachment(Publication $publication, PublicationAttachment $attachment): StreamedResponse
    {
        abort_unless($attachment->publication_id === $publication->id, 404);
        abort_unless(Gate::allows('view', $publication), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
