<?php
/*
Plugin Name: Visibilidad nuevo chat
Description: Elegí en qué página querés que se vea el nuevo chat y podés cambiar la URL del script del widget.
Version: 2025
Author: Javier I Olivieri
*/

// Agregar menú en admin
add_action('admin_menu', 'agregar_pagina_configuracion');
function agregar_pagina_configuracion() {
    add_menu_page(
        'Configuración del Widget Personalizado',
        'Visibilidad nuevo chat',
        'manage_options',
        'widget-personalizado-config',
        'mostrar_pagina_configuracion',
        'dashicons-visibility'
    );
}

// Mostrar las páginas donde está activo el chat
function mostrar_registro_paginas_marcadas() {
    $paginas_marcadas = get_option('widget_personalizado_mostrar_en_paginas', []);
    echo '<h3 style="text-decoration: underline;">Páginas en donde se están mostrando</h3>';

    if (in_array('todas', $paginas_marcadas)) {
        echo '<p><strong>Todas</strong></p>';
        return;
    }

    if (!empty($paginas_marcadas)) {
        echo '<ul>';
        foreach ($paginas_marcadas as $pagina_id) {
            $pagina = get_post($pagina_id);
            if ($pagina) echo '<li>' . esc_html($pagina->post_title) . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No hay páginas marcadas actualmente.</p>';
    }
}

// Mostrar el formulario de configuración
function mostrar_pagina_configuracion() {
    if (isset($_POST['guardar_configuracion'])) {

        // Guardar las páginas seleccionadas
        if (isset($_POST['mostrar_en_paginas'])) {
            if (in_array('todas', $_POST['mostrar_en_paginas'])) {
                update_option('widget_personalizado_mostrar_en_paginas', ['todas']);
            } else {
                update_option('widget_personalizado_mostrar_en_paginas', $_POST['mostrar_en_paginas']);
            }
        } else {
            update_option('widget_personalizado_mostrar_en_paginas', []);
        }

        // Guardar la URL del script del widget y asegurar que tenga https://
        if (isset($_POST['url_script_widget'])) {
            $url_input = trim($_POST['url_script_widget']);
            if (!preg_match('#^https?://#i', $url_input)) {
                $url_input = 'https://' . ltrim($url_input, '/');
            }
            $url_script_widget = esc_url_raw($url_input);
            update_option('widget_personalizado_url_script_widget', $url_script_widget);
        }

        echo '<div class="updated"><p>Configuración guardada con éxito.</p></div>';
    }

    $mostrar_en_paginas = get_option('widget_personalizado_mostrar_en_paginas', []);
    $url_script_widget = get_option('widget_personalizado_url_script_widget', 'https://n8nchat.maxirest.com/design/defaulttheme/js/widgetv2/index.js');
    $paginas = get_posts(['post_type' => 'page', 'posts_per_page' => -1]);
    ?>

    <style>
        .wrap h2, .wrap h4 { margin-bottom: 10px; }
        .input-url { width: 100%; max-width: 600px; }
    </style>

    <div class="wrap">
   <h2 style="font-size: 28px; font-weight: bold; margin-bottom: 10px;">Configuración de Comunicación con Clientes</h2>
  
  <p style="font-size: 16px; margin-bottom: 20px;"><strong>
    Este plugin fue diseñado para mostrar el nuevo chatbot en dispositivos de escritorio, únicamente en las páginas que selecciones.</strong>
  </p>
  
  <h3 style="font-size: 20px; font-weight: bold; text-decoration: underline; margin-top: 30px;">¿Cómo usarlo?</h3>
  
      <ol style="font-size: 16px; padding-left: 20px; margin-top: 15px; font-weight:600">
    <li style="margin-bottom: 12px;">
      <strong>Paso 1:</strong> Copiá la URL del chatbot desde el valor asignado a <code>script.src</code> dentro del código del script.
      <br>
      Por ejemplo, si tenés este fragmento:
      <pre style="background-color: lightgray; padding: 10px; border-left: 4px solid #ccc; font-size: 14px; overflow-x: auto;">
script.src = "//n8nchat.maxirest.com/design/defaulttheme/js/widgetv2/index.js?";
      </pre>
      Solo necesitás copiar esta parte:  
      <code>//n8nchat.maxirest.com/design/defaulttheme/js/widgetv2/index.js</code>
      <br>
      No te preocupes si no tiene <code>https://</code>, el sistema lo agregará automáticamente al guardar.
    </li>
    <br>
          <li style="margin-bottom: 12px;">
      <strong>Paso 2:</strong> Seleccioná la(s) página(s) donde querés que se muestre el chatbot. Si no seleccionás ninguna, no se mostrará.
    </li>
<br>
    <li style="margin-bottom: 12px;">
      <strong>Paso 3:</strong> Hacé clic en <em>Guardar configuración</em> para aplicar los cambios.
    </li>
  </ol>

        <form method="post" enctype="multipart/form-data" onsubmit="return validarURLChat();">

            <h3 style="text-decoration:underline;margin-top:30px">URL del script del widget del chat:</h3>
       <input 
    type="text" 
    id="url_script_widget"
    name="url_script_widget" 
    value="<?php echo esc_attr($url_script_widget); ?>" 
    class="input-url"
    placeholder="https://ejemplo.com/path/to/widget.js"
    required
>
            <p>Modificá esta URL para cambiar el script que se carga en el chat.</p>

            <h3 style="text-decoration:underline">Mostrar en las siguientes páginas:</h3>
            <input type="checkbox" id="mostrar_todas" name="mostrar_en_paginas[]" value="todas" <?php echo in_array('todas', $mostrar_en_paginas) ? 'checked' : ''; ?> onclick="toggleCheckboxes(this)">
            <label for="mostrar_todas">Todas</label><br>

            <?php foreach ($paginas as $pagina) : ?>
                <input type="checkbox" class="pagina_checkbox" name="mostrar_en_paginas[]" value="<?php echo esc_attr($pagina->ID); ?>" <?php echo in_array('todas', $mostrar_en_paginas) || in_array($pagina->ID, $mostrar_en_paginas) ? 'checked' : ''; ?>>
                <label for="pagina_<?php echo esc_attr($pagina->ID); ?>"><?php echo esc_html($pagina->post_title); ?></label><br>
            <?php endforeach; ?>

            <?php mostrar_registro_paginas_marcadas(); ?>

            <br>
            <input type="submit" name="guardar_configuracion" value="Guardar Configuración" class="button-primary">
        </form>
    </div>

    <script>
        function toggleCheckboxes(master) {
            const checkboxes = document.querySelectorAll('.pagina_checkbox');
            checkboxes.forEach(cb => cb.checked = master.checked);
        }

        function validarURLChat() {
            const input = document.getElementById('url_script_widget');
            let url = input.value.trim();

            if (!url.match(/^https?:\/\//i)) {
                url = 'https://' + url.replace(/^\/+/, '');
                input.value = url;
            }

            return true;
        }
    </script>

    <?php
}

// Insertar el script del chat en el footer según páginas seleccionadas y usando URL guardada
add_action('wp_footer', 'mostrar_widget_personalizado');
function mostrar_widget_personalizado() {
    $mostrar_en_paginas = get_option('widget_personalizado_mostrar_en_paginas', []);
    $url_script_widget = get_option('widget_personalizado_url_script_widget', 'https://n8nchat.maxirest.com/design/defaulttheme/js/widgetv2/index.js');

    $script_desktop = <<<EOT
<script>
(function () {
    window.LHC_API = window.LHC_API || {};
    window.LHC_API.args = {
        mode: "widget",
        lhc_base_url: "https://n8nchat.maxirest.com/index.php/",
        wheight: 450,
        wwidth: 350,
        pheight: 520,
        pwidth: 500,
        leaveamessage: true,
        department: ["2"],
        check_messages: false,
        lang: "esp/",
    };

    const script = document.createElement("script");
    script.type = "text/javascript";
    script.setAttribute("crossorigin", "anonymous");
    script.async = true;

    const date = new Date();
    script.src = "$url_script_widget?" + ("" + date.getFullYear() + date.getMonth() + date.getDate());

    const firstScript = document.getElementsByTagName("script")[0];
    firstScript.parentNode.insertBefore(script, firstScript);
})();
</script>
EOT;

    if (in_array('todas', $mostrar_en_paginas)) {
        echo $script_desktop;
        return;
    }

    if (is_singular() && in_array(get_the_ID(), $mostrar_en_paginas)) {
        echo $script_desktop;
    }
}
