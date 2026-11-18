<x-layouts.app title="Nouveau mot de passe">
    <x-auth-card title="Choisir un nouveau mot de passe">
        <form method="POST" action="{{ route('password.update') }}" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <x-form.input name="email" type="email" label="Adresse e-mail" :value="$request->email" autocomplete="email" required />
            <x-form.input name="password" type="password" label="Nouveau mot de passe" autocomplete="new-password" required autofocus />
            <x-form.input name="password_confirmation" type="password" label="Confirmation" autocomplete="new-password" required />
            <button type="submit" class="btn-primary w-full">Enregistrer</button>
        </form>
    </x-auth-card>
</x-layouts.app>
