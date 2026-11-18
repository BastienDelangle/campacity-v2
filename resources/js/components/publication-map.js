
const escape = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

/**
 * Carte des publications. Les repères sont fournis par le serveur (mêmes
 * filtres que la liste) et rafraîchis à chaque changement de filtre.
 */
export default (center, markers, config) => ({
    map: null,
    layer: null,

    leaflet: null,

    async init() {
        // Leaflet n'est chargé que sur les pages qui affichent une carte.
        this.leaflet = await import('./leaflet');
        this.map = this.leaflet.createMap(this.$refs.map, center, config);
        this.layer = this.leaflet.L.layerGroup().addTo(this.map);
        this.render(markers, true);

        this.onMarkers = (event) => this.render(event.detail.markers, true);
        window.addEventListener('map-markers', this.onMarkers);
    },

    destroy() {
        window.removeEventListener('map-markers', this.onMarkers);
        this.map?.remove();
    },

    render(items, fit = false) {
        if (!this.layer) return;
        const { L, pinIcon } = this.leaflet;
        this.layer.clearLayers();

        const bounds = [];
        for (const item of items) {
            const marker = L.marker([item.lat, item.lng], { icon: pinIcon(item.featured), title: item.title, alt: item.title });
            marker.bindPopup(
                `<strong>${escape(item.title)}</strong><br><span style="color:#67756c">${escape(item.category)}${item.commune ? ' · ' + escape(item.commune) : ''}</span><br>` +
                `<a href="${escape(item.url)}" style="color:#224d3a;font-weight:700">Voir la publication →</a>`
            );
            marker.addTo(this.layer);
            bounds.push([item.lat, item.lng]);
        }

        if (fit && bounds.length > 0) {
            this.map.fitBounds(bounds, { padding: [30, 30], maxZoom: 13 });
        }
    },
});
