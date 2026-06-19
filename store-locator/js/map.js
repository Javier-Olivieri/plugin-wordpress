document.addEventListener('DOMContentLoaded', function () {
  const map = L.map('store-map').setView([-34.6037, -58.3816], 4);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19
  }).addTo(map);
// Coordenadas de la imagen: [[sur, oeste], [norte, este]]
// Bounds más ancho
const bounds = [
  [-54, -64],  // sur más abajo
  [-49, -53]   // norte más abajo
];




L.imageOverlay(storeLocatorData.pluginUrl + 'images/malvinas-4.png', bounds, {
  opacity: 1,
  interactive: false
}).addTo(map);









  function createMarkerIcon(type, color, personalizadoUrl) {
    if (type === 'circle') {
      return L.divIcon({
        className: 'custom-circle-marker',
        html: `<svg width="24" height="24" viewBox="0 0 24 24" fill="${color}" xmlns="http://www.w3.org/2000/svg">
                 <circle cx="12" cy="12" r="10"/>
               </svg>`,
        iconSize: [24, 24],
        iconAnchor: [12, 12],
        popupAnchor: [0, -12],
      });
    } else if (type === 'divIcon') {
      return L.divIcon({
        className: 'custom-div-icon',
        html: `<div style="background:${color};width:20px;height:20px;border-radius:50%;"></div>`,
        iconSize: [20, 20],
        iconAnchor: [10, 10],
        popupAnchor: [0, -10],
      });
    } else if (type === 'pin') {
      return L.divIcon({
        className: 'custom-pin-marker',
        html: `<svg width="24" height="38" viewBox="0 0 24 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                 <path fill="${color}" d="M12 0C7.03 0 3 4.03 3 9c0 6 9 29 9 29s9-23 9-29c0-4.97-4.03-9-9-9z"/>
                 <circle cx="12" cy="9" r="4" fill="white"/>
               </svg>`,
        iconSize: [24, 38],
        iconAnchor: [12, 38],
        popupAnchor: [0, -38],
      });
    } else if (type === 'personalizado' && personalizadoUrl) {
      return L.divIcon({
        className: 'custom-personalizado-icon',
        html: `<img src="${personalizadoUrl}" style="width:32px; height:32px; object-fit:contain;" alt="Icono personalizado" />`,
        iconSize: [32, 32],
        iconAnchor: [16, 32],
        popupAnchor: [0, -32],
      });
    } else {
      return L.icon({
        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
        iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41],
      });
    }
  }

  Promise.all([
    fetch(storeLocatorData.styleUrl).then(res => res.json()),
    fetch(storeLocatorData.textStyleUrl).then(res => res.json())
  ]).then(([mapStyles, textStyles]) => {
    const panel = document.getElementById('store-info-panel');
    const wrapper = document.querySelector('.store-locator-wrapper');

    // Aplica estilos del panel de info
    const sInfopanel = textStyles.storeInfoPanel || {};
    panel.style.backgroundColor = sInfopanel.backgroundColor;
    panel.style.border = sInfopanel.border ;
    panel.style.padding = sInfopanel.padding;
    panel.style.borderRadius = sInfopanel.borderRadius;

        const sPopup = textStyles.storePopup || {};
 const popupCss = `
  .leaflet-popup-content-wrapper {
    background-color: ${sPopup.backgroundColor || 'inherit'};
    color: ${sPopup.color || 'inherit'};
    font-family: ${sPopup.fontFamily || 'inherit'};
    font-size: ${sPopup.fontSize || 'inherit'};
    padding: ${sPopup.padding || 'inherit'};
    border-radius: ${sPopup.borderRadius || 'inherit'};
  }
  .leaflet-popup-content {
    margin: 0;
  }

    .leaflet-popup-tip {
    background-color: ${sPopup.backgroundColor || 'inherit'};
    /* Podés agregar más estilos si querés */
  }

    .leaflet-container a.leaflet-popup-close-button {
    color: ${sPopup.closeButtonColor || 'inherit'};
    background: ${sPopup.closeButtonBg || 'transparent'};
    border-radius: 50%;
    width: 24px;
    height: 24px;
    text-align: center;
    line-height: 24px;
    font-size: 18px;
    font-weight: bold;
    box-shadow: 0 0 3px rgba(0,0,0,0.2);
    transition: background 0.3s ease;
  }
  .leaflet-container a.leaflet-popup-close-button:hover {
    background: ${sPopup.closeButtonHoverBg || 'inherit'};
    color: ${sPopup.closeButtonHoverColor || 'inherit'};
  }
`;

    const popupStyleTag = document.createElement('style');
    popupStyleTag.innerHTML = popupCss;
    document.head.appendChild(popupStyleTag);

    // Aplica otros estilos del mapa
    panel.style.borderRight = `1px solid ${mapStyles.panelBorderColor || '#ccc'}`;
    wrapper.style.border = `1px solid ${mapStyles.mapBorderColor || '#ddd'}`;

    const markerType = mapStyles.markerType || 'default';
    const markerColor = mapStyles.markerColor || '#ff8800';

    fetch(storeLocatorData.storesUrl)
      .then(res => res.json())
      .then(stores => {
        panel.innerHTML = '';
        const bounds = [];

        // Filtro provincias
        const provinciaSet = new Set();
        stores.forEach(store => {
          if (store.province) provinciaSet.add(store.province.trim());
        });
        const provincias = Array.from(provinciaSet).sort();

        const sFiltro = textStyles.provinciaFilter || {};

const filtro = document.createElement('select');
filtro.id = 'provincia-filter';
filtro.style.width = '100%';
filtro.style.marginBottom = '15px';
filtro.style.color = sFiltro.color || 'inherit';
filtro.style.backgroundColor = sFiltro.backgroundColor || 'inherit';
filtro.style.fontSize = sFiltro.fontSize || 'inherit';
filtro.style.padding = sFiltro.padding || 'inherit';
        filtro.innerHTML = `<option value="todas">-- Todas las provincias --</option>` +
          provincias.map(p => `<option value="${p}">${p}</option>`).join('');
        panel.appendChild(filtro);

        stores.forEach((store) => {
          let marker;

          if ((store.markerType || markerType) === 'circleMarker') {
            marker = L.circleMarker([store.lat, store.lng], {
              radius: 10,
              color: markerColor,
              fillColor: markerColor,
              fillOpacity: 0.6
            }).addTo(map)
              .bindPopup(`
  <div class="custom-popup-content">
    <strong>${store.name}</strong><br>
    ${store.address}
  </div>
`);
          } else {
            const icon = createMarkerIcon(store.markerType || markerType, markerColor, store.personalizadoUrl || mapStyles.customIconUrl);
            marker = L.marker([store.lat, store.lng], { icon }).addTo(map)
.bindPopup(`
  <div class="custom-popup-content">
    <strong>${store.name}</strong><br>
    ${store.address || 'N/A'}
    ${store.address && store.address.includes(',') 
        ? '' 
        : (store.city ? `, ${store.city}` : '')
    }<br>
  </div>
`);

          }

          bounds.push([store.lat, store.lng]);

          const entry = document.createElement('div');
          entry.className = 'store-entry';
          entry.setAttribute('data-provincia', store.province || '');

          // Estilos guardados para texto y entrada
          const sName = textStyles.storeName || {};
          const sEntry = textStyles.storeEntry || {};
          const sCob = textStyles.storeCobertura || {};
          const sPhone = textStyles.storePhone || {};
          const sPhone2 = textStyles.storePhone_secundario || {};
          const sEmail = textStyles.storeEmail || {};
          const sAddr = textStyles.storeAddress || {};

          entry.innerHTML = `
            <h3 class="store-name" style="
              font-family: ${sName.fontFamily || 'inherit'};
              font-size: ${sName.fontSize || 'inherit'};
              color: ${sName.color || 'inherit'};
              font-weight: ${sName.fontWeight || 'inherit'};
            ">${store.name}</h3>

            <p class="store-cobertura" style="
              font-family: ${sCob.fontFamily || 'inherit'};
              font-size: ${sCob.fontSize || 'inherit'};
              color: ${sCob.color || 'inherit'};
              font-weight: ${sCob.fontWeight || 'inherit'};
            "><strong>Area de cobertura:</strong> ${store.cobertura || 'N/A'}</p>
<p class="store-phone" style="
  font-family: ${sPhone.fontFamily || 'inherit'};
  font-size: ${sPhone.fontSize || 'inherit'};
  color: ${sPhone.color || 'inherit'};
  font-weight: ${sPhone.fontWeight || 'inherit'};
">
  <strong>Teléfono:</strong>
  ${store.phone
    ? `<a href="tel:${store.phone.replace(/\D/g, '')}" 
          style="color: ${sPhone.color || 'inherit'}; text-decoration: none;">
          ${store.phone}
       </a>`
    : 'N/A'}
  ${store.phone_secundario
    ? ` / <a href="tel:${store.phone_secundario.replace(/\D/g, '')}" 
            style="color: ${sPhone2.color || sPhone.color || 'inherit'}; text-decoration: none;">
            ${store.phone_secundario}
       </a>`
    : ''}
</p>

            <p class="store-email" style="
              font-family: ${sEmail.fontFamily || 'inherit'};
              font-size: ${sEmail.fontSize || 'inherit'};
              color: ${sEmail.color || 'inherit'};
              font-weight: ${sEmail.fontWeight || 'inherit'};
            "><strong>Email:</strong> ${store.email || 'N/A'}</p>

           <p class="store-address" style="
  font-family: ${sAddr.fontFamily || 'inherit'};
  font-size: ${sAddr.fontSize || 'inherit'};
  color: ${sAddr.color || 'inherit'};
  font-weight: ${sAddr.fontWeight || 'inherit'};
">
  <strong>Dirección:</strong> 
  ${store.address || 'N/A'}
  ${store.address && store.address.includes(',') 
      ? '' 
      : (store.city ? `, ${store.city}` : '')
  }
</p>

          `;

          // Aplica estilos storeEntry a cada entrada
          entry.style.border = sEntry.border || '';
          entry.style.padding = sEntry.padding || '';
          entry.style.borderRadius = sEntry.borderRadius || '';
          entry.style.borderTop = sEntry.borderTop || '';
          entry.style.borderBottom = sEntry.borderBottom || '';
          entry.style.borderLeft = sEntry.borderLeft || '';
          entry.style.borderRight = sEntry.borderRight || '';
          entry.style.backgroundColor = sEntry.backgroundColor || '';

          entry.addEventListener('click', () => {
            map.setView([store.lat, store.lng], mapStyles.zoom || 14);
            marker.openPopup();
          });

          panel.appendChild(entry);
        });

        filtro.addEventListener('change', function () {
          const selected = this.value;
          document.querySelectorAll('.store-entry').forEach(entry => {
            const prov = entry.getAttribute('data-provincia') || '';
            entry.style.display = (selected === 'todas' || prov === selected) ? '' : 'none';
          });
        });

        if (bounds.length > 0) {
          map.fitBounds(bounds, { padding: [20, 20] });
        }

        setTimeout(() => {
          map.invalidateSize();
        }, 300);
      }).catch(err => {
        console.error('Error al cargar tiendas:', err);
        panel.innerHTML = '<p>Error al cargar tiendas.</p>';
      });
  }).catch(err => {
    console.error('Error al cargar estilos:', err);
  });

  window.addEventListener('resize', () => {
    map.invalidateSize();
  });
});


