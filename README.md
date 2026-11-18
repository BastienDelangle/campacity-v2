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
php artisan db:seed       # rôles et permissions (+ comptes de démo en local)
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
php artisan test                # suite de tests (base campacity_testing)
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

### Rôles et permissions

- Les rôles (`admin`, `moderator`, `user`) et les permissions (`App\Enums\Permission`) sont stockés en base.
- `RolesAndPermissionsSeeder` est idempotent : il crée ce qui manque sans écraser les ajustements faits ensuite.
- L'administrateur passe tous les contrôles (`Gate::before`).
- L'accès à `/admin` exige la permission `admin.access` et une adresse e-mail vérifiée.

## Avancement

- [x] Phase 1 : socle technique (Laravel, MySQL, Herd, Filament, authentification, rôles et permissions)
- [ ] Phase 2 : comptes et organisations
- [ ] Phase 3 : publications
- [ ] Phase 4 : recherche et territoires
- [ ] Phase 5 : modération et contact
- [ ] Phase 6 : partenaires et visibilité
- [ ] Phase 7 : adhésions et paiements (HelloAsso)
- [ ] Phase 8 : finalisation
