<?php

namespace App\Console\Commands;

use App\Models\Commune;
use App\Models\Territory;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Importe (ou met à jour) des communes depuis l'API officielle du découpage
 * administratif (geo.api.gouv.fr) et les rattache à un territoire.
 *
 * Exemples :
 *   php artisan campacity:import-communes "Pays de Coutances" --epci=200067064
 *   php artisan campacity:import-communes "Manche" --departement=50
 *   php artisan campacity:import-communes "Mon territoire" --insee=50502 --insee=50129
 */
#[Signature('campacity:import-communes {territory : Nom du territoire (créé s\'il n\'existe pas)}
    {--epci=* : Code SIREN d\'intercommunalité}
    {--departement=* : Code de département}
    {--insee=* : Code INSEE de commune}')]
#[Description('Importe des communes depuis geo.api.gouv.fr dans un territoire')]
class ImportCommunes extends Command
{
    private const API = 'https://geo.api.gouv.fr';

    private const FIELDS = 'nom,code,codesPostaux,centre';

    public function handle(): int
    {
        $epcis = (array) $this->option('epci');
        $departements = (array) $this->option('departement');
        $insees = (array) $this->option('insee');

        if ($epcis === [] && $departements === [] && $insees === []) {
            $this->error('Indiquez au moins une option : --epci, --departement ou --insee.');

            return self::INVALID;
        }

        try {
            $rows = $this->fetch($epcis, $departements, $insees);
        } catch (RequestException|ConnectionException $exception) {
            $this->error("L'API geo.api.gouv.fr a répondu une erreur : {$exception->getMessage()}");

            return self::FAILURE;
        }

        if ($rows->isEmpty()) {
            $this->warn('Aucune commune trouvée pour ces critères.');

            return self::FAILURE;
        }

        $name = (string) $this->argument('territory');
        $territory = Territory::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);

        $created = 0;
        foreach ($rows as $row) {
            [$lng, $lat] = $row['centre']['coordinates'] ?? [null, null];

            $commune = Commune::updateOrCreate(['insee_code' => $row['code']], [
                'name' => $row['nom'],
                'postal_code' => $row['codesPostaux'][0] ?? null,
                'latitude' => $lat,
                'longitude' => $lng,
                'territory_id' => $territory->id,
            ]);

            $created += (int) $commune->wasRecentlyCreated;
        }

        if ($territory->center_latitude === null) {
            $territory->update([
                'center_latitude' => round((float) $territory->communes()->avg('latitude'), 7),
                'center_longitude' => round((float) $territory->communes()->avg('longitude'), 7),
                'default_zoom' => $rows->count() > 150 ? 9 : 10,
            ]);
        }

        $this->info("{$rows->count()} communes traitées pour « {$territory->name} » ({$created} créées, ".($rows->count() - $created).' mises à jour).');

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $epcis
     * @param  list<string>  $departements
     * @param  list<string>  $insees
     * @return Collection<int, array<string, mixed>>
     */
    private function fetch(array $epcis, array $departements, array $insees): Collection
    {
        // Nouvel essai uniquement sur erreur réseau ou serveur, pas sur un code introuvable.
        $client = Http::baseUrl(self::API)->acceptJson()->timeout(20)->retry(
            2,
            500,
            fn ($exception) => $exception instanceof ConnectionException
                || ($exception instanceof RequestException && $exception->response->serverError()),
            throw: false,
        );
        $rows = collect();

        foreach ($epcis as $code) {
            $rows = $rows->merge($client->get('/communes', ['codeEpci' => $code, 'fields' => self::FIELDS])->throw()->json());
        }

        foreach ($departements as $code) {
            $rows = $rows->merge($client->get('/communes', ['codeDepartement' => $code, 'fields' => self::FIELDS])->throw()->json());
        }

        foreach ($insees as $code) {
            $response = $client->get("/communes/{$code}", ['fields' => self::FIELDS]);
            if ($response->successful()) {
                $rows->push($response->json());
            } else {
                $this->warn("Commune {$code} introuvable.");
            }
        }

        return $rows->filter(fn ($row) => is_array($row) && isset($row['code'], $row['nom']))->unique('code')->values();
    }
}
