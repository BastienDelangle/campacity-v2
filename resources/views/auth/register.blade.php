<x-layouts.app title="Créer un compte">
    <x-auth-card title="Créer un compte" intro="Particulier, association, entreprise, collectivité… Créez votre compte pour publier et entrer en relation avec d'autres acteurs.">
        <form method="POST" action="{{ route('register') }}" novalidate>
            @csrf
            <x-form.input name="name" label="Nom et prénom" autocomplete="name" required autofocus />
            <x-form.input name="email" type="email" label="Adresse e-mail" autocomplete="email" required />
            <x-form.input name="password" type="password" label="Mot de passe" autocomplete="new-password" required help="8 caractères minimum." />
            <x-form.input name="password_confirmation" type="password" label="Confirmation du mot de passe" autocomplete="new-password" required />
            <div class="mb-6">
                <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="terms" value="1" class="mt-1 rounded border-line text-brand" @checked(old('terms'))> <span>J'accepte que mes données soient traitées pour la gestion de mon compte Campacity.</span></label>
                @error('terms')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn-primary w-full">Créer mon compte</button>
        </form>
        <x-slot:footer>Déjà inscrit ? <a href="{{ route('login') }}" class="font-bold text-brand">Se connecter</a></x-slot:footer>
    </x-auth-card>
</x-layouts.app>
