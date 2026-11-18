<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

/**
 * Protection anti-spam des formulaires publics, sans service tiers :
 * - champ piège invisible (`website_confirmation`) que seuls les robots remplissent ;
 * - délai minimal entre l'affichage et l'envoi (horodatage chiffré `form_token`).
 * À combiner avec une limitation de débit sur la route.
 */
class SpamGuard
{
    public const HONEYPOT = 'website_confirmation';

    public const TOKEN = 'form_token';

    public static function token(): string
    {
        return Crypt::encryptString((string) now()->timestamp);
    }

    /**
     * @throws ValidationException
     */
    public static function check(Request $request, string $errorBag = 'default'): void
    {
        $tooFast = true;

        try {
            $renderedAt = (int) Crypt::decryptString((string) $request->input(self::TOKEN));
            $tooFast = now()->timestamp - $renderedAt < (int) config('campacity.form_min_seconds', 3);
        } catch (\Throwable) {
            // Jeton absent ou altéré : traité comme un envoi suspect.
        }

        if (filled($request->input(self::HONEYPOT)) || $tooFast) {
            throw ValidationException::withMessages([
                'form' => 'Votre message n\'a pas pu être envoyé. Merci de patienter quelques secondes puis de réessayer.',
            ])->errorBag($errorBag);
        }
    }

    /**
     * Empreinte non réversible de l'adresse IP (limitation des abus sans stocker l'IP).
     */
    public static function ipHash(Request $request): string
    {
        return hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
    }
}
