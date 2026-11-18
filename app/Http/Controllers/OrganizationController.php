<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function show(Organization $organization): View
    {
        Gate::authorize('view', $organization);

        $organization->load('commune.territory');

        return view('organizations.show', compact('organization'));
    }
}
