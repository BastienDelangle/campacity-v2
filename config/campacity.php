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
    ],

    // Images : largeur maximale après redimensionnement, en pixels.
    'image_max_width' => 1600,
];
