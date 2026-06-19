<?php
if (!defined('ABSPATH')) exit;

/* ======================================================
   MENÚ PRINCIPAL
===================================================== */
function mac_admin_menu() {
    add_menu_page(
        'Mapa Argentina',
        'Mapa Restaurantes',
        'manage_options',
        'mac-admin',
        'mac_admin_page',
        'dashicons-location-alt',
        20
    );
}
add_action('admin_menu', 'mac_admin_menu');

/* ======================================================
   SUBMENÚ DE ESTILOS
===================================================== */
function mac_admin_submenu() {
    add_submenu_page(
        'mac-admin',
        'Estilos del mapa',
        '🎨 Estilos del mapa',
        'manage_options',
        'mac-estilos',
        'mac_estilos_page'
    );
}
add_action('admin_menu', 'mac_admin_submenu');

/* ======================================================
   AJAX: ELIMINAR / IMPORTAR CSV
===================================================== */
add_action('wp_ajax_mac_delete_all', function(){
    if(!current_user_can('manage_options')) wp_die('No permitido');
    delete_option('mac_points');
    wp_send_json_success();
});

add_action('wp_ajax_mac_parse_csv', function(){
    if(!current_user_can('manage_options')) wp_die('No permitido');
    if(!isset($_FILES['file']) || !$_FILES['file']['tmp_name']) wp_send_json_error();

    $file = $_FILES['file']['tmp_name'];
    $imported_points = [];

    // Detectar delimitador
    $firstLine = fgets(fopen($file, 'r'));
    $delimiter = ',';
    if(strpos($firstLine, ';') !== false) $delimiter = ';';
    elseif(strpos($firstLine, "\t") !== false) $delimiter = "\t";

    if(($handle = fopen($file, "r")) !== FALSE){
     while(($line = fgets($handle)) !== FALSE){
    // Forzar UTF-8
    if(!mb_check_encoding($line, 'UTF-8')){
        $line = mb_convert_encoding($line, 'UTF-8', 'auto');
    }

    $data = str_getcsv($line, $delimiter);
    if(count($data) < 2) continue;

    $nombre = trim($data[0]);
    $coords = trim($data[1]);

    $coords = preg_replace('/[^0-9\-,\.]/', '', $coords);

    // Limpiar nombre pero mantener acentos y ñ
$nombre = preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-]/u', '', $nombre);
$nombre = preg_replace('/\s+/', ' ', $nombre);
$nombre = trim($nombre);


    if($nombre && $coords) $imported_points[] = ['nombre'=>$nombre, 'coords'=>$coords];
}

        fclose($handle);
    }

    update_option('mac_points', $imported_points);
    wp_send_json_success($imported_points);
});


/* ======================================================
   PÁGINA PRINCIPAL ADMIN
===================================================== */
function mac_admin_page() {
    $points = get_option('mac_points', []);
    ?>
    <div class="wrap">
        <h1>🗺️ Mapa Restaurantes Argentina</h1>

        <h2>Importar CSV/TSV</h2>
        <form id="mac-csv-form" enctype="multipart/form-data">
            <input type="file" id="mac_csv" accept=".csv,.tsv" />
            <button type="button" class="button button-primary" id="mac-import-csv">Importar CSV/TSV</button>
        </form>

        <button id="mac-delete-all" class="button">Eliminar todos</button>

        <h2>Puntos cargados</h2>
        <div id="mac-points-container">
            <?php foreach($points as $index => $p): ?>
                <p data-index="<?php echo $index; ?>">
                    Nombre: <input type="text" class="mac-nombre" value="<?php echo esc_attr($p['nombre']); ?>" readonly />
                    Coordenadas: <input type="text" class="mac-coords" value="<?php echo esc_attr($p['coords']); ?>" readonly />
                </p>
            <?php endforeach; ?>
        </div>

        <div id="mac-notification" style="
            display:none;
            position:fixed;
            top:50%;
            left:50%;
            transform:translate(-50%, -50%);
            background-color:#323232;
            color:#fff;
            padding:20px 30px;
            font-size:18px;
            font-weight:bold;
            border-radius:10px;
            box-shadow:0 0 20px rgba(0,0,0,0.3);
            z-index:9999;
            text-align:center;
        "></div>
    </div>

    <script>
    jQuery(document).ready(function($){
        function showNotification(msg, type='info'){
            let notif = $('#mac-notification');
            let bg = '#323232';
            if(type === 'success') bg = '#28a745';
            else if(type === 'error') bg = '#dc3545';

            notif.stop(true,true)
                 .css('background-color', bg)
                 .text(msg)
                 .fadeIn(200)
                 .delay(2000)
                 .fadeOut(400);
        }

        // Eliminar todos
        $('#mac-delete-all').click(function(){
            if(!confirm('¿Seguro que quieres eliminar todos los puntos?')) return;
            showNotification('Eliminando...', 'info');
            $.post(ajaxurl,{action:'mac_delete_all'},function(resp){
                if(resp.success){
                    $('#mac-points-container').empty();
                    showNotification('✅ Todos eliminados.', 'success');
                } else showNotification('❌ Error al eliminar.', 'error');
            });
        });

        // Importar CSV/TSV
        $('#mac-import-csv').click(function(e){
            e.preventDefault();
            let file = $('#mac_csv')[0].files[0];
            if(!file){ showNotification('Seleccione un CSV', 'info'); return; }
            let formData = new FormData();
            formData.append('file', file);
            showNotification('Procesando CSV...', 'info');
            $.ajax({
                url: ajaxurl + '?action=mac_parse_csv',
                type: 'POST',
                data: formData,
                contentType: false, processData: false,
                success: function(data){
                    if(!data.success){ showNotification('❌ Error CSV.', 'error'); return; }
                    let points = data.data;
                    $('#mac-points-container').empty();
                    points.forEach((p,index)=>{
                        $('#mac-points-container').append(
                            `<p data-index="${index}">
                                Nombre: <input type="text" class="mac-nombre" value="${p.nombre}" readonly /> 
                                Coordenadas: <input type="text" class="mac-coords" value="${p.coords}" readonly />
                            </p>`
                        );
                    });
                    showNotification('✅ CSV procesado.', 'success');
                }
            });
        });
    });
    </script>
<?php
}


/* ======================================================
   SUBMENÚ: ESTILOS DEL MAPA
====================================================== */
function mac_estilos_page() {

    // Valores por defecto
    $defaults = [
        'pin_color' => '#ff0000',
        'pin_shape' => 'circle',
        'pin_size' => 6,
        'font_size' => 14,
        'font_family' => 'Arial, sans-serif',
        'popup_bg' => '#ffffff',
        'popup_text_color' => '#000000',
        'popup_border_color' => '#cccccc',
        'map_width' => '100%',
        'map_height' => 530,
        'map_border_radius' => 30,
    ];

    // Mezcla los valores guardados con los predeterminados
    $estilos_guardados = get_option('mac_estilos', []);
    $estilos = wp_parse_args($estilos_guardados, $defaults);

    // Guardar estilos si se envía el formulario
    if (isset($_POST['mac_guardar_estilos'])) {
        $estilos = [
            'pin_color' => sanitize_hex_color($_POST['pin_color']),
            'pin_shape' => sanitize_text_field($_POST['pin_shape']),
            'pin_size' => intval($_POST['pin_size']),
            'font_size' => intval($_POST['font_size']),
            'font_family' => sanitize_text_field($_POST['font_family']),
            'popup_bg' => sanitize_hex_color($_POST['popup_bg']),
            'popup_text_color' => sanitize_hex_color($_POST['popup_text_color']),
            'popup_border_color' => sanitize_hex_color($_POST['popup_border_color']),
            'map_width' => sanitize_text_field($_POST['map_width']),
            'map_height' => intval($_POST['map_height']),
            'map_border_radius' => intval($_POST['map_border_radius']),
        ];

        update_option('mac_estilos', $estilos);
        echo '<div class="updated"><p>✅ Estilos guardados correctamente.</p></div>';
    }
    ?>

    <div class="wrap">
        <h1>🎨 Estilos del mapa</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>Color del pin</th>
                    <td><input type="color" name="pin_color" value="<?php echo esc_attr($estilos['pin_color']); ?>"></td>
                </tr>

                <tr>
                    <th>Forma del pin</th>
                    <td>
                  <select name="pin_shape">
    <option value="circle" <?php selected($estilos['pin_shape'],'circle'); ?>>Círculo</option>
    <option value="square" <?php selected($estilos['pin_shape'],'square'); ?>>Cuadrado</option>
    <option value="diamond" <?php selected($estilos['pin_shape'],'diamond'); ?>>Rombo</option>
    <option value="star" <?php selected($estilos['pin_shape'],'star'); ?>>Estrella</option>
   
</select>

                    </td>
                </tr>

                <tr>
                    <th>Tamaño del pin</th>
                    <td><input type="number" name="pin_size" min="2" max="20" value="<?php echo esc_attr($estilos['pin_size']); ?>"> px</td>
                </tr>

                <tr>
                    <th>Tamaño del texto</th>
                    <td><input type="number" name="font_size" value="<?php echo esc_attr($estilos['font_size']); ?>"> px</td>
                </tr>

                <tr>
                    <th>Tipografía</th>
                    <td>
                        <select name="font_family">
                            <option value="Roboto, Arial, sans-serif" <?php selected($estilos['font_family'],'Roboto, Arial, sans-serif'); ?>>Roboto</option>
                            <option value="Arial, sans-serif" <?php selected($estilos['font_family'],'Arial, sans-serif'); ?>>Arial</option>
                            <option value="Verdana, sans-serif" <?php selected($estilos['font_family'],'Verdana, sans-serif'); ?>>Verdana</option>
                            <option value="'Times New Roman', serif" <?php selected($estilos['font_family'],"'Times New Roman', serif"); ?>>Times New Roman</option>
                            <option value="'Courier New', monospace" <?php selected($estilos['font_family'],"'Courier New', monospace"); ?>>Courier New</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th>Fondo del popup</th>
                    <td><input type="color" name="popup_bg" value="<?php echo esc_attr($estilos['popup_bg']); ?>"></td>
                </tr>

                <tr>
                    <th>Color del texto del popup</th>
                    <td><input type="color" name="popup_text_color" value="<?php echo esc_attr($estilos['popup_text_color']); ?>"></td>
                </tr>

                <tr>
                    <th>Borde del popup</th>
                    <td><input type="color" name="popup_border_color" value="<?php echo esc_attr($estilos['popup_border_color']); ?>"></td>
                </tr>

                <!-- NUEVOS CAMPOS PARA EL MAPA -->
                <tr>
                    <th>Ancho del mapa</th>
                    <td><input type="text" name="map_width" value="<?php echo esc_attr($estilos['map_width']); ?>"> (por ejemplo: 100% o 800px)</td>
                </tr>

                <tr>
                    <th>Alto del mapa</th>
                    <td><input type="number" name="map_height" value="<?php echo esc_attr($estilos['map_height']); ?>"> px</td>
                </tr>

                <tr>
                    <th>Radio de borde</th>
                    <td><input type="number" name="map_border_radius" value="<?php echo esc_attr($estilos['map_border_radius']); ?>"> px</td>
                </tr>
            </table>

            <p><input type="submit" name="mac_guardar_estilos" class="button button-primary" value="Guardar estilos"></p>
        </form>
    </div>

    <?php
}
