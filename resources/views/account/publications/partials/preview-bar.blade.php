@php($status = $publication->status)
<div class="mb-6 rounded-2xl border border-[#eedba8] bg-[#fff9e8] p-4 sm:p-5" role="region" aria-label="Gestion de la publication">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm font-bold text-[#7a6233]">Aperçu · <x-publication-status :status="$status" /></p>
            <p class="mt-1 text-sm text-[#7a6233]">
                @switch($status)
                    @case(\App\Enums\PublicationStatus::Draft) Vérifiez votre publication puis soumettez-la. @break
                    @case(\App\Enums\PublicationStatus::Pending) En cours de vérification par l'équipe de modération. @break
                    @case(\App\Enums\PublicationStatus::Rejected) Publication refusée. Corrigez-la puis soumettez-la à nouveau. @break
                    @case(\App\Enums\PublicationStatus::Expired) Publication expirée : elle n'est plus visible. @break
                    @case(\App\Enums\PublicationStatus::Archived) Publication archivée : elle n'est plus visible. @break
                    @default En ligne @if($publication->expires_at) jusqu'au {{ $publication->expires_at->translatedFormat('j F Y') }}@endif.
                @endswitch
            </p>
            @if ($status === \App\Enums\PublicationStatus::Rejected && $publication->rejection_reason)
                <p class="mt-2 text-sm"><strong>Motif :</strong> {{ $publication->rejection_reason }}</p>
            @endif
            @error('status')<p class="form-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="flex flex-wrap gap-2">
            @can('update', $publication)
                <a href="{{ route('account.publications.edit', $publication) }}" class="btn-secondary px-4 py-2">Modifier</a>
            @endcan
            @if (in_array($status, [\App\Enums\PublicationStatus::Draft, \App\Enums\PublicationStatus::Rejected], true))
                <form method="POST" action="{{ route('account.publications.submit', $publication) }}">@csrf<button type="submit" class="btn-primary px-4 py-2">Soumettre</button></form>
            @endif
            @if ($canRenew ?? false)
                <form method="POST" action="{{ route('account.publications.renew', $publication) }}">@csrf<button type="submit" class="btn-primary px-4 py-2">{{ $status === \App\Enums\PublicationStatus::Published ? 'Prolonger' : 'Remettre en ligne' }}</button></form>
            @endif
            @if (in_array($status, [\App\Enums\PublicationStatus::Published, \App\Enums\PublicationStatus::Pending, \App\Enums\PublicationStatus::Rejected, \App\Enums\PublicationStatus::Expired], true))
                <form method="POST" action="{{ route('account.publications.archive', $publication) }}" onsubmit="return confirm('Archiver cette publication ?')">@csrf<button type="submit" class="btn-secondary px-4 py-2">Archiver</button></form>
            @endif
        </div>
    </div>
</div>
