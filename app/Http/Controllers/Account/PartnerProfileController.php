<?php

namespace App\Http\Controllers\Account;

use App\Actions\Visibility\VisibilityPurchases;
use App\Enums\VisibilityKind;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\PartnerProfile;
use App\Models\VisibilityProduct;
use App\Services\ImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

/**
 * Offre partenaire premium d'une organisation : souscription et contenu de la vitrine.
 */
class PartnerProfileController extends Controller
{
    public function edit(Request $request, Organization $organization): View
    {
        Gate::authorize('update', $organization);

        $profile = $organization->partnerProfile;

        return view('account.partner.edit', [
            'organization' => $organization,
            'profile' => $profile,
            'products' => VisibilityProduct::active()->where('kind', VisibilityKind::PremiumPartner)->get(),
            'canSubscribe' => $request->user()->can('manageMembers', $organization),
            'purchases' => $profile?->purchases()->latest()->limit(10)->get() ?? collect(),
        ]);
    }

    public function subscribe(Request $request, Organization $organization, VisibilityPurchases $purchases): RedirectResponse
    {
        Gate::authorize('manageMembers', $organization);

        $data = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('visibility_products', 'id')->where('is_active', true)],
            'terms' => ['accepted'],
        ], attributes: ['product_id' => 'offre', 'terms' => 'conditions']);

        $purchase = $purchases->orderPremium($request->user(), VisibilityProduct::findOrFail($data['product_id']), $organization);

        return redirect()->route('account.purchases.show', $purchase);
    }

    public function update(Request $request, Organization $organization, ImageStorage $images): RedirectResponse
    {
        Gate::authorize('update', $organization);

        $profile = $organization->partnerProfile;
        abort_unless($profile instanceof PartnerProfile, 404);

        $data = $request->validate([
            'headline' => ['nullable', 'string', 'max:160'],
            'activity' => ['nullable', 'string', 'max:255'],
            'presentation' => ['nullable', 'string', 'max:8000'],
            'cover' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(5 * 1024)],
            'remove_cover' => ['boolean'],
        ], attributes: ['headline' => 'accroche', 'activity' => 'activité', 'presentation' => 'présentation', 'cover' => 'image de couverture']);

        $profile->fill(collect($data)->only(['headline', 'activity', 'presentation'])->all());

        if ($request->file('cover') || $request->boolean('remove_cover')) {
            if ($profile->cover_path) {
                Storage::disk('public')->delete($profile->cover_path);
            }
            $profile->cover_path = $request->file('cover')
                ? $images->store($request->file('cover'), "partners/{$profile->id}")['path']
                : null;
        }

        $profile->save();

        return back()->with('status', 'Vitrine mise à jour.');
    }
}
