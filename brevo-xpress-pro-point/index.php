<?php
/*
Plugin Name: Brevo Templates por Formulario Elementor (Solo sitio Argentina)
Description: Permite asignar una plantilla de Brevo diferente a cada formulario de Elementor (Pro, Xpress y Point).
Version: 1.1
Author: Javier Olivieri
*/

if (!defined('ABSPATH')) exit;

/* ==========================================================
   SETTINGS PAGE
========================================================== */

add_action('admin_menu', function () {
    add_menu_page(
        'Brevo Formularios',
        'Brevo Formularios (Pro, Xpress y Point)',
        'manage_options',
        'brevo-formularios',
        'brevo_formularios_settings_page',
        'dashicons-email-alt',
        80
    );
});

function brevo_formularios_settings_page()
{
    ?>
    <div class="wrap">
        <h1>Plantillas Brevo por Formulario</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('brevo_formularios_group');
            do_settings_sections('brevo-formularios');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', function () {

    register_setting('brevo_formularios_group', 'brevo_template_pro');
    register_setting('brevo_formularios_group', 'brevo_template_xpress');
    register_setting('brevo_formularios_group', 'brevo_template_point');

    add_settings_section(
        'brevo_formularios_section',
        'Configuración de Plantillas',
        null,
        'brevo-formularios'
    );

    add_settings_field(
        'brevo_template_pro',
        'Plantilla para Formulario Plan Pro (form_final_pro)',
        'brevo_template_pro_callback',
        'brevo-formularios',
        'brevo_formularios_section'
    );

    add_settings_field(
        'brevo_template_xpress',
        'Plantilla para Formulario Plan Xpress (form_final_xpress)',
        'brevo_template_xpress_callback',
        'brevo-formularios',
        'brevo_formularios_section'
    );

    add_settings_field(
        'brevo_template_point',
        'Plantilla para Formulario Plan Point (form_final_point)',
        'brevo_template_point_callback',
        'brevo-formularios',
        'brevo_formularios_section'
    );
});

/* ==========================================================
   CALLBACKS
========================================================== */

function brevo_template_pro_callback()
{
    $value = esc_attr(get_option('brevo_template_pro'));
    echo '<input type="number" name="brevo_template_pro" value="' . $value . '" style="width:120px" />';
    echo brevo_template_description('Plan Pro');
}

function brevo_template_xpress_callback()
{
    $value = esc_attr(get_option('brevo_template_xpress'));
    echo '<input type="number" name="brevo_template_xpress" value="' . $value . '" style="width:120px" />';
    echo brevo_template_description('Plan Xpress');
}

function brevo_template_point_callback()
{
    $value = esc_attr(get_option('brevo_template_point'));
    echo '<input type="number" name="brevo_template_point" value="' . $value . '" style="width:120px" />';
    echo brevo_template_description('Plan Point');
}

function brevo_template_description($plan)
{
    return '
    <p class="description">
        Ingresá el <strong>ID de la plantilla transaccional de Brevo</strong> que querés usar para el formulario 
        <strong>' . esc_html($plan) . '</strong>.<br><br>

        Para obtener este ID en Brevo:
        <ol>
            <li>Entrá a tu cuenta de Brevo.</li>
            <li>Andá a <strong>Campaigns → Transactional → Templates</strong>.</li>
            <li>Abrí la plantilla que querés usar.</li>
            <li>En la parte superior de la pantalla, Brevo muestra el <strong>ID de la plantilla</strong>.</li>
            <li>Copiá ese número y pegalo acá.</li>
        </ol>
    </p>';
}

/* ==========================================================
   ENVÍO A BREVO DESDE ELEMENTOR
========================================================== */

add_action('elementor_pro/forms/new_record', function ($record, $handler) {

    $api_key = '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';

    $form_name = $record->get_form_settings('form_name');
    $fields    = $record->get('fields');

    $nombre = $fields['name']['value'] ?? '';
    $email  = $fields['email']['value'] ?? '';
    $tel    = $fields['tel']['value'] ?? '';
    $nombrePlan  = $fields['plan_elegido']['value'] ?? '';
    $precioLista = $fields['precio_lista']['value'] ?? '';
    $terminalesInfo = $fields['terminales_info']['value'] ?? '';
    $precioTotal = $fields['precio_total']['value'] ?? '';

    if (empty($email)) return;
    
if ($form_name === 'form_final_point') {
    $terminalesInfo = 'Sin adicional (Sin cargo)';
} else {
    // Eliminamos posibles saltos de línea y espacios al inicio/final
    $terminalesInfo = trim(preg_replace("/\s+/", " ", $terminalesInfo));

    // Transformamos "10 terminales - $100.000" a "10 - ($100.000)"
    if (preg_match('/(\d+)\s*terminales\s*-\s*(\$[\d\.]+)/i', $terminalesInfo, $matches)) {
        $terminalesInfo = $matches[1] . ' - (' . $matches[2] . ')';
    }
}

    // Elegir plantilla según formulario
    if ($form_name === 'form_final_pro') {
        $template_id = get_option('brevo_template_pro');
    } elseif ($form_name === 'form_final_xpress') {
        $template_id = get_option('brevo_template_xpress');
    } elseif ($form_name === 'form_final_point') {
        $template_id = get_option('brevo_template_point');

    } else {
        return;
    }

    if (empty($template_id)) return;

    $body = [
        'to' => [
            [
                'email' => $email,
                'name'  => $nombre
            ]
        ],
        'templateId' => (int) $template_id,
        'params' => [
            'nombre'   => $nombre,
            'email'    => $email,
            'telefono' => $tel,
            'plan'     => $form_name,
            'nombrePlan' => $nombrePlan,
            'precioLista' => $precioLista,
            'terminalesInfo' => $terminalesInfo,
            'precioTotal' => $precioTotal
        ]
    ];

    wp_remote_post('https://api.brevo.com/v3/smtp/email', [
        'headers' => [
            'api-key'      => $api_key,
            'Content-Type' => 'application/json',
            'accept'       => 'application/json'
        ],
        'body'    => json_encode($body),
        'timeout' => 15
    ]);

}, 10, 2);
