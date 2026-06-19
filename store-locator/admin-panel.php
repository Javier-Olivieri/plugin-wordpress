<?php
/*
Plugin Name: Store Locator Leaflet
Description: Muestra un mapa con tiendas usando Leaflet y OpenStreetMap.
Version: 1.2
Author: Javier Olivieri
*/

defined('ABSPATH') || exit;

add_action('admin_menu', function () {
    add_menu_page(
        'Mapa de Distribuidores Maxirest',
        'Mapa de Distribuidores',
        'manage_options',
        'store-locator-admin',
        'render_store_locator_admin',
        'dashicons-location-alt',
        30
    );

    add_submenu_page(
        'store-locator-admin',       // Slug del menú principal
        'Estilos del Mapa',          // Título de la página
        'Estilos',                   // Título del submenú
        'manage_options',            // Capacidad requerida
        'store-locator-styles',      // Slug del submenú
        'render_store_locator_styles' // Función que muestra el contenido
    );
});

function render_store_locator_admin() {
    $json_file = plugin_dir_path(__FILE__) . 'stores.json';
    $style_file = plugin_dir_path(__FILE__) . 'map-style.json';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['stores'])) {
            $stores = [];

            foreach ($_POST['stores'] as $store) {
                if (!empty($store['name']) && !empty($store['address']) && !empty($store['lat']) && !empty($store['lng'])) {
                    $stores[] = [
                        'name' => sanitize_text_field($store['name']),
                        'address' => sanitize_text_field($store['address']),
                        'city' => sanitize_text_field($store['city']),
                        'province' => sanitize_text_field($store['province']), // NUEVO
                        'country' => sanitize_text_field($store['country']),
                        'phone' => sanitize_text_field($store['phone']),
                        'phone_secundario' => sanitize_text_field($store['phone_secundario']),
                        'email' => sanitize_email($store['email']),
                        'cobertura' => sanitize_text_field($store['cobertura']),
                        'lat' => floatval($store['lat']),
                        'lng' => floatval($store['lng']),
                    ];
                }
            }

            file_put_contents($json_file, json_encode($stores, JSON_PRETTY_PRINT));
        }

        if (isset($_POST['markerColor'], $_POST['markerType'])) {
            $styles = [
                'markerColor' => sanitize_hex_color($_POST['markerColor']),
                'markerType' => sanitize_text_field($_POST['markerType']),
            ];

            if ($_POST['markerType'] === 'personalizado' && !empty($_POST['customIconUrl'])) {
                $styles['customIconUrl'] = esc_url_raw($_POST['customIconUrl']);
            }

            file_put_contents($style_file, json_encode($styles, JSON_PRETTY_PRINT));

            
        }

        echo '<div class="updated"><p>Datos guardados correctamente.</p></div>';
    }

    

    $stores = file_exists($json_file) ? json_decode(file_get_contents($json_file), true) : [];
    $styles = file_exists($style_file) ? json_decode(file_get_contents($style_file), true) : [
        'markerColor' => '#ff8800',
        'markerType' => 'pin'
    ];

    // Obtener provincias únicas para el filtro
    $provincias = [];
    foreach ($stores as $store) {
        $prov = trim($store['province'] ?? '');
        if ($prov !== '' && !in_array($prov, $provincias)) {
            $provincias[] = $prov;
        }
    }
    sort($provincias);
    ?>

    <div class="wrap">
        <h1>Distribuidores de Maxirest</h1>

<h4>
  El shortcode necesario para mostrar el mapa con los distribuidores es: 
  <code id="shortcode-text">[store_locator_map]</code>
  <button onclick="copyShortcode()">Copiar</button>
</h4>

<script>
function copyShortcode() {
  const text = document.getElementById('shortcode-text').innerText;
  navigator.clipboard.writeText(text).then(() => {
    alert('¡Shortcode copiado!');
  });
}
</script>



        <form method="post" id="store-form">
            <h2>Filtro por Provincia</h2>
            <select id="provinciaFilter" style="margin-bottom: 20px;">
                <option value="todas">-- Todas las provincias --</option>
                <?php foreach ($provincias as $prov): ?>
                    <option value="<?php echo esc_attr($prov); ?>"><?php echo esc_html($prov); ?></option>
                <?php endforeach; ?>
            </select>

            <h2>Distribuidores</h2>
            <div id="stores-container">
                <?php foreach ($stores as $index => $store): ?>
                    <div class="store-block" data-provincia="<?php echo esc_attr(trim($store['province'] ?? '')); ?>">
                        <button type="button" class="remove-store button">❌ Eliminar</button><br>
                        <input type="text" name="stores[<?php echo $index; ?>][name]" value="<?php echo esc_attr($store['name'] ?? ''); ?>" placeholder="Nombre" required />
                        <input type="text" name="stores[<?php echo $index; ?>][address]" value="<?php echo esc_attr($store['address'] ?? ''); ?>" placeholder="Dirección" class="address-input" />
                        <input type="text" name="stores[<?php echo $index; ?>][city]" value="<?php echo esc_attr($store['city'] ?? '') ?>" placeholder="Ciudad" class="city-input" />
                        <input type="text" name="stores[<?php echo $index; ?>][province]" value="<?php echo esc_attr($store['province'] ?? ''); ?>" placeholder="Provincia" class="province-input" />
                        <input type="text" name="stores[<?php echo $index; ?>][country]" value="<?php echo esc_attr($store['country'] ?? '') ?>" placeholder="País" class="country-input" />
                        <input type="text" name="stores[<?php echo $index; ?>][phone]" value="<?php echo esc_attr($store['phone'] ?? ''); ?>" placeholder="Teléfono" />
                        <input type="text" name="stores[<?php echo $index; ?>][phone_secundario]" value="<?php echo esc_attr($store['phone_secundario'] ?? ''); ?>" placeholder="Teléfono secundario" />
                        <input type="email" name="stores[<?php echo $index; ?>][email]" value="<?php echo esc_attr($store['email'] ?? ''); ?>" placeholder="Email" />
                        <input type="text" name="stores[<?php echo $index; ?>][cobertura]" value="<?php echo esc_attr($store['cobertura'] ?? ''); ?>" placeholder="Área de Cobertura" />
                        <input type="text" name="stores[<?php echo $index; ?>][lat]" value="<?php echo esc_attr($store['lat'] ?? ''); ?>" placeholder="Lat" class="lat-input" readonly />
                        <input type="text" name="stores[<?php echo $index; ?>][lng]" value="<?php echo esc_attr($store['lng'] ?? ''); ?>" placeholder="Lng" class="lng-input" readonly />
                        <hr>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="button" id="add-store">Agregar Distribuidor</button>

            <h2>Estilo del Mapa</h2>
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row"><label for="markerColor">Color del marcador</label></th>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <input 
                                type="color" 
                                id="markerColorPicker"
                                value="<?php echo esc_attr($styles['markerColor'] ?? '#ff0000'); ?>"
                                oninput="document.getElementById('markerColorText').value = this.value"
                            />
                            <input 
                                type="text" 
                                id="markerColorText" 
                                name="markerColor" 
                                value="<?php echo esc_attr($styles['markerColor'] ?? '#ff0000'); ?>" 
                                pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$"
                                style="width: 90px;"
                                oninput="document.getElementById('markerColorPicker').value = this.value"
                            />
                        </div>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="markerType">Tipo de marcador</label></th>
                    <td>
                        <select name="markerType" id="markerType">
                            <option value="pin" <?php selected($styles['markerType'] ?? '', 'pin'); ?>>Pin</option>
                            <option value="circle" <?php selected($styles['markerType'] ?? '', 'circle'); ?>>Círculo</option>
                            <option value="personalizado" <?php selected($styles['markerType'] ?? '', 'personalizado'); ?>>Personalizado (imagen)</option>
                        </select>
                    </td>
                </tr>
                <tr id="customIconUrlWrapper" style="display: <?php echo ($styles['markerType'] ?? '') === 'personalizado' ? 'table-row' : 'none'; ?>;">
                    <th scope="row"><label for="customIconUrl">URL del ícono personalizado</label></th>
                    <td>
                        <input 
                            type="url" 
                            name="customIconUrl" 
                            id="customIconUrl"
                            value="<?php echo esc_attr($styles['customIconUrl'] ?? ''); ?>" 
                            placeholder="https://ejemplo.com/icon.png" 
                            style="width: 300px;" 
                        />
                    </td>
                </tr>
                </tbody>
            </table>

            <p><button type="submit" class="button button-primary">Guardar todo</button></p>
        </form>
    </div>
<h2>Creador: Javier I Olivieri</h2>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const markerTypeSelect = document.getElementById('markerType');
        const customIconUrlWrapper = document.getElementById('customIconUrlWrapper');

        markerTypeSelect.addEventListener('change', function () {
            customIconUrlWrapper.style.display = this.value === 'personalizado' ? 'table-row' : 'none';
        });

        let storeIndex = <?php echo count($stores); ?>;
        const container = document.getElementById('stores-container');

        // Agregar tienda
        document.getElementById('add-store').addEventListener('click', function () {
            const div = document.createElement('div');
            div.className = 'store-block';
            div.setAttribute('data-provincia', '');
            div.innerHTML = `
                <button type="button" class="remove-store button">❌ Eliminar</button><br>
                <input type="text" name="stores[${storeIndex}][name]" placeholder="Nombre" required />
                <input type="text" name="stores[${storeIndex}][address]" placeholder="Dirección" class="address-input" />
                <input type="text" name="stores[${storeIndex}][city]" placeholder="Ciudad" class="city-input" />
                <input type="text" name="stores[${storeIndex}][province]" placeholder="Provincia" class="province-input" />
                <input type="text" name="stores[${storeIndex}][country]" placeholder="País" class="country-input" />
                <input type="text" name="stores[${storeIndex}][phone]" placeholder="Teléfono" />
                <input type="text" name="stores[${storeIndex}][phone_secundario]" placeholder="Teléfono secundario" />
                <input type="email" name="stores[${storeIndex}][email]" placeholder="Email" />
                <input type="text" name="stores[${storeIndex}][cobertura]" placeholder="Área de Cobertura" />
                <input type="text" name="stores[${storeIndex}][lat]" placeholder="Lat" class="lat-input" readonly />
                <input type="text" name="stores[${storeIndex}][lng]" placeholder="Lng" class="lng-input" readonly />
                <hr>
            `;
            container.appendChild(div);
            storeIndex++;
        });

        // Eliminar tienda con confirmación
        container.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-store')) {
                const block = e.target.closest('.store-block');
                if (confirm('¿Estás seguro de que querés eliminar esta tienda?')) {
                    block.remove();
                }
            }
        });

        // Actualizar atributo data-provincia al cambiar la provincia
        container.addEventListener('input', function (e) {
            if (e.target.classList.contains('province-input')) {
                const block = e.target.closest('.store-block');
                block.setAttribute('data-provincia', e.target.value.trim());
            }
        });

        // También actualizamos lat/lng cuando cambia dirección, ciudad, país (mantengo tu código original)
        document.addEventListener('input', async function (e) {
            if (
                e.target.classList.contains('address-input') || 
                e.target.classList.contains('city-input') || 
                e.target.classList.contains('country-input')
            ) {
                const block = e.target.closest('.store-block');
                const address = block.querySelector('.address-input').value;
                const city = block.querySelector('.city-input').value;
                const country = block.querySelector('.country-input').value;
                const latInput = block.querySelector('.lat-input');
                const lngInput = block.querySelector('.lng-input');

                const fullAddress = `${address}, ${city}, ${country}`;
                if (fullAddress.length > 10) {
                    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(fullAddress)}`;
                    const response = await fetch(url);
                    const data = await response.json();

                    if (data[0]) {
                        latInput.value = data[0].lat;
                        lngInput.value = data[0].lon;
                    }
                }
            }
        });

        // Filtro por provincia
        const provinciaFilter = document.getElementById('provinciaFilter');
        provinciaFilter.addEventListener('change', function () {
            const selectedProvincia = this.value;
            document.querySelectorAll('.store-block').forEach(block => {
                const prov = block.getAttribute('data-provincia');
                if (selectedProvincia === 'todas' || prov === selectedProvincia) {
                    block.style.display = '';
                } else {
                    block.style.display = 'none';
                }
            });
        });
    });
    </script>

    <style>
        .store-block {
            margin-bottom: 10px;
            padding: 10px;
            background: #f9f9f9;
            border-left: 4px solid #0073aa;
            position: relative;
        }

        .store-block input {
            margin: 4px;
            width: calc(20% - 10px);
        }

        .remove-store {
            background-color: #dc3232;
            color: white;
            border: none;
            padding: 2px 10px;
            margin-bottom: 5px;
            float: right;
            cursor: pointer;
        }

        .remove-store:hover {
            background-color: #b91c1c;
        }

        #provinciaFilter {
            width: 250px;
            padding: 5px;
            font-size: 16px;
        }
    </style>

    <?php
}

function render_store_locator_styles() {
    $style_file = plugin_dir_path(__FILE__) . 'map-text-styles.json';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $styles = [

             'provinciaFilter' => [
             'color' => sanitize_hex_color($_POST['provinciaFilter_color'] ?? ''),
             'backgroundColor' => sanitize_hex_color($_POST['provinciaFilter_backgroundColor'] ?? ''),
             'fontSize' => sanitize_text_field($_POST['provinciaFilter_fontSize'] ?? ''),
             'padding' => sanitize_text_field($_POST['provinciaFilter_padding'] ?? ''),
            ],
            'storePopup' => [
            'backgroundColor' => sanitize_hex_color($_POST['storePopup_backgroundColor'] ?? '#ffffff'),
            'color' => sanitize_hex_color($_POST['storePopup_color'] ?? '#000000'),
            'fontFamily' => sanitize_text_field($_POST['storePopup_fontFamily'] ?? ''),
            'fontSize' => sanitize_text_field($_POST['storePopup_fontSize'] ?? ''),
            'padding' => sanitize_text_field($_POST['storePopup_padding'] ?? '8px'),
            'borderRadius' => sanitize_text_field($_POST['storePopup_borderRadius'] ?? '5px'),
            'closeButtonColor' => sanitize_hex_color($_POST['storePopup_closeButtonColor'] ?? ''),
           'closeButtonBg' => sanitize_text_field($_POST['storePopup_closeButtonBg'] ?? 'transparent'),
           'closeButtonHoverColor' => sanitize_hex_color($_POST['storePopup_closeButtonHoverColor'] ?? ''),
           'closeButtonHoverBg' => sanitize_hex_color($_POST['storePopup_closeButtonHoverBg'] ?? ''),
            ],
            'storeEntry' => [
                'border' => sanitize_text_field($_POST['storeEntry_border'] ?? ''),
                'padding' => sanitize_text_field($_POST['storeEntry_padding'] ?? ''),
                'borderRadius' => sanitize_text_field($_POST['storeEntry_borderRadius'] ?? ''),
                'borderTop' => sanitize_text_field($_POST['storeEntry_borderTop'] ?? ''),
                'borderRight' => sanitize_text_field($_POST['storeEntry_borderRight'] ?? ''),
                'borderBottom' => sanitize_text_field($_POST['storeEntry_borderBottom'] ?? ''),
                'borderLeft' => sanitize_text_field($_POST['storeEntry_borderLeft'] ?? ''),
                'backgroundColor' => sanitize_hex_color($_POST['storeEntry_backgroundColor'] ?? '#ffffff'),
            ],
            'storeName' => [
                'fontFamily' => sanitize_text_field($_POST['storeName_fontFamily'] ?? ''),
                'fontSize' => sanitize_text_field($_POST['storeName_fontSize'] ?? ''),
                'color' => sanitize_hex_color($_POST['storeName_color'] ?? ''),
                'fontWeight' => sanitize_text_field($_POST['storeName_fontWeight'] ?? ''),
            ],
            'storeCobertura' => [
                'fontFamily' => sanitize_text_field($_POST['storeCobertura_fontFamily'] ?? ''),
                'fontSize' => sanitize_text_field($_POST['storeCobertura_fontSize'] ?? ''),
                'color' => sanitize_hex_color($_POST['storeCobertura_color'] ?? ''),
                'fontWeight' => sanitize_text_field($_POST['storeCobertura_fontWeight'] ?? ''),
            ],
            'storePhone' => [
                'fontFamily' => sanitize_text_field($_POST['storePhone_fontFamily'] ?? ''),
                'fontSize' => sanitize_text_field($_POST['storePhone_fontSize'] ?? ''),
                'color' => sanitize_hex_color($_POST['storePhone_color'] ?? ''),
                'fontWeight' => sanitize_text_field($_POST['storePhone_fontWeight'] ?? ''),
            ],
            'storePhone2' => [
                'fontFamily' => sanitize_text_field($_POST['storePhone2_fontFamily'] ?? ''),
                'fontSize' => sanitize_text_field($_POST['storePhone2_fontSize'] ?? ''),
                'color' => sanitize_hex_color($_POST['storePhone2_color'] ?? ''),
                'fontWeight' => sanitize_text_field($_POST['storePhone2_fontWeight'] ?? ''),
            ],
            'storeEmail' => [
                'fontFamily' => sanitize_text_field($_POST['storeEmail_fontFamily'] ?? ''),
                'fontSize' => sanitize_text_field($_POST['storeEmail_fontSize'] ?? ''),
                'color' => sanitize_hex_color($_POST['storeEmail_color'] ?? ''),
                'fontWeight' => sanitize_text_field($_POST['storeEmail_fontWeight'] ?? ''),
            ],
            'storeAddress' => [
                'fontFamily' => sanitize_text_field($_POST['storeAddress_fontFamily'] ?? ''),
                'fontSize' => sanitize_text_field($_POST['storeAddress_fontSize'] ?? ''),
                'color' => sanitize_hex_color($_POST['storeAddress_color'] ?? ''),
                'fontWeight' => sanitize_text_field($_POST['storeAddress_fontWeight'] ?? ''),
            ],
            'storeInfoPanel' => [
                'backgroundColor' => sanitize_hex_color($_POST['storeInfoPanel_backgroundColor'] ?? '#ffffff'),
                'border' => sanitize_text_field($_POST['storeInfoPanel_border'] ?? ''),
                'padding' => sanitize_text_field($_POST['storeInfoPanel_padding'] ?? ''),
                'borderRadius' => sanitize_text_field($_POST['storeInfoPanel_borderRadius'] ?? ''),
                'width' => sanitize_text_field($_POST['storeInfoPanel_width'] ?? ''),
            ],

             'storeLocatorWrapper' => [
                'width' => sanitize_text_field($_POST['storeLocatorWrapper_width'] ?? ''),
                'height' => sanitize_text_field($_POST['storeLocatorWrapper_height'] ?? ''),
            ],
            'storeMap' => [
                'width' => sanitize_text_field($_POST['storeMap_width'] ?? ''),
                'height' => sanitize_text_field($_POST['storeMap_height'] ?? ''),
            ],
        ];

        file_put_contents($style_file, json_encode($styles, JSON_PRETTY_PRINT));
      
     // Generar CSS dinámico para map.css
        $css = '';

        if (!empty($styles['storeLocatorWrapper'])) {
            $css .= ".store-locator-wrapper {\n";
            if (!empty($styles['storeLocatorWrapper']['width'])) {
                $css .= "  width: {$styles['storeLocatorWrapper']['width']};\n";
            }
            if (!empty($styles['storeLocatorWrapper']['height'])) {
                $css .= "  height: {$styles['storeLocatorWrapper']['height']};\n";
            }
            $css .= "}\n\n";
        }

        if (!empty($styles['storeInfoPanel'])) {
            $css .= "#store-info-panel {\n";
            if (!empty($styles['storeInfoPanel']['width'])) {
                $css .= "  width: {$styles['storeInfoPanel']['width']};\n";
            }
            $css .= "}\n\n";
        }

        if (!empty($styles['storeMap'])) {
            $css .= "#store-map {\n";
            if (!empty($styles['storeMap']['width'])) {
                $css .= "  width: {$styles['storeMap']['width']};\n";
            }
            if (!empty($styles['storeMap']['height'])) {
                $css .= "  height: {$styles['storeMap']['height']};\n";
            }
            $css .= "}\n";
        }

        // Guardar el CSS en el archivo custom.css
        file_put_contents(plugin_dir_path(__FILE__) . 'css/custom.css', $css);

        echo '<div class="updated"><p>Estilos guardados correctamente.</p></div>';
    }


    // Leer estilos guardados
 // Leer estilos guardados desde JSON
$styles_json = file_exists($style_file) ? file_get_contents($style_file) : '';
$styles = $styles_json ? json_decode($styles_json, true) : [];

// Si no se pudo leer JSON o hubo error de parseo, usar opciones de WP
if (json_last_error() !== JSON_ERROR_NONE || empty($styles)) {
    $styles = get_option('store_locator_styles', $defaultStyles);
}

    // Valores por defecto para asegurar que las claves existen
    $defaultStyles = [
        'provinciaFilter' => ['color' => '', 'backgroundColor' => '', 'fontSize' => '', 'padding' => ''],
        'storePopup' => ['backgroundColor' => '', 'color' => '', 'fontFamily' => '', 'fontSize' => '', 'padding' => '', 'borderRadius' => ''],
        'storeEntry' => ['border' => '', 'borderTop' => '', 'borderRight' => '', 'borderBottom' => '', 'borderLeft' => '', 'backgroundColor' => '', 'padding' => '', 'borderRadius' => ''],
        'storeName' => ['fontFamily' => '', 'fontSize' => '', 'color' => '', 'fontWeight' => ''],
        'storeCobertura' => ['fontFamily' => '', 'fontSize' => '', 'color' => '', 'fontWeight' => ''],
        'storePhone' => ['fontFamily' => '', 'fontSize' => '', 'color' => '', 'fontWeight' => ''],
        'storePhone2' => ['fontFamily' => '', 'fontSize' => '', 'color' => '', 'fontWeight' => ''],
        'storeEmail' => ['fontFamily' => '', 'fontSize' => '', 'color' => '', 'fontWeight' => ''],
        'storeAddress' => ['fontFamily' => '', 'fontSize' => '', 'color' => '', 'fontWeight' => ''],
        'storeInfoPanel' => ['backgroundColor' => '', 'border' => '', 'padding' => '', 'borderRadius' => '', 'width' => ''],
             'storeLocatorWrapper' => ['width' => '', 'height' => ''],
        'storeMap' => ['width' => '', 'height' => ''],
    ];

    $styles = array_replace_recursive($defaultStyles, $styles);

    $panelBackground = $styles['storeInfoPanel']['backgroundColor'];


$infoPanelStyles = $styles['storeInfoPanel'] ?? [];
$locatorWrapper = $styles['storeLocatorWrapper'] ?? [];
$storeMap = $styles['storeMap'] ?? [];
?>
<div class="wrap">
    <h1>Estilo para la lista de Distribuidores</h1>
    <form method="post">
        <table class="form-table" role="presentation">
            <tbody>
                <?php
                $fields = [
                    'storeName' => 'Nombre de Distribuidor',
                    'storeCobertura' => 'Área de Cobertura',
                    'storePhone' => 'Teléfono',
             
                    'storeEmail' => 'Email',
                    'storeAddress' => 'Dirección',
                ];

                foreach ($fields as $key => $label):
                    $current = $styles[$key];
                ?>
                <tr>
                    <th scope="row"><?php echo esc_html($label); ?></th>
                    <td>
                        <label>
                            Tipografía (font-family):<br>
                            <input type="text" name="<?php echo $key; ?>_fontFamily" value="<?php echo esc_attr($current['fontFamily']); ?>" placeholder="Ej: Arial, sans-serif" style="width: 300px;" />
                        </label>
                        <br>
                        <label>
                            Tamaño de fuente (font-size):<br>
                            <input type="text" name="<?php echo $key; ?>_fontSize" value="<?php echo esc_attr($current['fontSize']); ?>" placeholder="Ej: 16px, 1.2em" style="width: 100px;" />
                        </label>
                        <br>
                        <label>
                            Peso de fuente (font-weight):<br>
                            <input type="text" name="<?php echo $key; ?>_fontWeight" value="<?php echo esc_attr($current['fontWeight']); ?>" placeholder="Ej: 400, bold" style="width: 100px;" />
                        </label>
                        <br>
                        <label>
                            Color:<br>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input 
                                    type="color" 
                                    id="<?php echo $key; ?>_color_picker"
                                    value="<?php echo esc_attr($current['color']); ?>"
                                    oninput="document.getElementById('<?php echo $key; ?>_color_text').value = this.value"
                                />
                                <input 
                                    type="text" 
                                    id="<?php echo $key; ?>_color_text" 
                                    name="<?php echo $key; ?>_color" 
                                    value="<?php echo esc_attr($current['color']); ?>" 
                                    pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$"
                                    style="width: 90px;"
                                    oninput="document.getElementById('<?php echo $key; ?>_color_picker').value = this.value"
                                />
                            </div>
                        </label>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php 
                $entryStyles = $styles['storeEntry'];
                ?>
                <tr style="border-top:2px solid black;">
                    <th scope="row">Campo datos Distribuidores </th>
                    <td>
                        <label>
                            Borde (border):<br>
                            <input type="text" name="storeEntry_border" value="<?php echo esc_attr($entryStyles['border']); ?>" placeholder="Ej: 1px solid #ccc" style="width: 300px;" />
                        </label>
                        <br>
                        <label>
                            Borde de arriba (border):<br>
                            <input type="text" name="storeEntry_borderTop" value="<?php echo esc_attr($entryStyles['borderTop']); ?>" placeholder="Ej: 1px solid #ccc" style="width: 300px;" />
                        </label>
                        <br>
                        <label>
                            Borde de la derecha (border):<br>
                            <input type="text" name="storeEntry_borderRight" value="<?php echo esc_attr($entryStyles['borderRight']); ?>" placeholder="Ej: 1px solid #ccc" style="width: 300px;" />
                        </label>
                        <br>
                        <label>
                            Borde de abajo (border):<br>
                            <input type="text" name="storeEntry_borderBottom" value="<?php echo esc_attr($entryStyles['borderBottom']); ?>" placeholder="Ej: 1px solid #ccc" style="width: 300px;" />
                        </label>
                        <br>
                        <label>
                            Borde de la izquierda (border):<br>
                            <input type="text" name="storeEntry_borderLeft" value="<?php echo esc_attr($entryStyles['borderLeft']); ?>" placeholder="Ej: 1px solid #ccc" style="width: 300px;" />
                        </label>
                        <br>
                      
                       <label> 
    Color de fondo (background):<br>
    <div style="display: flex; align-items: center; gap: 10px;">
        <input 
            type="color" 
            id="storeEntry_backgroundColor_picker"
            value="<?php echo esc_attr($entryStyles['backgroundColor']); ?>"
            oninput="document.getElementById('storeEntry_backgroundColor_text').value = this.value"
        />
        <input 
            type="text" 
            id="storeEntry_backgroundColor_text" 
            name="storeEntry_backgroundColor" 
            value="<?php echo esc_attr($entryStyles['backgroundColor']); ?>" 
            pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$"
            style="width: 90px;"
            oninput="document.getElementById('storeEntry_backgroundColor_picker').value = this.value"
        />
    </div>
</label>

                      
                        <label>
                            Padding:<br>
                            <input type="text" name="storeEntry_padding" value="<?php echo esc_attr($entryStyles['padding']); ?>" placeholder="Ej: 10px" style="width: 100px;" />
                        </label>
                        <br>
                        <label>
                            Border Radius:<br>
                            <input type="text" name="storeEntry_borderRadius" value="<?php echo esc_attr($entryStyles['borderRadius']); ?>" placeholder="Ej: 8px" style="width: 100px;" />
                        </label>
                    </td>
                </tr>
        
                <tr style="border-top:2px solid black;">
                   <th scope="row"> Panel donde estan los distribuidores</th>
                </tr>
                <tr>
                   <th scope="row"> color de fondo</th>
    
                    <td> 
                        
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <input 
                                type="color" 
                                id="storeInfoPanel_backgroundColor_picker"
                                value="<?php echo esc_attr($panelBackground); ?>"
                                oninput="document.getElementById('storeInfoPanel_backgroundColor_text').value = this.value"
                            />
                            <input 
                                type="text" 
                                id="storeInfoPanel_backgroundColor_text" 
                                name="storeInfoPanel_backgroundColor" 
                                value="<?php echo esc_attr($panelBackground); ?>" 
                                pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$"
                                style="width: 90px;"
                                oninput="document.getElementById('storeInfoPanel_backgroundColor_picker').value = this.value"
                            />
                        </div>
                    </td>
                </tr>

                <?php 
                $infoPanelStyles = $styles['storeInfoPanel'];
                ?>

                <tr>
                    <th scope="row">Borde (border)</th>
                    <td>
                        <input type="text" name="storeInfoPanel_border" value="<?php echo esc_attr($infoPanelStyles['border']); ?>" placeholder="Ej: 1px solid #ccc" style="width: 300px;" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Padding</th>
                    <td>
                        <input type="text" name="storeInfoPanel_padding" value="<?php echo esc_attr($infoPanelStyles['padding']); ?>" placeholder="Ej: 10px" style="width: 100px;" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Border Radius</th>
                    <td>
                        <input type="text" name="storeInfoPanel_borderRadius" value="<?php echo esc_attr($infoPanelStyles['borderRadius']); ?>" placeholder="Ej: 8px" style="width: 100px;" />
                    </td>
                </tr>
               <tr>

    <th scope="row">Width</th>
    <td>
        <?php $infoPanelStyles = $styles['storeInfoPanel'] ?? []; ?>

        <input type="text" name="storeInfoPanel_width" value="<?php echo esc_attr($infoPanelStyles['width']); ?>" placeholder="Ej: 600px, 100%" style="width: 100px;" />
    </td>
</tr>

                   <?php 
                $locatorWrapper = $styles['storeLocatorWrapper'];
                ?>
                <tr style="border-top:2px solid black;">
                    <th scope="row">Contenedor Principal </th>
                    <td>
                        <label>
                            Width:<br>
                            <input type="text" name="storeLocatorWrapper_width" value="<?php echo esc_attr($locatorWrapper['width']); ?>" placeholder="Ej: 1000px, 80%" style="width: 100px;" />
                        </label>
                        <br>
                        <label>
                            Height:<br>
                            <input type="text" name="storeLocatorWrapper_height" value="<?php echo esc_attr($locatorWrapper['height']); ?>" placeholder="Ej: 400px, 60vh" style="width: 100px;" />
                        </label>
                    </td>
                </tr>

                <?php 
                $storeMap = $styles['storeMap'];
                ?>
                <tr style="border-top:2px solid black;">
                    <th scope="row">Mapa </th>
                    <td>
                        <label>
                            Width:<br>
                            <input type="text" name="storeMap_width" value="<?php echo esc_attr($storeMap['width']); ?>" placeholder="Ej: 100%, 600px" style="width: 100px;" />
                        </label>
                        <br>
                        <label>
                            Height:<br>
                            <input type="text" name="storeMap_height" value="<?php echo esc_attr($storeMap['height']); ?>" placeholder="Ej: 400px, 60vh" style="width: 100px;" />
                        </label>
                    </td>
                </tr>

<tr style="border-top:2px solid black;">
    <th scope="row">Popup de los marcadores</th>
    <td>
        <label>Color de fondo:<br>
            <div style="display: flex; align-items: center; gap: 10px;">
                <input 
                    type="color" 
                    id="storePopup_backgroundColor_picker" 
                    value="<?php echo esc_attr($styles['storePopup']['backgroundColor']); ?>" 
                    oninput="document.getElementById('storePopup_backgroundColor_text').value = this.value"
                />
                <input 
                    type="text" 
                    id="storePopup_backgroundColor_text" 
                    name="storePopup_backgroundColor" 
                    value="<?php echo esc_attr($styles['storePopup']['backgroundColor']); ?>" 
                    oninput="document.getElementById('storePopup_backgroundColor_picker').value = this.value"
                />
            </div>
        </label><br>

        <label>Color de texto:<br>
            <div style="display: flex; align-items: center; gap: 10px;">
                <input 
                    type="color" 
                    id="storePopup_color_picker" 
                    value="<?php echo esc_attr($styles['storePopup']['color']); ?>" 
                    oninput="document.getElementById('storePopup_color_text').value = this.value"
                />
                <input 
                    type="text" 
                    id="storePopup_color_text" 
                    name="storePopup_color" 
                    value="<?php echo esc_attr($styles['storePopup']['color']); ?>" 
                    oninput="document.getElementById('storePopup_color_picker').value = this.value"
                />
            </div>
        </label><br>

        <label>Tipografía (font-family):<br>
            <input type="text" name="storePopup_fontFamily" value="<?php echo esc_attr($styles['storePopup']['fontFamily']); ?>">
        </label><br>

        <label>Tamaño de fuente:<br>
            <input type="text" name="storePopup_fontSize" value="<?php echo esc_attr($styles['storePopup']['fontSize']); ?>">
        </label><br>

        <label>Padding:<br>
            <input type="text" name="storePopup_padding" value="<?php echo esc_attr($styles['storePopup']['padding']); ?>">
        </label><br>

        <label>Border Radius:<br>
            <input type="text" name="storePopup_borderRadius" value="<?php echo esc_attr($styles['storePopup']['borderRadius']); ?>">
        </label>
    </td>
</tr>

<tr style="border-top:2px solid black;">
  <th scope="row">Color botón cerrar popup (cruz)</th>
  <td>
    <input type="color" 
           name="storePopup_closeButtonColor" 
           value="<?php echo esc_attr($styles['storePopup']['closeButtonColor'] ?? '#333333'); ?>" />
    <input type="text" 
           name="storePopup_closeButtonColor" 
           value="<?php echo esc_attr($styles['storePopup']['closeButtonColor'] ?? '#333333'); ?>" 
           style="width: 90px;"
           pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$" />
  </td>
</tr>
<tr >
  <th scope="row">Fondo botón cerrar popup</th>
  <td>
    <input type="color" 
           name="storePopup_closeButtonBg" 
           value="<?php echo esc_attr($styles['storePopup']['closeButtonBg'] ?? 'transparent'); ?>" />
    <input type="text" 
           name="storePopup_closeButtonBg" 
           value="<?php echo esc_attr($styles['storePopup']['closeButtonBg'] ?? 'transparent'); ?>" 
           style="width: 90px;"
           pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$|^transparent$" />
  </td>
</tr>
<tr>
  <th scope="row">Color botón cerrar hover</th>
  <td>
    <input type="color" 
           name="storePopup_closeButtonHoverColor" 
           value="<?php echo esc_attr($styles['storePopup']['closeButtonHoverColor'] ?? '#000000'); ?>" />
    <input type="text" 
           name="storePopup_closeButtonHoverColor" 
           value="<?php echo esc_attr($styles['storePopup']['closeButtonHoverColor'] ?? '#000000'); ?>" 
           style="width: 90px;"
           pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$" />
  </td>
</tr>
<tr>
  <th scope="row">Fondo botón cerrar hover</th>
  <td>
    <input type="color" 
           name="storePopup_closeButtonHoverBg" 
           value="<?php echo esc_attr($styles['storePopup']['closeButtonHoverBg'] ?? '#eeeeee'); ?>" />
    <input type="text" 
           name="storePopup_closeButtonHoverBg" 
           value="<?php echo esc_attr($styles['storePopup']['closeButtonHoverBg'] ?? '#eeeeee'); ?>" 
           style="width: 90px;"
           pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$" />
  </td>
</tr>

<?php 
$filterStyles = $styles['provinciaFilter'] ?? ['color' => '', 'backgroundColor' => '', 'fontSize' => '', 'padding' => ''];
?>
<tr style="border-top:2px solid black;">
  <th scope="row">Filtro de Provincias (select)</th>
  <td>
    <label>Color de texto:<br>
      <input type="color" 
             id="provinciaFilter_color_picker"
             value="<?php echo esc_attr($filterStyles['color']); ?>"
             oninput="document.getElementById('provinciaFilter_color_text').value = this.value"
      />
      <input type="text"
             id="provinciaFilter_color_text"
             name="provinciaFilter_color"
             value="<?php echo esc_attr($filterStyles['color']); ?>"
             pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$"
             style="width: 90px;"
             oninput="document.getElementById('provinciaFilter_color_picker').value = this.value"
      />
    </label>
    <br>
    <label>Color de fondo:<br>
      <input type="color" 
             id="provinciaFilter_backgroundColor_picker"
             value="<?php echo esc_attr($filterStyles['backgroundColor']); ?>"
             oninput="document.getElementById('provinciaFilter_backgroundColor_text').value = this.value"
      />
      <input type="text"
             id="provinciaFilter_backgroundColor_text"
             name="provinciaFilter_backgroundColor"
             value="<?php echo esc_attr($filterStyles['backgroundColor']); ?>"
             pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$"
             style="width: 90px;"
             oninput="document.getElementById('provinciaFilter_backgroundColor_picker').value = this.value"
      />
    </label>
    <br>
    <label>Tamaño de fuente:<br>
      <input type="text" name="provinciaFilter_fontSize" value="<?php echo esc_attr($filterStyles['fontSize']); ?>" placeholder="Ej: 16px" style="width: 100px;" />
    </label>
    <br>
    <label>Padding:<br>
      <input type="text" name="provinciaFilter_padding" value="<?php echo esc_attr($filterStyles['padding']); ?>" placeholder="Ej: 5px" style="width: 100px;" />
    </label>
  </td>
</tr>


            </tbody>
        </table>
        <p><button type="submit" class="button button-primary">Guardar estilos</button></p>
    </form>
</div>
<h2>Creador: Javier I Olivieri</h2>
<?php
}

