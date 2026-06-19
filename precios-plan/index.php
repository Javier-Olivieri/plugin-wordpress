<?php
/*
Plugin Name: Precios de Planes (Se activa solo en Argentina)
Description: Administra precios de Plan Xpress, Pro, Point y Terminales desde el panel.
Version: 1.5
Author: Javier Olivieri
*/

if (!defined('ABSPATH')) exit;

/* ============================
   MENÚ ADMIN
   ============================ */
add_action('admin_menu','pp_add_admin_menu');
add_action('admin_init','pp_settings_init');

function pp_add_admin_menu() {
    add_menu_page(
        'Precios de Planes',
        'Precios Planes',
        'manage_options',
        'precios_planes',
        'pp_options_page',
        'dashicons-tag',
        25
    );
}

/* ============================
   SETTINGS
   ============================ */
function pp_settings_init() {

    register_setting('ppSettings','pp_settings');

    add_settings_section(
        'pp_section',
        'Configuración de Precios',
        null,
        'precios_planes'
    );

    add_settings_field('pp_xpress','Precio Plan Xpress','pp_xpress_render','precios_planes','pp_section');
    add_settings_field('pp_pro','Precio Plan Pro','pp_pro_render','precios_planes','pp_section');
    add_settings_field('pp_point','Precio Plan Point','pp_point_render','precios_planes','pp_section');

    add_settings_field('pp_terminales_xpress','Precio Terminales Xpress','pp_terminales_xpress_render','precios_planes','pp_section');
    add_settings_field('pp_terminales_pro','Precio Terminales Pro','pp_terminales_pro_render','precios_planes','pp_section');
    add_settings_field('pp_terminales_point','Precio Terminales Point','pp_terminales_point_render','precios_planes','pp_section');
}

/* ============================
   RENDERS DE CAMPOS
   ============================ */

function pp_xpress_render() {
    $o = get_option('pp_settings'); ?>
    <input type="text" name="pp_settings[pp_xpress]" value="<?php echo esc_attr($o['pp_xpress'] ?? ''); ?>" style="width:200px;">
    <span style="margin-left:10px;font-size:13px;">
        <code id="sc-xpress">[precio_plan_xpress]</code>
        <button type="button" class="button" onclick="ppCopyShortcode('sc-xpress')">Copiar</button>
    </span>
<?php }

function pp_pro_render() {
    $o = get_option('pp_settings'); ?>
    <input type="text" name="pp_settings[pp_pro]" value="<?php echo esc_attr($o['pp_pro'] ?? ''); ?>" style="width:200px;">
    <span style="margin-left:10px;font-size:13px;">
        <code id="sc-pro">[precio_plan_pro]</code>
        <button type="button" class="button" onclick="ppCopyShortcode('sc-pro')">Copiar</button>
    </span>
<?php }

function pp_point_render() {
    $o = get_option('pp_settings'); ?>
    <input type="text" name="pp_settings[pp_point]" value="<?php echo esc_attr($o['pp_point'] ?? ''); ?>" style="width:200px;">
    <span style="margin-left:10px;font-size:13px;">
        <code id="sc-point">[precio_plan_point]</code>
        <button type="button" class="button" onclick="ppCopyShortcode('sc-point')">Copiar</button>
    </span>
<?php }

function pp_terminales_xpress_render() {
    $o = get_option('pp_settings'); ?>
    <input type="text" name="pp_settings[pp_terminales_xpress]" value="<?php echo esc_attr($o['pp_terminales_xpress'] ?? ''); ?>" style="width:200px;">
    <span style="margin-left:10px;font-size:13px;">
        <code id="sc-terminales-xpress">[precio_plan_terminales_xpress]</code>
        <button type="button" class="button" onclick="ppCopyShortcode('sc-terminales-xpress')">Copiar</button>
    </span>
<?php }

function pp_terminales_pro_render() {
    $o = get_option('pp_settings'); ?>
    <input type="text" name="pp_settings[pp_terminales_pro]" value="<?php echo esc_attr($o['pp_terminales_pro'] ?? ''); ?>" style="width:200px;">
    <span style="margin-left:10px;font-size:13px;">
        <code id="sc-terminales-pro">[precio_plan_terminales_pro]</code>
        <button type="button" class="button" onclick="ppCopyShortcode('sc-terminales-pro')">Copiar</button>
    </span>
<?php }

function pp_terminales_point_render() {
    $o = get_option('pp_settings'); ?>
    <input type="text" name="pp_settings[pp_terminales_point]" value="<?php echo esc_attr($o['pp_terminales_point'] ?? ''); ?>" style="width:200px;">
    <span style="margin-left:10px;font-size:13px;">
        <code id="sc-terminales-point">[precio_plan_terminales_point]</code>
        <button type="button" class="button" onclick="ppCopyShortcode('sc-terminales-point')">Copiar</button>
    </span>
<?php }

/* ============================
   PÁGINA OPCIONES
   ============================ */
function pp_options_page() { ?>
    <div class="wrap">
        <h1>
            Precios de Planes<br>
            <small>Ingresar valores sin “$”. Usar punto para miles (Ej: 53.375)</small>
        </h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('ppSettings');
            do_settings_sections('precios_planes');
            submit_button('Guardar precios');
            ?>
        </form>
    </div>
<?php }

/* ============================
   COPIAR SHORTCODES
   ============================ */
add_action('admin_footer', function() { ?>
<script>
function ppCopyShortcode(id){
    const el=document.getElementById(id);
    navigator.clipboard.writeText(el.innerText).then(()=>{
        let msg=document.createElement("span");
        msg.className="pp-copiado-msg";
        msg.innerText=" Copiado ✔";
        msg.style.color="#2e7d32";
        msg.style.marginLeft="6px";
        el.parentNode.appendChild(msg);
        setTimeout(()=>msg.remove(),1500);
    });
}
</script>
<?php });

/* ============================
   FORMATO PRECIO
   ============================ */
function pp_format_price($value){
    $n=preg_replace('/[^0-9]/','',$value);
    if($n==='') return '';
    return '$'.number_format($n,0,',','.');
}

/* ============================
   SHORTCODES
   ============================ */
add_shortcode('precio_plan_xpress', fn() => ($o=get_option('pp_settings')) && !empty($o['pp_xpress']) ? pp_format_price($o['pp_xpress']) : '');
add_shortcode('precio_plan_pro', fn() => ($o=get_option('pp_settings')) && !empty($o['pp_pro']) ? pp_format_price($o['pp_pro']) : '');
add_shortcode('precio_plan_point', fn() => ($o=get_option('pp_settings')) && !empty($o['pp_point']) ? pp_format_price($o['pp_point']) : '');

add_shortcode('precio_plan_terminales_xpress', fn() => ($o=get_option('pp_settings')) && !empty($o['pp_terminales_xpress']) ? pp_format_price($o['pp_terminales_xpress']) : '');
add_shortcode('precio_plan_terminales_pro', fn() => ($o=get_option('pp_settings')) && !empty($o['pp_terminales_pro']) ? pp_format_price($o['pp_terminales_pro']) : '');
add_shortcode('precio_plan_terminales_point', fn() => ($o=get_option('pp_settings')) && !empty($o['pp_terminales_point']) ? pp_format_price($o['pp_terminales_point']) : '');
