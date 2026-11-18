<details id="signaler" class="text-sm" @if($errors->report->any() || session('report_status')) open @endif>
    <summary class="cursor-pointer text-muted hover:text-ink">⚑ Signaler cette publication</summary>
    @if (session('report_status'))
        <p role="status" class="mt-3 rounded-lg bg-[#edf5e6] p-3 font-semibold text-brand">{{ session('report_status') }}</p>
    @else
        <form method="POST" action="{{ route('publications.report', $publication) }}" class="card relative mt-3 p-4" novalidate>
            @csrf
            <x-form.spam-guard />
            @error('form', 'report')<p class="form-error mb-2" role="alert">{{ $message }}</p>@enderror
            <x-form.select name="reason" label="Motif" bag="report" placeholder="— Choisir —" required>
                @foreach (\App\Enums\ReportReason::cases() as $reason)
                    <option value="{{ $reason->value }}" @selected(old('reason') === $reason->value)>{{ $reason->label() }}</option>
                @endforeach
            </x-form.select>
            <x-form.textarea name="comment" label="Précisions (facultatif)" bag="report" rows="3" maxlength="2000" />
            @guest
                <x-form.input name="reporter_email" type="email" label="Votre e-mail (facultatif)" bag="report" help="Pour être recontacté(e) si besoin." />
            @endguest
            <button type="submit" class="btn-secondary w-full">Envoyer le signalement</button>
        </form>
    @endif
</details>
