
/**
 * Sélection d'une position sur la carte (formulaire de publication).
 * Un clic place le repère ; les coordonnées sont stockées dans deux champs cachés.
 */
export default (initial, fallbackCenter, config) => ({
    lat: initial.lat,
    lng: initial.lng,
    map: null,
    marker: null,

    leaflet: null,

    async init() {
        this.leaflet = await import('./leaflet');
        const center = this.lat !== null ? { lat: this.lat, lng: this.lng, zoom: 14 } : fallbackCenter;
        this.map = this.leaflet.createMap(this.$refs.map, center, config);

        if (this.lat !== null) {
            this.place(this.lat, this.lng);
        }

        this.map.on('click', (event) => this.place(event.latlng.lat, event.latlng.lng));

        this.onCommune = (event) => {
            if (this.lat === null && event.detail.lat) {
                this.map.setView([event.detail.lat, event.detail.lng], 13);
            }
        };
        window.addEventListener('commune-selected', this.onCommune);
    },

    destroy() {
        window.removeEventListener('commune-selected', this.onCommune);
        this.map?.remove();
    },

    place(lat, lng) {
        this.lat = Math.round(lat * 1e7) / 1e7;
        this.lng = Math.round(lng * 1e7) / 1e7;

        if (this.marker) {
            this.marker.setLatLng([this.lat, this.lng]);
        } else {
            const { L, pinIcon } = this.leaflet;
            this.marker = L.marker([this.lat, this.lng], { icon: pinIcon(), draggable: true }).addTo(this.map);
            this.marker.on('dragend', () => {
                const position = this.marker.getLatLng();
                this.place(position.lat, position.lng);
            });
        }
    },

    clear() {
        this.lat = null;
        this.lng = null;
        this.marker?.remove();
        this.marker = null;
    },
});
