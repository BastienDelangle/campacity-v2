/**
 * Champ de saisie avec suggestions de communes (combobox accessible).
 * La valeur envoyée est l'identifiant de la commune (champ caché).
 */
export default (endpoint, initial) => ({
    query: initial?.label ?? '',
    selectedId: initial?.id ?? '',
    results: [],
    open: false,
    active: -1,
    controller: null,

    async search() {
        this.selectedId = '';
        const term = this.query.trim();

        if (term.length < 2) {
            this.results = [];
            this.open = false;
            return;
        }

        this.controller?.abort();
        this.controller = new AbortController();

        try {
            const response = await fetch(`${endpoint}?q=${encodeURIComponent(term)}`, {
                headers: { Accept: 'application/json' },
                signal: this.controller.signal,
            });
            this.results = response.ok ? await response.json() : [];
            this.active = this.results.length ? 0 : -1;
            this.open = true;
        } catch (error) {
            if (error.name !== 'AbortError') {
                this.results = [];
            }
        }
    },

    choose(result) {
        this.selectedId = result.id;
        this.query = result.label;
        this.open = false;
        window.dispatchEvent(new CustomEvent('commune-selected', { detail: result }));
    },

    move(step) {
        if (!this.open || !this.results.length) return;
        this.active = (this.active + step + this.results.length) % this.results.length;
    },

    confirm() {
        if (this.open && this.results[this.active]) {
            this.choose(this.results[this.active]);
        }
    },
});
