<x-layouts.app title="Mot de passe oublié">
    <x-auth-card title="Mot de passe oublié" intro="Indiquez votre adresse e-mail : nous vous enverrons un lien pour choisir un nouveau mot de passe.">
        <form method="POST" action="{{ route('password.email') }}" novalidate>
            @csrf
            <x-form.input name="email" type="email" label="Adresse e-mail" autocomplete="email" required autofocus />
            <button type="submit" class="btn-primary w-full">Envoyer le lien</button>
        </form>
        <x-slot:footer><a href="{{ route('login') }}" class="font-bold text-brand">Retour à la connexion</a></x-slot:footer>
    </x-auth-card>
</x-layouts.app>
