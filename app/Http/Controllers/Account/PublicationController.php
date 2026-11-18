<?php

namespace App\Http\Controllers\Account;

use App\Actions\Publications\PublicationLifecycle;
use App\Actions\Publications\SavePublication;
use App\Enums\LocationPrecision;
use App\Enums\PublicationNature;
use App\Enums\PublicationOrigin;
use App\Enums\PublicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicationRequest;
use App\Models\Category;
use App\Models\Commune;
use App\Models\Publication;
use App\Models\PublicationType;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PublicationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $status = PublicationStatus::tryFrom((string) $request->query('statut'));

        $publications = Publication::query()
            ->where(fn (Builder $query) => $query
                ->where('user_id', $user->id)
                ->orWhereIn('organization_id', $user->organizations()->select('organizations.id')))
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->with(['type', 'category', 'organization', 'images'])
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('account.publications.index', [
            'publications' => $publications,
            'statuses' => PublicationStatus::cases(),
            'currentStatus' => $status,
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Publication::class);

        return view('account.publications.form', [
            'publication' => new Publication([
                'nature' => PublicationNature::Offer,
                'location_precision' => LocationPrecision::Commune,
                'organization_id' => $request->integer('organisation') ?: null,
            ]),
            ...$this->formOptions($request),
        ]);
    }

    public function store(PublicationRequest $request, SavePublication $save): RedirectResponse
    {
        $publication = $save->create($request->user(), $request->validated());

        return redirect()->route('account.publications.preview', $publication)
            ->with('status', 'Brouillon enregistré. Vérifiez l\'aperçu avant de soumettre votre publication.');
    }

    public function preview(Publication $publication): View
    {
        Gate::authorize('manage', $publication);

        $publication->load(['type', 'category.parent', 'commune.territory', 'organization', 'author', 'event', 'images', 'attachments']);

        return view('publications.show', [
            'publication' => $publication,
            'preview' => true,
            'canRenew' => app(PublicationLifecycle::class)->canBeRenewed($publication),
        ]);
    }

    public function edit(Request $request, Publication $publication): View
    {
        Gate::authorize('update', $publication);

        $publication->load(['event', 'images', 'attachments']);

        return view('account.publications.form', [
            'publication' => $publication,
            ...$this->formOptions($request),
        ]);
    }

    public function update(PublicationRequest $request, Publication $publication, SavePublication $save): RedirectResponse
    {
        $wasPublished = $publication->status === PublicationStatus::Published;

        $save->update($publication, $request->user(), $request->validated());

        $message = $wasPublished && $publication->status === PublicationStatus::Pending
            ? 'Modifications enregistrées. Votre publication repasse en modération avant d\'être de nouveau visible.'
            : 'Modifications enregistrées.';

        return redirect()->route('account.publications.preview', $publication)->with('status', $message);
    }

    public function destroy(Publication $publication): RedirectResponse
    {
        Gate::authorize('delete', $publication);

        $publication->delete();

        return redirect()->route('account.publications.index')->with('status', 'Publication supprimée.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(Request $request): array
    {
        $settings = app(Settings::class);

        return [
            'types' => PublicationType::active()->ordered()->get(),
            'categories' => Category::active()->roots()->ordered()
                ->with(['children' => fn ($query) => $query->active()->ordered()])
                ->get(),
            'communes' => Commune::query()->with('territory:id,name')->orderBy('name')->get(['id', 'name', 'postal_code', 'territory_id']),
            'organizations' => $request->user()->organizations()->orderBy('name')->get(['organizations.id', 'organizations.name']),
            'natures' => PublicationNature::cases(),
            'origins' => PublicationOrigin::cases(),
            'precisions' => LocationPrecision::cases(),
            'maxImages' => $settings->int('publication_max_images'),
            'maxAttachments' => $settings->int('publication_max_attachments'),
            'attachmentTypes' => PublicationRequest::ATTACHMENT_TYPES,
        ];
    }
}
