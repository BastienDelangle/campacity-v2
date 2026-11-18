import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

/**
 * Crée une carte Leaflet avec le fournisseur de tuiles configuré côté serveur
 * (config/campacity.php › map), pour pouvoir en changer sans toucher au JS.
 */
export function createMap(element, center, config, options = {}) {
    const map = L.map(element, { scrollWheelZoom: false, ...options }).setView([center.lat, center.lng], center.zoom);

    L.tileLayer(config.tile_url, {
        attribution: config.attribution,
        maxZoom: config.max_zoom,
    }).addTo(map);

    return map;
}

export function pinIcon(featured = false) {
    const color = featured ? '#dba453' : '#224d3a';

    return L.divIcon({
        className: '',
        html: `<span style="display:block;width:18px;height:18px;border-radius:50% 50% 50% 0;background:${color};border:2px solid #fff;transform:rotate(-45deg);box-shadow:0 1px 4px rgba(0,0,0,.35)"></span>`,
        iconSize: [18, 18],
        iconAnchor: [9, 18],
        popupAnchor: [0, -18],
    });
}

export { L };
