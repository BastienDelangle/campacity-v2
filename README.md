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
php artisan schedule:work       # exécute le planificateur en local (rappels, expirations, rapprochement des paiements)
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

### Modération

- **Modération mixte :**
  - les publications des nouveaux utilisateurs passent en attente ;
  - celles des utilisateurs « de confiance » (case à cocher dans Administration › Utilisateurs) et des modérateurs paraissent immédiatement ;
  - le niveau de confiance ne s'acquiert jamais automatiquement.
- **Actions des modérateurs** (liste ou fiche détaillée `/admin/publications/{slug}`) : valider, refuser avec motif, suspendre avec motif, rétablir.
  - Une publication suspendue est retirée du site (404). Son auteur ne peut plus la modifier, la renouveler ni l'archiver.
- **Historique :** chaque transition, y compris automatique, est enregistrée dans `moderation_logs` (qui, quand, avant → après, motif) et affichée sur la fiche détaillée. Mécanisme : `PublicationLifecycle` émet `PublicationStatusChanged`, sur lequel se branchent les écouteurs `RecordModerationLog` et `SendPublicationStatusNotifications`.
- **E-mails à l'auteur :** publication en attente, en ligne (validée ou rétablie), refusée ou suspendue, avec le motif.
- **Alertes aux modérateurs :** notification dans la cloche de l'administration à chaque soumission et à chaque signalement.

### Signalements et contact

- **Signalement :** visiteur ou utilisateur, avec un motif, un commentaire facultatif et un e-mail facultatif. Un seul signalement par visiteur et par publication. Traitement dans Administration › Signalements : « Traiter », avec suspension possible, ou « Classer sans suite ».
- **Formulaire de contact :**
  - le message arrive par e-mail chez l'auteur, avec l'expéditeur en adresse de réponse. L'adresse de l'auteur n'est jamais affichée ;
  - les messages sont consultables dans `/espace/messages`, puis supprimés automatiquement après `CONTACT_MESSAGE_RETENTION_DAYS` jours (365 par défaut, à valider).
- **Anti-spam, sans service tiers (`App\Support\SpamGuard`) :** champ piège, délai minimal de 3 s avant l'envoi, limitation à 3 envois par minute et 30 par jour par IP. Seule une empreinte non réversible de l'IP est conservée.
- **Bienvenue :** un e-mail est envoyé une fois l'adresse e-mail vérifiée.

> Les e-mails passent par la file d'attente : en local, lancer `php artisan queue:work` (ou `composer dev`) pour qu'ils partent ; ils sont écrits dans `storage/logs/laravel.log` avec `MAIL_MAILER=log`.

### Visibilité commerciale et partenaires premium

- **Offres (`visibility_products`) :** mise en avant, boost et partenaire premium. Prix, durée, poids de tri et activation sont administrables (Administration › Commercial). Les prix initiaux sont ceux, **indicatifs**, de la maquette.
  - « Booster » n'a pas de règle propre en dehors de son poids de tri plus élevé et de son badge, puisque ses règles restent à définir.
- **Achats (`visibility_purchases`) :** le produit, le montant, la durée et le poids sont figés au moment de l'achat. Statuts : en attente de paiement → active → terminée, ou annulée.
  - **L'activation dépend uniquement de la confirmation du paiement** (événement `PaymentSucceeded` → `ActivatePaidVisibilityPurchase`).
  - Un nouvel achat du même type démarre à la fin de l'offre en cours.
  - La visibilité cesse exactement à `ends_at` ; la tâche `visibility:expire` (toutes les heures) met ensuite le statut à jour.
- **Affichage :**
  - les publications boostées puis mises en avant passent devant dans les listes ;
  - elles portent un badge distinct sur les cartes et les fiches, et un repère doré sur la carte ;
  - un filtre « Mises en avant » est disponible.
- **Partenaire premium (`partner_profiles`) :** entité distincte de l'organisation, avec une période de visibilité.
  - **Pages :** vitrine `/partenaires/{slug}`, annuaire `/partenaires`, bandeau sur l'accueil (rotation aléatoire), page des offres `/offres-de-visibilite`.
  - **Souscription :** par un propriétaire de l'organisation. Le contenu de la vitrine est modifiable par ses responsables.
  - **Administration :** l'admin peut aussi fixer une période à la main, par exemple pour un partenariat hors offre payante.

### Paiements

- **Couche indépendante du métier :**
  - la table `payments` est polymorphe ;
  - `App\Payments\PaymentManager` est le point d'entrée unique, avec des transitions idempotentes ;
  - chaque prestataire implémente l'interface `App\Payments\Contracts\PaymentGateway` (`config/payments.php`).
- **Prestataire `manual` :** virement ou chèque, avec les instructions définies par `PAYMENT_MANUAL_INSTRUCTIONS`. L'acheteur reçoit une référence `CAMP-{id}` ; l'administrateur confirme la réception dans Commercial › Achats › « Paiement reçu », ce qui active l'offre et envoie l'e-mail de confirmation.
- **Choix du prestataire :** `PAYMENT_COMMERCIAL_GATEWAY` fixe celui des achats commerciaux (`manual` par défaut, puisque l'éligibilité de HelloAsso n'est pas acquise). HelloAsso arrive en phase 7.

### Adhésions

- **Indépendance :** l'adhésion à l'association (`memberships`) est distincte des achats commerciaux. Seule la couche de paiement est commune.
- **Règles de période (`App\Actions\Memberships\MembershipPeriods`, configurables dans `campacity.membership`) :**
  - 12 mois à compter de la date de paiement, sans lien avec l'année civile ;
  - renouvellement anticipé : la nouvelle période démarre à l'expiration de la période en cours ;
  - renouvellement après expiration : nouvelle période à compter du nouveau paiement.
- **Activation :** une adhésion n'est active **qu'après confirmation du paiement** (`PaymentSucceeded` → `ActivatePaidMembership`), puis l'e-mail de confirmation d'adhésion part.
- **Rappels :** `memberships:send-reminders`, tous les jours à 8 h, envoie les rappels à J-30, J-7 et le jour J, sans doublon grâce à une clé unique dans `notification_logs`. Aucun rappel si l'adhérent a déjà renouvelé. La même tâche passe en « expirée » les adhésions échues.
- **Cotisation :** son montant est un paramètre administrable (Paramètres › Adhésions), 20 € par défaut, à valider.
- **Administration (Association › Membres et adhésions) :** filtres « à jour » et « expire dans 30 jours », action « Enregistrer une adhésion » pour un règlement hors ligne (même circuit de paiement et mêmes règles).
- **Historique des notifications :** Configuration › Notifications envoyées enregistre le type, le destinataire, le canal et la date, jamais le contenu.

### HelloAsso

- **Configuration (`.env`) :** `HELLOASSO_ENV` (`sandbox` ou `production`), `HELLOASSO_CLIENT_ID`, `HELLOASSO_CLIENT_SECRET`, `HELLOASSO_ORGANIZATION_SLUG`.
  - Le slug s'obtient via `GET /v5/users/me/organizations`.
  - Ne jamais commiter les identifiants.
- **Parcours :**
  - `HelloAssoGateway` crée un panier (checkout-intent) et redirige l'utilisateur vers HelloAsso ;
  - au retour, `HelloAssoPaymentVerifier` interroge l'API. Le paramètre `code` du retour étant falsifiable, il n'est jamais pris en compte ;
  - la validation exige une commande avec un paiement `Authorized` **et** un montant identique ;
  - un refus ou une annulation fait échouer le paiement.
- **Notifications :**
  - URL à déclarer dans le back-office HelloAsso (Mon compte › Intégrations et API) : `https://<domaine>/webhooks/helloasso` ;
  - pour une association, elles ne sont pas signées. Elles ne font donc que **déclencher une vérification via l'API** (job `VerifyHelloAssoPayment`, dédoublonné), sans jamais rien activer directement ;
  - option : `HELLOASSO_WEBHOOK_IPS` restreint les adresses IP émettrices (documentation HelloAsso : production 51.138.206.200, test 4.233.135.234).
- **Rapprochement :** `payments:reconcile`, toutes les 10 minutes, revérifie les paiements en attente, ce qui couvre une notification manquée (cas du poste local, injoignable depuis HelloAsso). Il marque comme abandonné un paiement sans autorisation après 2 heures.
- **Achats commerciaux :** `PAYMENT_COMMERCIAL_GATEWAY=helloasso` les fait passer par HelloAsso, une fois leur éligibilité confirmée par le prestataire.

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
- [x] Phase 5 : modération et contact
- [x] Phase 6 : partenaires et visibilité
- [x] Phase 7 : adhésions et paiements (HelloAsso)
- [ ] Phase 8 : finalisation
