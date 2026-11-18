<x-layouts.app title="Mes informations">
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        <x-page-header title="Mes informations" kicker="Mon espace" :kicker-url="route('account.dashboard')" />

        <section class="card mt-8 p-6" aria-labelledby="profil-titre">
            <h2 id="profil-titre" class="mb-4 text-lg font-bold">Profil et coordonnées</h2>
            <form method="POST" action="{{ route('user-profile-information.update') }}" novalidate>
                @csrf
                @method('PUT')
                <x-form.input name="name" label="Nom et prénom" :value="$user->name" bag="updateProfileInformation" autocomplete="name" required />
                <x-form.input name="email" type="email" label="Adresse e-mail" :value="$user->email" bag="updateProfileInformation" autocomplete="email" required help="Une nouvelle vérification sera demandée si vous changez d'adresse." />
                <x-form.input name="phone" type="tel" label="Téléphone" :value="$user->phone" bag="updateProfileInformation" autocomplete="tel" />
                <x-form.input name="website" type="url" label="Site internet" :value="$user->website" bag="updateProfileInformation" placeholder="https://" />

                <fieldset class="mt-2 mb-5">
                    <legend class="form-label">Coordonnées visibles sur mes publications</legend>
                    <p class="form-help mb-3">Ce que vous ne cochez pas reste privé. Les visiteurs pourront toujours vous écrire via le formulaire de contact, sans voir votre adresse.</p>
                    <x-form.checkbox name="show_email" label="Afficher mon e-mail" :checked="$user->show_email" bag="updateProfileInformation" />
                    <x-form.checkbox name="show_phone" label="Afficher mon téléphone" :checked="$user->show_phone" bag="updateProfileInformation" />
                    <x-form.checkbox name="show_website" label="Afficher mon site internet" :checked="$user->show_website" bag="updateProfileInformation" />
                </fieldset>
                <button type="submit" class="btn-primary">Enregistrer</button>
            </form>
        </section>

        <section class="card mt-6 p-6" aria-labelledby="mdp-titre">
            <h2 id="mdp-titre" class="mb-4 text-lg font-bold">Mot de passe</h2>
            <form method="POST" action="{{ route('user-password.update') }}" novalidate>
                @csrf
                @method('PUT')
                <x-form.input name="current_password" type="password" label="Mot de passe actuel" bag="updatePassword" autocomplete="current-password" required />
                <x-form.input name="password" type="password" label="Nouveau mot de passe" bag="updatePassword" autocomplete="new-password" required />
                <x-form.input name="password_confirmation" type="password" label="Confirmation" bag="updatePassword" autocomplete="new-password" required />
                <button type="submit" class="btn-primary">Modifier le mot de passe</button>
            </form>
        </section>

        <section class="card mt-6 border-red-200 p-6" aria-labelledby="suppression-titre">
            <h2 id="suppression-titre" class="text-lg font-bold text-red-800">Supprimer mon compte</h2>
            <p class="mt-1 text-sm text-muted">Vos données personnelles seront effacées et vous ne pourrez plus vous connecter. Les informations que la loi impose de conserver (adhésions, paiements) sont gardées sous forme anonymisée.</p>
            @error('account')<p class="form-error mt-3" role="alert">{{ $message }}</p>@enderror
            <form method="POST" action="{{ route('account.destroy') }}" class="mt-4" novalidate>
                @csrf
                @method('DELETE')
                <x-form.input name="password" type="password" label="Mot de passe" bag="deleteAccount" id="delete-password" autocomplete="current-password" required />
                <x-form.checkbox name="confirmation" label="Je comprends que cette action est définitive." bag="deleteAccount" />
                <button type="submit" class="btn-danger mt-2">Supprimer mon compte</button>
            </form>
        </section>
    </div>
</x-layouts.app>
