<?php

namespace App\Http\Controllers\Account;

use App\Actions\Organizations\SaveOrganization;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrganizationRequest;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(Request $request): View
    {
        $organizations = $request->user()->organizations()
            ->with('commune')
            ->orderBy('name')
            ->get();

        return view('account.organizations.index', compact('organizations'));
    }

    public function create(): View
    {
        Gate::authorize('create', Organization::class);

        return view('account.organizations.form', [
            'organization' => new Organization(['type' => OrganizationType::Association]),
            ...$this->formOptions(),
        ]);
    }

    public function store(OrganizationRequest $request, SaveOrganization $save): RedirectResponse
    {
        $organization = $save->create($request->user(), $request->safe()->except(['logo', 'remove_logo']), $request->file('logo'));

        return redirect()->route('account.organizations.edit', $organization)
            ->with('status', 'Organisation créée.');
    }

    public function edit(Request $request, Organization $organization): View
    {
        Gate::authorize('update', $organization);

        $organization->load(['commune', 'partnerProfile', 'members' => fn ($query) => $query->orderBy('name')]);

        return view('account.organizations.form', [
            'organization' => $organization,
            'canManageMembers' => $request->user()->can('manageMembers', $organization),
            'roles' => OrganizationRole::cases(),
            ...$this->formOptions(),
        ]);
    }

    public function update(OrganizationRequest $request, Organization $organization, SaveOrganization $save): RedirectResponse
    {
        $save->update(
            $organization,
            $request->safe()->except(['logo', 'remove_logo']),
            $request->file('logo'),
            $request->boolean('remove_logo'),
        );

        return redirect()->route('account.organizations.edit', $organization)
            ->with('status', 'Organisation mise à jour.');
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        Gate::authorize('delete', $organization);

        $organization->delete();

        return redirect()->route('account.organizations.index')
            ->with('status', 'Organisation supprimée.');
    }

    /**
     * @return array{types: list<OrganizationType>}
     */
    private function formOptions(): array
    {
        return [
            'types' => OrganizationType::cases(),
        ];
    }
}
