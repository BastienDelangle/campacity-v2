<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\PartnerProfile;
use App\Models\VisibilityProduct;
use Illuminate\View\View;

class PartnerController extends Controller
{
    /**
     * Annuaire public des partenaires premium.
     */
    public function index(): View
    {
        $partners = PartnerProfile::visible()
            ->with('organization.commune.territory')
            ->orderBy('sort_order')
            ->orderBy('visible_from')
            ->get();

        return view('partners.index', compact('partners'));
    }

    /**
     * Vitrine d'un partenaire (accessible via le slug de son organisation).
     */
    public function show(Organization $organization): View
    {
        $profile = $organization->partnerProfile;
        $isManager = auth()->user()?->can('update', $organization) ?? false;

        abort_unless($profile && ($profile->isVisible() || $isManager), 404);

        $organization->load('commune.territory');

        $publications = $organization->publications()
            ->publiclyVisible()
            ->with(['type', 'category', 'commune', 'images', 'activeVisibility'])
            ->latest('published_at')
            ->limit(9)
            ->get();

        return view('partners.show', [
            'organization' => $organization,
            'profile' => $profile,
            'publications' => $publications,
            'preview' => ! $profile->isVisible(),
        ]);
    }

    /**
     * Présentation des offres de visibilité (prix administrables).
     */
    public function offers(): View
    {
        return view('partners.offers', [
            'products' => VisibilityProduct::active()->get(),
        ]);
    }
}
