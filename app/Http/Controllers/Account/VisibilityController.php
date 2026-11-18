<?php

namespace App\Http\Controllers\Account;

use App\Actions\Visibility\VisibilityPurchases;
use App\Enums\VisibilityKind;
use App\Http\Controllers\Controller;
use App\Models\Publication;
use App\Models\VisibilityProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Commande d'une mise en avant ou d'un boost pour une publication.
 */
class VisibilityController extends Controller
{
    public function create(Publication $publication): View
    {
        Gate::authorize('manage', $publication);
        abort_unless($publication->isPubliclyVisible(), 404);

        return view('account.visibility.create', [
            'publication' => $publication->load('activeVisibility'),
            'products' => VisibilityProduct::active()->whereIn('kind', [VisibilityKind::Featured, VisibilityKind::Boost])->get(),
        ]);
    }

    public function store(Request $request, Publication $publication, VisibilityPurchases $purchases): RedirectResponse
    {
        Gate::authorize('manage', $publication);

        $data = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('visibility_products', 'id')->where('is_active', true)],
            'terms' => ['accepted'],
        ], attributes: ['product_id' => 'offre', 'terms' => 'conditions']);

        $purchase = $purchases->orderForPublication($request->user(), VisibilityProduct::findOrFail($data['product_id']), $publication);

        return redirect()->route('account.purchases.show', $purchase);
    }
}
