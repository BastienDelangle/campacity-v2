<?php

use App\Payments\Gateways\ManualGateway;

/*
| Prestataires de paiement. Le métier (achats de visibilité, adhésions) ne
| connaît que l'interface App\Payments\Contracts\PaymentGateway : changer ou
| ajouter un prestataire ne touche pas aux règles métier.
*/

return [
    // Prestataire des achats commerciaux (visibilité). L'éligibilité de HelloAsso
    // pour ces achats n'est pas acquise : « manual » par défaut.
    'commercial_gateway' => env('PAYMENT_COMMERCIAL_GATEWAY', 'manual'),

    'gateways' => [
        'manual' => [
            'class' => ManualGateway::class,
            'label' => 'Virement ou chèque',
            // Instructions affichées à l'acheteur (RIB, ordre du chèque…).
            'instructions' => env('PAYMENT_MANUAL_INSTRUCTIONS', 'Réglez par virement ou par chèque à l\'ordre de l\'association en indiquant la référence ci-dessous. Votre offre sera activée dès réception du paiement.'),
        ],
    ],
];
