<?php

namespace App\Http\Controllers\Account;

use App\Actions\Organizations\ManageOrganizationMembers;
use App\Enums\OrganizationRole;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrganizationMemberController extends Controller
{
    public function store(Request $request, Organization $organization, ManageOrganizationMembers $members): RedirectResponse
    {
        Gate::authorize('manageMembers', $organization);

        $data = $request->validateWithBag('members', [
            'member_email' => ['required', 'email', 'max:255'],
            'member_role' => ['required', Rule::enum(OrganizationRole::class)],
        ], attributes: ['member_email' => 'adresse e-mail', 'member_role' => 'rôle']);

        try {
            $user = $members->add($organization, $data['member_email'], OrganizationRole::from($data['member_role']));
        } catch (ValidationException $exception) {
            throw $exception->errorBag('members');
        }

        return back()->with('status', "{$user->name} a été ajouté(e) aux responsables.");
    }

    public function update(Request $request, Organization $organization, User $member, ManageOrganizationMembers $members): RedirectResponse
    {
        Gate::authorize('manageMembers', $organization);
        $this->ensureIsMember($organization, $member);

        $data = $request->validate(['role' => ['required', Rule::enum(OrganizationRole::class)]]);

        $members->changeRole($organization, $member, OrganizationRole::from($data['role']));

        return back()->with('status', 'Rôle mis à jour.');
    }

    public function destroy(Request $request, Organization $organization, User $member, ManageOrganizationMembers $members): RedirectResponse
    {
        $this->ensureIsMember($organization, $member);

        // Chacun peut se retirer lui-même ; retirer quelqu'un d'autre est réservé aux propriétaires.
        if (! $request->user()->is($member)) {
            Gate::authorize('manageMembers', $organization);
        }

        $members->remove($organization, $member);

        return $request->user()->is($member)
            ? redirect()->route('account.organizations.index')->with('status', "Vous ne gérez plus {$organization->name}.")
            : back()->with('status', 'Responsable retiré(e).');
    }

    private function ensureIsMember(Organization $organization, User $member): void
    {
        abort_unless($organization->members()->whereKey($member->id)->exists(), 404);
    }
}
