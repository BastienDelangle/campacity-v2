<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

#[Signature('campacity:create-admin {email} {--name=}')]
#[Description('Crée (ou promeut) un compte administrateur')]
class CreateAdmin extends Command
{
    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $password = (string) $this->secret('Mot de passe');
            $validator = Validator::make(
                ['email' => $email, 'password' => $password],
                ['email' => ['required', 'email'], 'password' => ['required', Password::defaults()]],
            );

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {
                    $this->error($error);
                }

                return self::FAILURE;
            }

            $user = User::create([
                'name' => $this->option('name') ?: 'Administrateur',
                'email' => $email,
                'password' => $password,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $user->assignRole(Role::Admin->value);
        $this->info("{$user->email} est administrateur.");

        return self::SUCCESS;
    }
}
