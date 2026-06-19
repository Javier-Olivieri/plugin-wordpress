<?php
/*
Plugin Name: Visibilidad Chatbot Live Helper y Whatsapp Web
Description: Live Helper Chat para escritorio y WhatsApp Web para móvil/responsive (solo Argentina editable).
Version: 2025
Author: Javier I Olivieri
*/

// =======================
// MENÚ DE CONFIGURACIÓN
// =======================
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

// Guardar páginas marcadas
function guardar_paginas_marcadas($paginas_marcadas) {
    update_option('paginas_marcadas', $paginas_marcadas);
}

// Obtener páginas marcadas
function obtener_paginas_marcadas() {
    return get_option('paginas_marcadas', []);
}

// Mostrar registro de páginas marcadas
function mostrar_registro_paginas_marcadas() {
    $paginas_marcadas = obtener_paginas_marcadas();
    echo '<h3>Páginas donde se está mostrando</h3>';
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

// =======================
// PÁGINA DE CONFIGURACIÓN
// =======================
function mostrar_pagina_configuracion() {
    if (isset($_POST['guardar_configuracion'])) {
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

        // Guardar teléfono Argentina
        update_option('telefono_ar', sanitize_text_field($_POST['telefono_ar']));

        // Guardar imagen de widget
        if (isset($_FILES['imagen_widget']) && $_FILES['imagen_widget']['error'] == UPLOAD_ERR_OK) {
            $imagen = $_FILES['imagen_widget'];
            $imagen_url = wp_upload_bits($imagen['name'], null, file_get_contents($imagen['tmp_name']));
            update_option('widget_personalizado_imagen_url', $imagen_url['url']);
        } elseif (isset($_POST['imagen_widget_url'])) {
            update_option('widget_personalizado_imagen_url', esc_url_raw($_POST['imagen_widget_url']));
        }

        echo '<div class="updated"><p>Configuración guardada con éxito.</p></div>';
    }

    $mostrar_en_paginas = get_option('widget_personalizado_mostrar_en_paginas', []);
    $imagen_url = get_option('widget_personalizado_imagen_url', '');
    $paginas_marcadas = obtener_paginas_marcadas();
    $telefono_ar = get_option('telefono_ar', '');

    // Obtener todas las páginas, sin paginación
    $query = isset($_GET['query']) ? sanitize_text_field($_GET['query']) : '';
    $args = ['post_type' => 'page', 'posts_per_page' => -1, 's' => $query];
    $paginas = get_posts($args);
    ?>
    <div class="wrap">
        <h2>Configuración de comunicación con clientes</h2>
        <h4>Este plugin muestra Live Helper Chat en desktop y WhatsApp web en responsive</h4>
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
            <?php foreach ($paginas as $pagina) : ?>
                <input type="checkbox" class="pagina_checkbox" name="mostrar_en_paginas[]" value="<?php echo esc_attr($pagina->ID); ?>" <?php echo in_array('todas', $mostrar_en_paginas) || in_array($pagina->ID, $mostrar_en_paginas) ? 'checked' : ''; ?>>
                <label><?php echo esc_html($pagina->post_title); ?></label><br>
            <?php endforeach; ?>

            <?php mostrar_registro_paginas_marcadas(); ?>

            <h3>Seleccionar imagen del widget para responsive:</h3>
            <input type="file" name="imagen_widget"><br><br>
            <label for="imagen_widget_url">O ingresar URL de la imagen:</label><br><br>
            <input type="url" name="imagen_widget_url" value="<?php echo esc_attr($imagen_url); ?>" size="50"><br><br>
            <?php if ($imagen_url) : ?>
                <img src="<?php echo esc_url($imagen_url); ?>" alt="Imagen del widget" style="max-width: 300px;"><br><br>
            <?php endif; ?>

            <h3>Teléfono WhatsApp Argentina:</h3>
            <input type="text" name="telefono_ar" value="<?php echo esc_attr($telefono_ar); ?>"><br><br>

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

// =======================
// MOSTRAR WIDGET EN FRONTEND
// =======================
add_action('wp_footer', 'mostrar_widget_personalizado_frontend');
function mostrar_widget_personalizado_frontend() {
    $mostrar_en_paginas = get_option('widget_personalizado_mostrar_en_paginas', []);
    $imagen_url = get_option('widget_personalizado_imagen_url', '');
    $telefono_ar = get_option('telefono_ar', '');

    if (!empty($mostrar_en_paginas) && (in_array('todas', $mostrar_en_paginas) || is_page($mostrar_en_paginas))) {
        if (wp_is_mobile()) {
            // Móvil / tablet: WhatsApp
            ?>
            <div id="widget-personalizado" style="position: fixed; bottom: 20px; right: 20px; z-index:9999;">
                <a href="#" id="widget-link">
                    <img src="<?php echo esc_url($imagen_url); ?>" alt="Widget Personalizado">
                </a>
            </div>
            <script>
            function isTablet() {
                return /iPad|Android(?!.*Mobile)/i.test(navigator.userAgent);
            }
            document.addEventListener('DOMContentLoaded', function() {
                if (isTablet()) {
                    document.getElementById('widget-personalizado').style.display = 'none';
                    return;
                }
                var telefono = "<?php echo $telefono_ar; ?>";
                var mensaje = encodeURIComponent("¡Hola! Estoy interesado/a en Maxirest");
                var whatsappWebLink = "https://api.whatsapp.com/send?phone=" + telefono + "&text=" + mensaje;

                var widgetLink = document.getElementById('widget-link');
                if (widgetLink) {
                    widgetLink.addEventListener('click', function(e) {
                        e.preventDefault();
                        window.open(whatsappWebLink, '_blank');
                    });
                }
            });
            </script>
            <?php
        } else {
            // Escritorio: Live Helper Chat
            ?>
            <script>
            function loadChatWidget() {
                if(window.LHC_API && window.LHC_API.loaded) return;
                (function () {
                    window.LHC_API = window.LHC_API || {};
                    window.LHC_API.args = {
                        mode: "widget",
                        lhc_base_url: "//n8nchat.maxirest.com/index.php/",
                        wheight: 450,
                        wwidth: 350,
                        pheight: 520,
                        pwidth: 500,
                        leaveamessage: true,
                        department: ["2"],
                        check_messages: false,
                        lang: "esp/"
                    };
                    const script = document.createElement("script");
                    script.type = "text/javascript";
                    script.setAttribute("crossorigin", "anonymous");
                    script.async = true;
                    const date = new Date();
                    script.src = "//n8nchat.maxirest.com/design/defaulttheme/js/widgetv2/index.js?" +
                                 ("" + date.getFullYear() + date.getMonth() + date.getDate());
                    document.body.appendChild(script);
                    window.LHC_API.loaded = true;
                })();
            }
            document.addEventListener('scroll', loadChatWidget, {once:true});
            document.addEventListener('click', loadChatWidget, {once:true});
            </script>
            <?php
        }
    }
}

