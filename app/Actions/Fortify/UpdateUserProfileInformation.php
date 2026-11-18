<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        $validated = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+().\s-]{6,30}$/'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'show_email' => ['boolean'],
            'show_phone' => ['boolean'],
            'show_website' => ['boolean'],
        ], attributes: [
            'show_email' => 'afficher l\'e-mail',
            'show_phone' => 'afficher le téléphone',
            'show_website' => 'afficher le site',
        ])->validateWithBag('updateProfileInformation');

        $emailChanged = $validated['email'] !== $user->email;

        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'website' => $validated['website'] ?? null,
            'show_email' => (bool) ($validated['show_email'] ?? false),
            'show_phone' => (bool) ($validated['show_phone'] ?? false),
            'show_website' => (bool) ($validated['show_website'] ?? false),
        ]);

        if ($emailChanged && $user instanceof MustVerifyEmail) {
            $user->forceFill(['email_verified_at' => null])->save();
            $user->sendEmailVerificationNotification();

            return;
        }

        $user->save();
    }
}
