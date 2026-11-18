<?php

namespace Tests\Concerns;

use App\Support\SpamGuard;
use Illuminate\Support\Facades\Crypt;

trait SubmitsPublicForms
{
    /**
     * Jeton anti-spam d'un formulaire affiché il y a une minute (envoi « humain »).
     *
     * @return array<string, string>
     */
    protected function humanFormFields(): array
    {
        return [
            SpamGuard::TOKEN => Crypt::encryptString((string) now()->subMinute()->timestamp),
            SpamGuard::HONEYPOT => '',
        ];
    }
}
