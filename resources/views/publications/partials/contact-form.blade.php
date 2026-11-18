<section id="contacter" class="card p-5" aria-labelledby="contacter-titre">
    <h2 id="contacter-titre" class="font-bold">Envoyer un message</h2>
    @if (session('contact_status'))
        <p role="status" class="mt-3 rounded-lg bg-[#edf5e6] p-3 text-sm font-semibold text-brand">{{ session('contact_status') }}</p>
    @else
        <p class="mt-1 text-xs text-muted">Votre message est transmis par e-mail à {{ $publication->publisherName() }}, qui vous répondra directement.</p>
        @error('form', 'contact')<p class="form-error mt-2" role="alert">{{ $message }}</p>@enderror
        <form method="POST" action="{{ route('publications.contact', $publication) }}" class="relative mt-4" novalidate>
            @csrf
            <x-form.spam-guard />
            <x-form.input name="sender_name" label="Votre nom" :value="auth()->user()?->name" bag="contact" autocomplete="name" maxlength="100" required />
            <x-form.input name="sender_email" type="email" label="Votre e-mail" :value="auth()->user()?->email" bag="contact" autocomplete="email" required />
            <x-form.textarea name="message" label="Message" bag="contact" rows="5" maxlength="5000" required />
            <x-form.checkbox name="consent" bag="contact" label="J'accepte que mon nom, mon e-mail et mon message soient transmis à l'auteur de la publication." />
            <button type="submit" class="btn-primary mt-2 w-full">Envoyer</button>
        </form>
    @endif
</section>
