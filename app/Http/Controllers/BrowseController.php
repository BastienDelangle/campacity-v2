<?php

namespace App\Http\Controllers;

use App\Enums\PublicationOrigin;
use App\Models\Publication;
use App\Queries\PublicationSearch;
use Illuminate\View\View;

/**
 * Pages publiques de consultation : accueil « Découvrir » et rubriques.
 */
class BrowseController extends Controller
{
    /**
     * @var array<string, array{title: string, intro: string, kicker: string}>
     */
    private const SECTIONS = [
        'all' => [
            'kicker' => 'Toutes les annonces',
            'title' => 'Toutes les annonces',
            'intro' => 'Offres, demandes, événements et partenariats publiés par les acteurs des villes et des campagnes.',
        ],
        'urban' => [
            'kicker' => 'Côté urbain',
            'title' => 'Les besoins et offres des villes',
            'intro' => 'Centres de vacances, télétravail au vert, partenariats, formations… ce que les acteurs urbains proposent ou recherchent.',
        ],
        'rural' => [
            'kicker' => 'Côté rural',
            'title' => 'Les besoins et offres des campagnes',
            'intro' => 'Stages en ville, produits du terroir, espaces à proposer, débouchés… ce que les acteurs ruraux proposent ou recherchent.',
        ],
        'agenda' => [
            'kicker' => 'Formations et rendez-vous',
            'title' => 'Formations et rendez-vous',
            'intro' => 'Événements, rencontres et formations à venir sur les territoires.',
        ],
    ];

    public function home(): View
    {
        $latest = (new PublicationSearch)->query()
            ->with(['type', 'category', 'commune', 'images'])
            ->limit(6)
            ->get();

        $upcoming = (new PublicationSearch(agenda: true, sort: 'upcoming'))->query()
            ->with(['type', 'category', 'commune', 'images', 'event'])
            ->limit(3)
            ->get();

        $counts = Publication::publiclyVisible()
            ->selectRaw('origin, count(*) as total')
            ->groupBy('origin')
            ->pluck('total', 'origin');

        return view('home', [
            'latest' => $latest,
            'upcoming' => $upcoming,
            'urbanCount' => (int) ($counts[PublicationOrigin::Urban->value] ?? 0) + (int) ($counts[PublicationOrigin::Both->value] ?? 0),
            'ruralCount' => (int) ($counts[PublicationOrigin::Rural->value] ?? 0) + (int) ($counts[PublicationOrigin::Both->value] ?? 0),
        ]);
    }

    public function section(string $section): View
    {
        return view('browse', [
            'section' => self::SECTIONS[$section],
            'preset' => $section === 'all' ? null : $section,
        ]);
    }
}
