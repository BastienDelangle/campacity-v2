<x-layouts.app title="Connexion">
    <x-auth-card title="Connexion">
        <form method="POST" action="{{ route('login') }}" novalidate>
            @csrf
            <x-form.input name="email" type="email" label="Adresse e-mail" autocomplete="email" required autofocus />
            <x-form.input name="password" type="password" label="Mot de passe" autocomplete="current-password" required />
            <div class="mb-6 flex items-center justify-between gap-3 text-sm">
                <label class="inline-flex items-center gap-2"><input type="checkbox" name="remember" class="rounded border-line text-brand"> Se souvenir de moi</label>
                <a href="{{ route('password.request') }}" class="font-semibold text-brand underline-offset-4 hover:underline">Mot de passe oublié ?</a>
            </div>
            <button type="submit" class="btn-primary w-full">Se connecter</button>
        </form>
        <x-slot:footer>Pas encore de compte ? <a href="{{ route('register') }}" class="font-bold text-brand">Créer un compte</a></x-slot:footer>
    </x-auth-card>
</x-layouts.app>
