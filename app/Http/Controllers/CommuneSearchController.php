<?php

namespace App\Http\Controllers;

use App\Models\Commune;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Suggestions de communes pour les champs de saisie (données publiques uniquement).
 */
class CommuneSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q'));

        if (mb_strlen($term) < 2 || mb_strlen($term) > 60) {
            return response()->json([]);
        }

        $like = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);

        $communes = Commune::query()
            ->with('territory:id,name')
            ->where(fn ($q) => $q->where('name', 'like', $like.'%')->orWhere('postal_code', 'like', $like.'%'))
            ->when($request->filled('territoire'), fn ($q) => $q->whereHas('territory', fn ($t) => $t->where('slug', $request->query('territoire'))))
            ->orderByRaw('name LIKE ? DESC', [$like.'%'])
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'postal_code', 'territory_id', 'latitude', 'longitude']);

        return response()->json($communes->map(fn (Commune $commune) => [
            'id' => $commune->id,
            'label' => $commune->displayName(),
            'territory' => $commune->territory?->name,
            'lat' => $commune->latitude,
            'lng' => $commune->longitude,
        ]));
    }
}
