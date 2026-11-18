<section class="card mt-6 p-6" aria-labelledby="responsables">
    <h2 id="responsables" class="text-lg font-bold">Responsables</h2>
    <p class="mt-1 text-sm text-muted">Les propriétaires gèrent la fiche et les responsables ; les éditeurs gèrent uniquement la fiche.</p>
    @error('member')<p class="form-error mt-3" role="alert">{{ $message }}</p>@enderror

    <ul class="mt-4 divide-y divide-line">
        @foreach ($organization->members as $member)
            <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                <div class="min-w-0">
                    <p class="truncate font-semibold">{{ $member->name }} @if($member->is(auth()->user()))<span class="text-xs font-normal text-muted">(vous)</span>@endif</p>
                    <p class="truncate text-xs text-muted">{{ $member->email }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($canManageMembers)
                        <form method="POST" action="{{ route('account.organizations.members.update', [$organization, $member]) }}" class="flex items-center gap-2">
                            @csrf
                            @method('PUT')
                            <label for="role-{{ $member->id }}" class="sr-only">Rôle de {{ $member->name }}</label>
                            <select id="role-{{ $member->id }}" name="role" class="form-input w-auto py-1.5 text-sm" onchange="this.form.requestSubmit()">
                                @foreach ($roles as $role)
                                    <option value="{{ $role->value }}" @selected($member->pivot->role === $role->value)>{{ $role->label() }}</option>
                                @endforeach
                            </select>
                            <noscript><button type="submit" class="btn-secondary px-3 py-1.5">OK</button></noscript>
                        </form>
                    @else
                        <span class="tag">{{ \App\Enums\OrganizationRole::from($member->pivot->role)->label() }}</span>
                    @endif

                    @if ($canManageMembers || $member->is(auth()->user()))
                        <form method="POST" action="{{ route('account.organizations.members.destroy', [$organization, $member]) }}" onsubmit="return confirm('{{ $member->is(auth()->user()) ? 'Ne plus gérer cette organisation ?' : 'Retirer ce responsable ?' }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-semibold text-red-700 hover:underline">{{ $member->is(auth()->user()) ? 'Me retirer' : 'Retirer' }}</button>
                        </form>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>

    @if ($canManageMembers)
        <form method="POST" action="{{ route('account.organizations.members.store', $organization) }}" class="mt-4 border-t border-line pt-4" novalidate>
            @csrf
            <h3 class="mb-3 font-bold">Ajouter un responsable</h3>
            <div class="grid gap-x-3 sm:grid-cols-[2fr_1fr_auto] sm:items-end">
                <x-form.input name="member_email" type="email" label="E-mail de son compte Campacity" bag="members" required />
                <x-form.select name="member_role" label="Rôle" bag="members">
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected(old('member_role', 'editor') === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </x-form.select>
                <button type="submit" class="btn-primary mb-4">Ajouter</button>
            </div>
        </form>
    @endif
</section>
