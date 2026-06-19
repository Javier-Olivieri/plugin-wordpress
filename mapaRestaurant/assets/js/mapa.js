document.addEventListener('DOMContentLoaded', function () {
    const mapContainer = document.getElementById('mac-map');
    if (!mapContainer) return;

    // 🌍 Inicialización del mapa
    const map = L.map('mac-map', {
        dragging: true,
        scrollWheelZoom: true,
        zoomControl: false,
        doubleClickZoom: true,
        boxZoom: true,
        keyboard: true,
        worldCopyJump: true,
        zoomAnimation: true,
        fadeAnimation: false
    });

    // 🇦🇷 Vista estándar sobre Argentina
    const bounds = [[-55.3, -70], [-23.5, -59]];
    map.fitBounds(bounds);
    map.setMinZoom(4);
    map.setMaxZoom(18);

    // 🗺️ Capa base
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // ➕➖ Controles personalizados de zoom
  const zoomControls = L.control({ position: 'bottomright' });
zoomControls.onAdd = function () {
  const container = L.DomUtil.create('div', 'mac-zoom-container');

const zoomInBtn = L.DomUtil.create('button', 'mac-zoom-btn zoom-in', container);
zoomInBtn.innerHTML = '<span class="zoom-mas">+</span>';
zoomInBtn.title = 'Acercar mapa';

const zoomOutBtn = L.DomUtil.create('button', 'mac-zoom-btn zoom-out', container);
zoomOutBtn.innerHTML = '<span class="zoom-symbol">-</span>';

zoomOutBtn.title = 'Alejar mapa';

map.getContainer().appendChild(container);

    const minZoomCustom = 3;
    const maxZoomCustom = 18;

    function updateZoomButtons() {
        const currentZoom = map.getZoom();
        zoomInBtn.disabled = currentZoom >= maxZoomCustom;
        zoomOutBtn.disabled = currentZoom <= minZoomCustom;
    }

    zoomInBtn.onclick = () => {
        if (map._zoomingToHome) return;
        const currentZoom = map.getZoom();
        const center = map.getCenter();
        if (currentZoom < 4) {
            map.setView(center, 3, { animate: true, duration: 0.5 });
        } else if (currentZoom < maxZoomCustom) {
            map.setView(center, currentZoom + 1, { animate: true, duration: 0.5 });
        }
        updateZoomButtons();
    };

    zoomOutBtn.onclick = () => {
        if (map._zoomingToHome) return;
        const currentZoom = map.getZoom();
        if (currentZoom > minZoomCustom) {
            const prevMin = map.getMinZoom();
            map.setMinZoom(minZoomCustom);
            map.zoomOut();
            setTimeout(() => map.setMinZoom(prevMin), 50);
        }
        updateZoomButtons();
    };

    map.on('zoomend', updateZoomButtons);
    updateZoomButtons();
    return container;
};
zoomControls.addTo(map);

// 🧭 Botón volver al inicio (sin salto)
let homeBtnEl;
const homeButton = L.control({ position: 'topright' });
homeButton.onAdd = function () {
    homeBtnEl = L.DomUtil.create('button', 'mac-home-btn disabled');
    homeBtnEl.innerHTML = '🌎'; // globo terrestre
    homeBtnEl.title = 'Vista general (Argentina)';
    homeBtnEl.disabled = true;

    homeBtnEl.onclick = () => {
        if (!homeBtnEl.disabled) {
            homeBtnEl.innerHTML = '⏳'; // reloj de arena como cargando
            homeBtnEl.disabled = true;
            homeBtnEl.classList.add('disabled');

            map._zoomingToHome = true;
            map.flyToBounds(bounds, { duration: 3 });

            map.once('moveend', function () {
                const center = map.getCenter();
                const zoom = map.getZoom();

                const wasAnimated = map.options.zoomAnimation;
                map.options.zoomAnimation = false;
                map.invalidateSize();
                map.setView(center, zoom, { animate: false });
                setTimeout(() => {
                    map.options.zoomAnimation = wasAnimated;
                    map._zoomingToHome = false;
                    homeBtnEl.innerHTML = '🌎';
                }, 100);
            });
        }
    };
    return homeBtnEl;
};
homeButton.addTo(map);

    // 🏝️ Capa de Malvinas
    const malvinasBounds = [[-54, -64], [-49, -53]];
    if (macOptions.pluginUrl) {
        L.imageOverlay(macOptions.pluginUrl + 'images/malvinas-4.png', malvinasBounds, {
            opacity: 1,
            interactive: false
        }).addTo(map);
    }

    // 📍 Marcadores
    const puntos = macOptions.points || [];
    const estilos = macOptions.estilos || {};
    const markers = [];

    puntos.forEach(function (punto) {
        if (!punto.coords) return;
        const [lat, lng] = punto.coords.split(',').map(parseFloat);
        if (isNaN(lat) || isNaN(lng)) return;

        const shape = estilos.pin_shape || 'circle';
        const color = estilos.pin_color || '#ff4d4f';
        const size = estilos.pin_size || 7;
let marker;

const commonOptions = {
    color: color,
    radius: size,
    weight: 2,
    fillColor: color,
    fillOpacity: 0.9,
    interactive: true
};

if (shape === 'circle') {
    marker = L.circleMarker([lat, lng], commonOptions).addTo(map);
} else if (shape === 'pin') {
    // Pin clásico pero adaptado al estilo circle
    const iconHtml = `
        <svg width="${size*2}" height="${size*3}" viewBox="0 0 24 38" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path fill="${color}" d="M12 0C7.03 0 3 4.03 3 9c0 6 9 29 9 29s9-23 9-29c0-4.97-4.03-9-9-9z"/>
            <circle cx="12" cy="9" r="${size/2}" fill="white"/>
        </svg>
    `;
    const icon = L.divIcon({
        className: 'custom-pin-marker',
        html: iconHtml,
        iconSize: [size*2, size*3],
        iconAnchor: [size, size*3],
        popupAnchor: [0, -size*3],
    });
    marker = L.marker([lat, lng], { icon }).addTo(map);
} else {
    // Otros shapes (square, diamond, star)
    const shapeClass = shape || 'circle';
    const iconHtml = `
        <div class="mac-pin ${shapeClass} pulse"
             style="background:${color}; width:${size*2}px; height:${size*2}px; border:2px solid ${color}; opacity:0.9;">
        </div>`;
    const icon = L.divIcon({
        html: iconHtml,
        className: '',
        iconSize: [size*2, size*2]
    });
    marker = L.marker([lat, lng], { icon }).addTo(map);
}

markers.push(marker);



        marker.bindPopup(`
            <div class="mac-popup">
                <h4>${punto.nombre}</h4>
                ${punto.descripcion ? `<p>${punto.descripcion}</p>` : ''}
            </div>
        `);

        marker.on('mouseover', function () {
            this.setStyle && this.setStyle({ radius: size * 1.4, fillOpacity: 1 });
        });
        marker.on('mouseout', function () {
            this.setStyle && this.setStyle({ radius: size, fillOpacity: 0.9 });
        });
// Creamos un LayerGroup para manejar todos los marcadores juntos
// markers = [marker1, marker2, marker3...]
const markersLayer = L.layerGroup(markers).addTo(map);

marker.on('click', function () {
    const clickedMarker = this;

    // Esconder todos los marcadores
    markers.forEach(m => {
        const el = m.getElement();
        if (el) el.style.display = 'none';
    });

    // Animar el mapa al marcador
    map.flyTo(clickedMarker.getLatLng(), 13, { duration: 1.5, easeLinearity: 0.5 });

    // Al terminar la animación, volver a mostrar todos los marcadores
    map.once('moveend', () => {
        markers.forEach(m => {
            const el = m.getElement();
            if (el) el.style.display = '';
        });

        if (homeBtnEl) {
            homeBtnEl.disabled = false;
            homeBtnEl.classList.remove('disabled');
        }
    });
});






    });

    // 🌀 Distribución para evitar superposición (espiral + leve jitter)
    function jitterMarkers(markers, intensidad = 0.0003) {
        markers.forEach(m => {
            const { lat, lng } = m.getLatLng();
            const randomLat = lat + (Math.random() - 0.5) * intensidad;
            const randomLng = lng + (Math.random() - 0.5) * intensidad;
            m.setLatLng([randomLat, randomLng]);
        });
    }

    function separarMarkers(map, markers, radio = 0.0006) {
        const grupos = {};
        markers.forEach(m => {
            const latlng = m.getLatLng();
            const key = `${latlng.lat.toFixed(3)},${latlng.lng.toFixed(3)}`;
            if (!grupos[key]) grupos[key] = [];
            grupos[key].push(m);
        });

        Object.values(grupos).forEach(lista => {
            if (lista.length === 1) return;
            const centro = lista[0].getLatLng();
            const anguloStep = (2 * Math.PI) / lista.length;
            lista.forEach((m, i) => {
                const angulo = i * anguloStep;
                const offsetLat = centro.lat + radio * Math.cos(angulo);
                const offsetLng = centro.lng + radio * Math.sin(angulo);
                m.setLatLng([offsetLat, offsetLng]);
            });
        });
    }

    jitterMarkers(markers);
    separarMarkers(map, markers);

    // 💅 Estilos
    const css = `
    #mac-map {
        width: ${estilos.map_width || '100%'};
        height: ${estilos.map_height || 480}px;
        border-radius: ${estilos.map_border_radius || 16}px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.25);
        overflow: hidden;
        background: #f2f4f7;
    }
    .mac-zoom-btn, .mac-home-btn {
        background: rgba(255,255,255,0.85);
        border: 1px solid #ccc;
        border-radius: 10px;
        cursor: pointer;
        width: 40px;
        height: 40px;
        font-size: 18px;
        margin: 6px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        transition: all 0.2s ease;
        display: flex;
        justify-content: center;
     
    }

.mac-home-btn {
    padding-top: 11px;}

.zoom-symbol {
    font-size: 35px;
    color: #6524c0;
    margin-top: -7px;
}

.zoom-mas {
    font-size: 32px;
    color: #6524c0;
    margin-top: -4px;
}

@media (max-width: 768px) {
.zoom-symbol {
margin-top: -4px;}
.zoom-mas {
margin-top: 0px;}
}

    .mac-zoom-btn:focus,
.mac-home-btn:focus,
.mac-zoom-btn:active,
.mac-home-btn:active {
    outline: none;          /* Quita el contorno azul por defecto */
    box-shadow: 0 4px 10px rgba(0,0,0,0.15); /* Mantiene el mismo sombreado */
    background: rgba(255,255,255,0.85); /* Mantiene el mismo fondo */
    border-color: #ccc;     /* Mantiene el mismo borde */
}

    .mac-home-btn.disabled {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }
    .mac-home-btn:hover:not(.disabled),
    .mac-zoom-btn:hover {
        background: #f3f3f3;
        transform: scale(1.08);
    }
    .mac-zoom-controls { 
        display: flex; 
        flex-direction: column; 
        align-items: center; 
    }
    .fa-spinner { color: #555; }
.mac-pin.square {
    border-radius: 0;
}

.mac-pin.diamond {
    transform: rotate(45deg);
}

.mac-pin.star {
    clip-path: polygon(
        50% 0%,
        61% 35%,
        98% 35%,
        68% 57%,
        79% 91%,
        50% 70%,
        21% 91%,
        32% 57%,
        2% 35%,
        39% 35%
    );
}

    `;
    const style = document.createElement('style');
    style.innerHTML = css;
    document.head.appendChild(style);

});
