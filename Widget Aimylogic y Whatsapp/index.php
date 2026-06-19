<?php
/*
Plugin Name: Visibilidad Chatbot Aimylogic y Whatsapp Web
Description: Elegí en qué página querés que se vean los widgets. El de Whatsapp web es personalizable, por archivo o URL. 
Version: 2025
Author: Javier I Olivieri
*/

// Agregar la página de configuración al menú de administración
add_action('admin_menu', 'agregar_pagina_configuracion');
function agregar_pagina_configuracion() {
    add_menu_page(
        'Configuración del Widget Personalizado',
        'Visibilidad chatbot Desktop y responsive',
        'manage_options',
        'widget-personalizado-config',
        'mostrar_pagina_configuracion',
        'dashicons-visibility'
    );
}

function guardar_paginas_marcadas($paginas_marcadas) {
    update_option('paginas_marcadas', $paginas_marcadas);
}

// Obtener las páginas marcadas
function obtener_paginas_marcadas() {
    return get_option('paginas_marcadas', []);
}

// Función para mostrar el registro de páginas marcadas
function mostrar_registro_paginas_marcadas() {
    $paginas_marcadas = obtener_paginas_marcadas();
    echo '<h3>Páginas en donde se están mostrando</h3>';
    if (!empty($paginas_marcadas)) {
        echo '<ul>';
        foreach ($paginas_marcadas as $pagina_id) {
            $pagina = get_post($pagina_id);
            if ($pagina) {
                echo '<li>' . esc_html($pagina->post_title) . '</li>';
            }
        }
        echo '</ul>';
    } else {
        echo '<p>No hay páginas marcadas actualmente o están marcadas Todas</p>';
    }
}

function mostrar_pagina_configuracion() {
    // Guardar la configuración si se envió el formulario
    if (isset($_POST['guardar_configuracion'])) {
        // Verificar y actualizar las opciones
        if (isset($_POST['mostrar_en_paginas'])) {
            if (in_array('todas', $_POST['mostrar_en_paginas'])) {
                update_option('widget_personalizado_mostrar_en_paginas', ['todas']);
                guardar_paginas_marcadas([]);
            } else {
                update_option('widget_personalizado_mostrar_en_paginas', $_POST['mostrar_en_paginas']);
                guardar_paginas_marcadas($_POST['mostrar_en_paginas']);
            }
        } else {
            update_option('widget_personalizado_mostrar_en_paginas', []);
            guardar_paginas_marcadas([]);
        }
        
        // Guardar números de teléfono
        update_option('telefono_ar', sanitize_text_field($_POST['telefono_ar']));
        update_option('telefono_uy', sanitize_text_field($_POST['telefono_uy']));
        update_option('telefono_py', sanitize_text_field($_POST['telefono_py']));

        if (isset($_FILES['imagen_widget']) && $_FILES['imagen_widget']['error'] == UPLOAD_ERR_OK) {
            $imagen = $_FILES['imagen_widget'];
            $imagen_url = wp_upload_bits($imagen['name'], null, file_get_contents($imagen['tmp_name']));
            update_option('widget_personalizado_imagen_url', $imagen_url['url']);
        } elseif (isset($_POST['imagen_widget_url'])) {
            update_option('widget_personalizado_imagen_url', esc_url_raw($_POST['imagen_widget_url']));
        }

        if (isset($_POST['bitrix_script_url'])) {
            update_option('bitrix_script_url', esc_url_raw($_POST['bitrix_script_url']));
        }
        echo '<div class="updated"><p>Configuración guardada con éxito.</p></div>';
    }

    // Obtener opciones actuales
    $mostrar_en_paginas = get_option('widget_personalizado_mostrar_en_paginas', []);
    $imagen_url = get_option('widget_personalizado_imagen_url', '');
    $paginas_marcadas = obtener_paginas_marcadas();
    $telefono_ar = get_option('telefono_ar', '541166540463');
    $telefono_uy = get_option('telefono_uy', '59896564339');
    $telefono_py = get_option('telefono_py', '595991288682');
    $bitrix_script_url = get_option('bitrix_script_url', 'https://cdn.bitrix24.es/b25398835/crm/site_button/loader_14_hi7rjr.js');

    // Obtener todas las páginas y filtrar si se realizó una búsqueda
    $query = isset($_GET['query']) ? sanitize_text_field($_GET['query']) : '';
    $args = [
        'post_type' => 'page',
        'posts_per_page' => -1,
        's' => $query,
    ];
    $paginas = get_posts($args);

    // Implementar paginación
    $items_per_page = 10;
    $current_page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
    $total_pages = ceil(count($paginas) / $items_per_page);
    $start_index = ($current_page - 1) * $items_per_page;
    $paginated_pages = array_slice($paginas, $start_index, $items_per_page);
    ?>
    <div class="wrap">
        <h2>Configuración de comunicación con clientes</h2>
        <h4>Este plugin está creado para que el chat Aimylogic se muestre en desktop y Whatsapp web se muestre en responsive</h4>
        <h4 style="font-weight:800"><u>Cómo Usar</u></h4>
        <h4 style="font-weight:800">Paso 1: Seleccionar la/las páginas donde debe estar visible chatbot Aimylogic o en su defecto Whatsapp web, si no están seleccionadas no se mostrarán </h4>
        <h4 style="font-weight:800">Paso 2: Seleccionar imagen por pc o URL del icono que representará la comunicación por Whatsapp web (Responsive). La imagen seleccionada se mostrará debajo </h4>
        <h4 style="font-weight:800; font-family:cursive; margin-top:-10px"><i>Importante!. Dependiendo el país donde se abra la página del sitio, el número de contacto de whatsapp web se irá actualizando automáticamente (ar,uy,py)</i>  </h4>
        <h4 style="font-weight:800">Paso 3: Guardar Configuración</h4>
        <form method="get" action="">
            <input type="hidden" name="page" value="widget-personalizado-config">
            <label for="query">Buscar páginas:</label>
            <input type="text" name="query" value="<?php echo esc_attr($query); ?>">
            <input type="submit" value="Buscar" class="button-secondary">
        </form>
        <form method="post" enctype="multipart/form-data">
            <h3>Mostrar en las siguientes páginas:</h3>
            <input type="checkbox" id="mostrar_todas" name="mostrar_en_paginas[]" value="todas" <?php echo in_array('todas', $mostrar_en_paginas) ? 'checked' : ''; ?> onclick="toggleCheckboxes(this)">
            <label for="mostrar_todas">Todas</label><br>
            <?php foreach ($paginated_pages as $pagina) : ?>
                <input type="checkbox" class="pagina_checkbox" name="mostrar_en_paginas[]" value="<?php echo esc_attr($pagina->ID); ?>" <?php echo in_array('todas', $mostrar_en_paginas) || in_array($pagina->ID, $mostrar_en_paginas) ? 'checked' : ''; ?>>
                <label for="pagina_<?php echo esc_attr($pagina->ID); ?>"><?php echo esc_html($pagina->post_title); ?></label><br>
            <?php endforeach; ?>
            <div class="pagination">
                <?php if ($current_page > 1): ?>
                    <a href="<?php echo add_query_arg(['paged' => $current_page - 1, 'query' => $query]); ?>">&laquo; Anterior</a>
                <?php endif; ?>
                <?php if ($current_page < $total_pages): ?>
                    <a href="<?php echo add_query_arg(['paged' => $current_page + 1, 'query' => $query]); ?>">Siguiente &raquo;</a>
                <?php endif; ?>
            </div>
            <?php
            mostrar_registro_paginas_marcadas();
            ?>
            <h3>Seleccionar imagen del widget que se mostrará en responsive:</h3>
            <input type="file" name="imagen_widget"><br><br>
            <label for="imagen_widget_url">O ingresar URL de la imagen:</label><br><br>
            <input type="url" name="imagen_widget_url" value="<?php echo esc_attr($imagen_url); ?>" size="50"><br><br>
            <?php if ($imagen_url) : ?>
                <img src="<?php echo esc_url($imagen_url); ?>" alt="Imagen del widget" style="max-width: 300px;"><br><br>
            <?php endif; ?>


            <h3>Configuración de números de WhatsApp:</h3>
            <label for="telefono_ar">Teléfono Argentina:</label>
            <input type="text" name="telefono_ar" value="<?php echo esc_attr($telefono_ar); ?>"><br><br>
            <label for="telefono_uy">Teléfono Uruguay:</label>
            <input type="text" style="margin-left:8px" name="telefono_uy" value="<?php echo esc_attr($telefono_uy); ?>"><br><br>
            <label for="telefono_py">Teléfono Paraguay:</label>
            <input type="text" style="margin-left:5px" name="telefono_py" value="<?php echo esc_attr($telefono_py); ?>"><br><br>

            <h3>Configurar Script de Chatbot Aimylogic Bitrix:</h3>
            <h4>Si el script es el siguiente, <code>
(function(w,d,u){
    var s=d.createElement('script');
    s.async=true;
    s.src=u+'?'+(Date.now()/60000|0);
    var h=d.getElementsByTagName('script')[0];
    h.parentNode.insertBefore(s,h);
})(window,document,'https://cdn.bitrix24.es/b25398835/crm/site_button/loader_14_hi7rjr.js');</code>
 lo único que se necesita ingresar es la URL: 
<i>https://cdn.bitrix24.es/b25398835/crm/site_button/loader_14_hi7rjr.js</i></h4>



            <label for="bitrix_script_url">URL del Script de Aimylogic:</label>
            <input type="text" name="bitrix_script_url" value="<?php echo esc_attr($bitrix_script_url); ?>" size="50"><br><br>

            <input type="submit" name="guardar_configuracion" value="Guardar Configuración" class="button-primary">
        </form>




        <h4>Creador: Javier I Olivieri</h4>

    </div>
    <script>
    function toggleCheckboxes(master) {
        var checkboxes = document.querySelectorAll('.pagina_checkbox');
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = master.checked;
        });
    }
    </script>
    <?php
}

add_action('wp_footer', 'mostrar_widget_personalizado');
function mostrar_widget_personalizado() {
    $mostrar_en_paginas = get_option('widget_personalizado_mostrar_en_paginas', []);
    $imagen_url = get_option('widget_personalizado_imagen_url', '');
    $telefono_ar = get_option('telefono_ar', '541166540463');
    $telefono_uy = get_option('telefono_uy', '59896564339');
    $telefono_py = get_option('telefono_py', '595991288682');
    $bitrix_script_url = get_option('bitrix_script_url', 'https://cdn.bitrix24.es/b25398835/crm/site_button/loader_14_hi7rjr.js');

    if (!empty($mostrar_en_paginas) && (in_array('todas', $mostrar_en_paginas) || is_page($mostrar_en_paginas))) {
        if (wp_is_mobile()) {
            ?>
               <div id="widget-personalizado" style="position: fixed; bottom: 20px; right: 20px;">
                <a href="#" id="widget-link">
                    <img src="<?php echo esc_url($imagen_url); ?>" alt="Widget Personalizado">
                </a>
            </div>
            <script>
            function isTablet() {
                return /iPad|Android(?!.*Mobile)/i.test(navigator.userAgent);
            }

            function cargarBitrixYAbrirChat() {
                if (!window.bitrixScriptLoaded) {
                    (function(w,d,u){
                        var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/60000|0);
                        var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
                    })(window,document,'<?php echo esc_url($bitrix_script_url); ?>');
                    window.bitrixScriptLoaded = true;
                }
          
            }
            

            document.addEventListener('DOMContentLoaded', function() {
              if (isTablet()) {
    // Ocultar el botón si es tablet
    var widget = document.getElementById('widget-personalizado');
    if (widget) {
        widget.style.display = 'none';
    }
    
    // Si aún así deseas cargar Bitrix en tablet:
    cargarBitrixYAbrirChat();

    return; // No continuar con el resto del script
}


                // Mostrar el botón solo si NO es tablet
         

                // Configurar el botón de WhatsApp para móviles
                var telefono;
                var hostname = window.location.hostname;

                if (hostname.includes('maxirest.com.ar')) {
                    telefono = "<?php echo $telefono_ar; ?>";
                } else if (hostname.includes('maxirest.com.uy')) {
                    telefono = "<?php echo $telefono_uy; ?>";
                } else if (hostname.includes('maxirest.com.py')) {
                    telefono = "<?php echo $telefono_py; ?>";
                } else {
                    telefono = "<?php echo $telefono_ar; ?>";
                }

                var mensaje = encodeURIComponent("¡Hola! Estoy interesado/a en Maxirest");
                var whatsappWebLink = "https://api.whatsapp.com/send?phone=" + telefono + "&text=" + mensaje;

                setTimeout(function() {
                    var widgetLink = document.getElementById('widget-link');
                    if (widgetLink) {
                        widgetLink.addEventListener('click', function(e) {
                            e.preventDefault();
                            window.open(whatsappWebLink, '_blank');
                        });
                    }
                }, 100);
            });
            </script>
            <?php
        } else {
            // Para escritorio: solo cargar Bitrix
            ?>
            <script>
            if (!window.bitrixScriptLoaded) {
                (function(w,d,u){
                    var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/60000|0);
                    var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
                })(window,document,'<?php echo esc_url($bitrix_script_url); ?>');
                window.bitrixScriptLoaded = true;
            }
            </script>
            <?php
        }
    }
}
