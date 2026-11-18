<x-layouts.app title="Vérifiez votre adresse e-mail">
    <x-auth-card title="Vérifiez votre adresse e-mail" intro="Un lien de confirmation vient de vous être envoyé. Cliquez dessus pour activer votre compte. Pensez à vérifier vos courriers indésirables.">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-primary w-full">Renvoyer le lien</button>
        </form>
    </x-auth-card>
</x-layouts.app>
