{{-- Protection anti-spam : champ piège masqué + horodatage chiffré. --}}
<div aria-hidden="true" style="position:absolute;left:-10000px;width:1px;height:1px;overflow:hidden">
    <label>Ne pas remplir ce champ <input type="text" name="{{ \App\Support\SpamGuard::HONEYPOT }}" tabindex="-1" autocomplete="off"></label>
</div>
<input type="hidden" name="{{ \App\Support\SpamGuard::TOKEN }}" value="{{ \App\Support\SpamGuard::token() }}">
