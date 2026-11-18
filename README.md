# Campacity

Plateforme de coopérations entre territoires urbains et ruraux : publications (offres et demandes), organisations, carte, modération, partenaires premium et adhésions associatives.

> MVP en cours de développement, phase par phase (voir « Avancement »).

## Stack

| Élément | Version |
|---|---|
| PHP | 8.4 (minimum 8.3) |
| Laravel | 13.x |
| Filament (administration) | 5.x |
| Livewire | 4.x (fourni avec Filament) |
| Authentification | Laravel Fortify 1.x (backend), vues Blade propres au projet |
| Rôles et permissions | spatie/laravel-permission 8.x |
| Base de données | MySQL 8+ (développé sur MySQL 9.7) |
| Frontend | Blade, Tailwind CSS 4, Vite |
| Tests | PHPUnit 12 |

## Installation locale (Laravel Herd)

Prérequis : Herd (PHP 8.4, Composer, Node 20+), MySQL accessible (`127.0.0.1:3306`, utilisateur `root`).

```bash
cd ~/Herd
git clone <dépôt> campacity && cd campacity

# Bases de données (application et tests)
mysql -uroot -h127.0.0.1 -e "CREATE DATABASE campacity CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
                             CREATE DATABASE campacity_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

composer setup            # install, .env, clé, migrations, build front
php artisan db:seed       # rôles, permissions, types et catégories initiaux (+ démo en local)
php artisan storage:link  # accès public aux fichiers envoyés (logos, images)
herd secure campacity     # HTTPS local
```

Le site est disponible sur https://campacity.test et l'administration sur https://campacity.test/admin.

### Comptes de démonstration (environnement `local` uniquement)

| Rôle | E-mail | Mot de passe |
|---|---|---|
| Administrateur | admin@campacity.test | password |
| Modérateur | moderateur@campacity.test | password |
| Utilisateur | utilisateur@campacity.test | password |

En dehors du local, créer un administrateur avec :

```bash
php artisan campacity:create-admin prenom.nom@domaine.fr --name="Prénom Nom"
```

## Configuration

Toute la configuration passe par `.env` (voir `.env.example`) :

- `APP_URL` : `https://campacity.test` en local.
- `DB_*` : connexion MySQL.
- `MAIL_*` : en local, `MAIL_MAILER=log` écrit les e-mails dans `storage/logs/laravel.log`. En recette et en production, renseigner les accès SMTP.
- `QUEUE_CONNECTION=database` : les e-mails et traitements asynchrones passent par la file d'attente.

## Commandes utiles

```bash
composer dev                    # serveur Vite + file d'attente + logs
npm run build                   # build des assets front
php artisan test                # suite de tests (base campacity_testing, données de référence semées)
vendor/bin/pint                 # formatage du code
php artisan migrate:fresh --seed  # réinitialise la base locale (destructif)
php artisan queue:work          # traite la file d'attente
php artisan schedule:work       # exécute le planificateur en local
```

En production, une seule entrée cron est nécessaire : `* * * * * php /chemin/artisan schedule:run`.

## Architecture

Une application Laravel monolithique, organisée par domaine fonctionnel :

- `app/Enums` : valeurs métier fermées (rôles, permissions, puis statuts, natures, origines…).
- `app/Actions` : logique métier (actions Fortify, puis actions métier par domaine).
- `app/Policies` : autorisations, vérifiées côté serveur.
- `app/Filament` : back-office (ressources, pages, widgets).
- `resources/views/components` : composants Blade réutilisables. Les jetons de design de la maquette V1 sont dans `resources/css/app.css` (`@theme`), pour faciliter l'intégration de la future maquette.

### Comptes et organisations

- **Profil :** coordonnées (téléphone, site) et choix de ce qui est visible publiquement (`show_email`, `show_phone`, `show_website`). Par défaut, l'e-mail et le téléphone restent privés.
- **Suppression de compte :** elle se fait par anonymisation (`App\Actions\Users\AnonymizeUser`). Les données personnelles sont effacées, mais la ligne est conservée pour les historiques soumis à obligation de conservation. Un propriétaire unique d'une organisation partagée doit d'abord désigner un autre propriétaire.
- **Organisations :**
  - fiche publique sur `/organisations/{slug}` ; le slug ne change pas en cas de renommage, pour préserver les URLs ;
  - responsables : `owner` (gère la fiche et les responsables) ou `editor` (gère la fiche). Il reste toujours au moins un propriétaire (`ManageOrganizationMembers`) ;
  - le logo est stocké sur le disque `public` (JPG, PNG ou WebP, 2 Mo maximum, SVG refusé). Pensez à lancer `php artisan storage:link`.
- **Territoires et communes :** gérés dans l'administration (rubrique « Référentiels »). Le territoire d'une organisation est déduit de sa commune.

### Publications

- **Trois axes indépendants :** le type (table `publication_types`, administrable ; un type « événement » active les champs date, lieu, programme et inscription), la nature (offre ou demande) et l'origine (urbain, rural, entre les deux). La catégorie est une table `categories` à un niveau de sous-catégories.
- **Cycle de vie (`App\Actions\Publications\PublicationLifecycle`) :**
  - brouillon → aperçu → soumission ;
  - à la soumission, la publication part en attente de modération, ou paraît directement si l'auteur est « de confiance » ou modérateur ;
  - une modification sensible d'une publication en ligne la renvoie en modération, si le paramètre est actif ;
  - l'auteur peut archiver ou renouveler sa publication. Une page archivée, expirée ou supprimée répond 410.
- **Tâche planifiée `publications:process-lifecycle` (toutes les heures) :** archivage des événements terminés, passage en « expirée » des publications échues et rappel par e-mail avant expiration (envoyé une seule fois).
- **Localisation :** la précision affichée publiquement est choisie par l'auteur (commune uniquement, position approximative ≈ 1 km, adresse exacte). `Publication::publicCoordinates()` ne renvoie jamais une position plus précise que ce choix.
- **Fichiers :**
  - les images sont redimensionnées (1 600 px de large maximum), ré-encodées en WebP et débarrassées de leurs métadonnées EXIF (GD natif, sans dépendance) ;
  - les documents sont stockés sur le disque privé et servis seulement si la publication est consultable.

### Recherche, rubriques et carte

- **Pages :**
  - `/` (Découvrir), `/annonces` (toutes), `/cote-urbain`, `/cote-rural`, `/formations-et-rendez-vous` ;
  - « Entre les deux » apparaît dans les deux rubriques, urbaine et rurale ;
  - l'agenda regroupe les types « événement » et les catégories listées dans `campacity.agenda_category_slugs`.
- **Moteur :** `App\Queries\PublicationSearch` est la requête unique utilisée par la liste **et** la carte, qui affichent donc toujours les mêmes résultats.
  - Recherche plein texte (index FULLTEXT MySQL sur le titre et la description), plus la commune, le code postal, le territoire et la catégorie.
  - Filtres combinables : catégorie (sous-catégories incluses), origine, nature, type, territoire, commune.
  - Seules les publications publiées et non échues sont renvoyées.
- **Composant `App\Livewire\PublicationBrowser` :** les filtres sont reflétés dans l'URL, la liste est paginée, la carte se met à jour à chaque filtre, avec un message quand rien ne correspond. Une page filtrée est en `noindex, follow` et pointe vers sa page canonique.
- **Carte :**
  - Leaflet, chargé uniquement sur les pages qui affichent une carte ;
  - le fournisseur de tuiles se règle avec `MAP_TILE_URL` et `MAP_ATTRIBUTION`. OpenStreetMap par défaut : pour un fort trafic en production, prévoir un fournisseur dédié, conformément à la politique d'usage d'OSM ;
  - les repères respectent la précision choisie par l'auteur.
- **Saisie de commune :** autocomplétion par nom ou code postal (`/communes/recherche`, limité à 60 requêtes par minute). Dans le formulaire de publication, on peut placer un repère sur la carte.

### Import des communes

Territoire de lancement : **Normandie** (5 départements, 2 644 communes). Import sur un nouvel environnement :

```bash
php artisan campacity:import-communes "Normandie" --departement=14 --departement=27 --departement=50 --departement=61 --departement=76
```

Autres usages :

Depuis l'API officielle du découpage administratif (geo.api.gouv.fr). Le territoire est créé s'il n'existe pas, et l'import peut être relancé sans créer de doublons :

```bash
php artisan campacity:import-communes "Nom du territoire" --epci=<SIREN EPCI>
php artisan campacity:import-communes "Nom du territoire" --departement=50
php artisan campacity:import-communes "Nom du territoire" --insee=50147 --insee=50003
```

### Paramètres administrables

Ils sont réglables dans Administration › Configuration › Paramètres. Les valeurs par défaut sont dans `config/campacity.php`, et la durée de mise en ligne reste à valider :
- durée de mise en ligne ;
- délai du rappel avant expiration ;
- nombre maximal d'images et de documents ;
- re-modération après une modification sensible.

### Rôles et permissions

- Les rôles (`admin`, `moderator`, `user`) et les permissions (`App\Enums\Permission`) sont stockés en base.
- `RolesAndPermissionsSeeder` est idempotent : il crée ce qui manque sans écraser les ajustements faits ensuite.
- Le rôle administrateur reçoit toutes les permissions à chaque exécution du seeder, y compris les nouvelles. Les garde-fous métier, comme l'interdiction de modifier ses propres rôles ou de supprimer une catégorie utilisée, s'appliquent aussi à lui.
- L'accès à `/admin` exige la permission `admin.access` et une adresse e-mail vérifiée.

## Avancement

- [x] Phase 1 : socle technique (Laravel, MySQL, Herd, Filament, authentification, rôles et permissions)
- [x] Phase 2 : comptes et organisations
- [x] Phase 3 : publications
- [x] Phase 4 : recherche et territoires
- [ ] Phase 5 : modération et contact
- [ ] Phase 6 : partenaires et visibilité
- [ ] Phase 7 : adhésions et paiements (HelloAsso)
- [ ] Phase 8 : finalisation
