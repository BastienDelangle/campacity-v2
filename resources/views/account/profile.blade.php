<x-layouts.app title="Mes informations">
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        <p class="kicker"><a href="{{ route('account.dashboard') }}" class="hover:underline">Mon espace</a></p>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-brand">Mes informations</h1>

        <section class="card mt-8 p-6" aria-labelledby="profil-titre">
            <h2 id="profil-titre" class="mb-4 text-lg font-bold">Profil</h2>
            <form method="POST" action="{{ route('user-profile-information.update') }}" novalidate>
                @csrf
                @method('PUT')
                <x-form.input name="name" label="Nom et prénom" :value="auth()->user()->name" bag="updateProfileInformation" autocomplete="name" required />
                <x-form.input name="email" type="email" label="Adresse e-mail" :value="auth()->user()->email" bag="updateProfileInformation" autocomplete="email" required help="Une nouvelle vérification sera demandée si vous changez d'adresse." />
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
    </div>
</x-layouts.app>
