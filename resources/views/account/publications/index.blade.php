<x-layouts.app title="Mes publications">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
        <x-page-header title="Mes publications" kicker="Mon espace" :kicker-url="route('account.dashboard')">
            <x-slot:actions>
                <a href="{{ route('account.publications.create') }}" class="btn-primary">+ Nouvelle publication</a>
            </x-slot:actions>
        </x-page-header>

        <nav class="mt-6 flex flex-wrap gap-2" aria-label="Filtrer par statut">
            <a href="{{ route('account.publications.index') }}" @class(['rounded-full px-3 py-1.5 text-sm font-semibold', 'bg-brand text-white' => ! $currentStatus, 'bg-white ring-1 ring-line' => $currentStatus]) @if(! $currentStatus) aria-current="page" @endif>Toutes</a>
            @foreach ($statuses as $status)
                <a href="{{ route('account.publications.index', ['statut' => $status->value]) }}" @class(['rounded-full px-3 py-1.5 text-sm font-semibold', 'bg-brand text-white' => $currentStatus === $status, 'bg-white ring-1 ring-line' => $currentStatus !== $status]) @if($currentStatus === $status) aria-current="page" @endif>{{ $status->label() }}</a>
            @endforeach
        </nav>

        @if ($publications->isEmpty())
            <div class="card mt-8 p-8 text-center">
                <p class="text-muted">Aucune publication {{ $currentStatus ? 'avec ce statut' : 'pour le moment' }}.</p>
                <a href="{{ route('account.publications.create') }}" class="btn-primary mt-4">Publier une offre ou une demande</a>
            </div>
        @else
            <div class="card mt-6 overflow-hidden">
                <ul class="divide-y divide-line">
                    @foreach ($publications as $publication)
                        <li class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
                            @if ($cover = $publication->coverImage())
                                <img src="{{ $cover->url() }}" alt="" class="hidden h-16 w-24 shrink-0 rounded-lg object-cover sm:block" loading="lazy">
                            @else
                                <div class="hidden h-16 w-24 shrink-0 rounded-lg bg-lime sm:block" aria-hidden="true"></div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('account.publications.preview', $publication) }}" class="font-bold hover:underline">{{ $publication->title }}</a>
                                <p class="mt-1 text-xs text-muted">
                                    {{ $publication->type->name }} · {{ $publication->category->name }}
                                    @if ($publication->organization) · {{ $publication->organization->name }}@endif
                                    · modifiée le {{ $publication->updated_at->translatedFormat('j M Y') }}
                                    @if ($publication->expires_at && $publication->status === \App\Enums\PublicationStatus::Published) · expire le {{ $publication->expires_at->translatedFormat('j M Y') }}@endif
                                </p>
                            </div>
                            <x-publication-status :status="$publication->status" class="self-start sm:self-center" />
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="mt-6">{{ $publications->links() }}</div>
        @endif
    </div>
</x-layouts.app>
