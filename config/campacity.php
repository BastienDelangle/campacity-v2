<?php

/*
| Valeurs par défaut des paramètres métier. Elles sont surchargeables depuis
| l'administration (table `settings`) : voir App\Support\Settings.
| Les valeurs marquées « à valider » ne sont pas arbitrées par le cahier des charges.
*/

return [
    'settings' => [
        // Durée de vie d'une publication avant expiration, en jours (à valider).
        'publication_duration_days' => 90,
        // Délai du rappel avant expiration, en jours.
        'publication_expiration_notice_days' => 7,
        // Une modification sensible d'une publication publiée repasse en modération
        // (sauf utilisateurs de confiance et modérateurs).
        'publication_edits_require_moderation' => true,
        // Nombre maximal d'images par publication.
        'publication_max_images' => 6,
        // Nombre maximal de pièces jointes par publication.
        'publication_max_attachments' => 3,
        // Montant de la cotisation annuelle, en centimes (à valider).
        'membership_fee_cents' => 2000,
    ],

    // Catégories rattachées à la rubrique « Formations et rendez-vous »
    // (en plus des types « événement »).
    'agenda_category_slugs' => ['formations'],

    // Carte : fournisseur de tuiles configurable (OpenStreetMap par défaut).
    // Pour un usage en production à fort trafic, prévoir un fournisseur dédié
    // (la politique d'usage des tuiles OSM limite les volumes).
    'map' => [
        'tile_url' => env('MAP_TILE_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
        'attribution' => env('MAP_ATTRIBUTION', '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'),
        'max_zoom' => (int) env('MAP_MAX_ZOOM', 18),
        // Centre par défaut si aucun territoire actif n'a de centre défini (France).
        'default_center' => [46.6, 2.4],
        'default_zoom' => 6,
        // Nombre maximal de repères envoyés à la carte.
        'max_markers' => 500,
    ],

    // Adhésions (cahier § 11). Le montant de la cotisation est un paramètre
    // administrable (settings.membership_fee_cents).
    'membership' => [
        'duration_months' => 12,
        // Renouvellement anticipé : prolongation à partir de l'expiration en cours.
        'early_renewal_extends' => true,
        // Rappels avant expiration, en jours (0 = le jour de l'expiration).
        'reminder_days' => [30, 7, 0],
    ],

    // Conservation des messages de contact, en jours (RGPD). À valider.
    'contact_message_retention_days' => (int) env('CONTACT_MESSAGE_RETENTION_DAYS', 365),

    // Anti-spam des formulaires publics : délai minimal (secondes) entre
    // l'affichage du formulaire et son envoi (les robots répondent instantanément).
    'form_min_seconds' => 3,

    // Images : largeur maximale après redimensionnement, en pixels.
    'image_max_width' => 1600,
];
