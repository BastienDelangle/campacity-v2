<x-layouts.app title="Mes messages">
    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
        <x-page-header title="Mes messages" kicker="Mon espace" :kicker-url="route('account.dashboard')" />
        <p class="mt-3 text-sm text-muted">Messages reçus via le formulaire de contact de vos publications. Ils sont conservés {{ (int) round(config('campacity.contact_message_retention_days') / 30) }} mois.</p>

        @if ($messages->isEmpty())
            <div class="card mt-8 p-8 text-center text-muted">Aucun message reçu pour le moment.</div>
        @else
            <ul class="card mt-6 divide-y divide-line overflow-hidden">
                @foreach ($messages as $message)
                    <li>
                        <a href="{{ route('account.messages.show', $message) }}" class="flex flex-col gap-1 p-4 hover:bg-cream sm:flex-row sm:items-center sm:gap-4">
                            <span class="flex items-center gap-2 sm:w-48 sm:shrink-0">
                                @unless ($message->isRead())<span class="h-2 w-2 rounded-full bg-accent" aria-hidden="true"></span><span class="sr-only">Non lu :</span>@endunless
                                <span @class(['truncate', 'font-bold' => ! $message->isRead()])>{{ $message->sender_name }}</span>
                            </span>
                            <span class="min-w-0 flex-1 truncate text-sm text-muted">
                                <span class="text-ink">{{ $message->publication?->title ?? 'Publication supprimée' }}</span> — {{ \Illuminate\Support\Str::limit($message->message, 80) }}
                            </span>
                            <time datetime="{{ $message->created_at->toIso8601String() }}" class="shrink-0 text-xs text-muted">{{ $message->created_at->translatedFormat('j M Y') }}</time>
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="mt-6">{{ $messages->links() }}</div>
        @endif
    </div>
</x-layouts.app>
