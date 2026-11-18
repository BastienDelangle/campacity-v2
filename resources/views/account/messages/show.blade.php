<x-layouts.app title="Message de {{ $message->sender_name }}">
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        <x-page-header :title="'Message de '.$message->sender_name" kicker="Mes messages" :kicker-url="route('account.messages.index')" />

        <article class="card mt-8 p-6">
            <dl class="grid gap-2 text-sm sm:grid-cols-[8rem_1fr]">
                <dt class="font-bold">De</dt><dd>{{ $message->sender_name }} &lt;<a href="mailto:{{ $message->sender_email }}" class="text-brand underline">{{ $message->sender_email }}</a>&gt;</dd>
                <dt class="font-bold">Publication</dt>
                <dd>
                    @if ($message->publication && ! $message->publication->trashed())
                        <a href="{{ route('publications.show', $message->publication->slug) }}" class="text-brand underline">{{ $message->publication->title }}</a>
                    @else
                        {{ $message->publication?->title ?? 'Publication supprimée' }}
                    @endif
                </dd>
                <dt class="font-bold">Reçu le</dt><dd>{{ $message->created_at->translatedFormat('j F Y à H\hi') }}</dd>
            </dl>
            <div class="mt-6 border-t border-line pt-6 leading-relaxed whitespace-pre-line">{{ $message->message }}</div>
        </article>

        <div class="mt-6 flex flex-wrap justify-between gap-3">
            <a href="mailto:{{ $message->sender_email }}?subject={{ rawurlencode('Re : '.($message->publication?->title ?? 'votre message')) }}" class="btn-primary">Répondre par e-mail</a>
            <form method="POST" action="{{ route('account.messages.destroy', $message) }}" onsubmit="return confirm('Supprimer ce message ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-secondary">Supprimer</button>
            </form>
        </div>
    </div>
</x-layouts.app>
