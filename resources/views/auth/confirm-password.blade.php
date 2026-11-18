<x-layouts.app title="Confirmer le mot de passe">
    <x-auth-card title="Confirmez votre mot de passe" intro="Cette action est sensible : merci de confirmer votre mot de passe.">
        <form method="POST" action="{{ route('password.confirm') }}" novalidate>
            @csrf
            <x-form.input name="password" type="password" label="Mot de passe" autocomplete="current-password" required autofocus />
            <button type="submit" class="btn-primary w-full">Confirmer</button>
        </form>
    </x-auth-card>
</x-layouts.app>
