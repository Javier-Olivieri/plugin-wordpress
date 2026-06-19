<?php
/**
 * Plugin Name: Elementor Submissions Cleaner Manager
 * Description: Muestra y permite la eliminación selectiva de nombres de formularios sin envíos (Submissions) en Elementor Pro.
 * Version: 1.3 // Versión final, extendida y robusta.
 * Author: Gemini
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Evita el acceso directo
}

class ElementorFormsCleanerManager {

    // Nombres de las opciones de la base de datos a revisar (orden de prioridad de más nuevo a más antiguo)
    const FORM_OPTION_KEYS = [
        'elementor_pro_forms_names',    // Clave más común y moderna
        'elementor_forms_db_versions',  // Clave alternativa 1
        'elementor_forms_names',        // Clave muy antigua
        'elementor_pro_forms_options',  // Clave alternativa 2
    ];

    private $active_option_key = '';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_page' ) );
        add_action( 'admin_init', array( $this, 'process_cleanup_actions' ) );
    }

    /**
     * Añade la página como un menú de nivel superior propio.
     */
    public function add_admin_page() {
        add_menu_page( 
            'Limpiador de Elementor Forms',
            'Limpiar Forms',
            'manage_options',
            'efc-forms-cleaner',
            array( $this, 'render_admin_page' ),
            'dashicons-eraser',
            30
        );
    }

    /**
     * Obtiene la lista actual de formularios revisando las 4 claves.
     * @return array|bool Array de IDs => Nombres de formularios, o false si no hay.
     */
    private function get_registered_forms() {
        $forms_array = false;
        
        foreach ( self::FORM_OPTION_KEYS as $key ) {
            $forms_names = get_option( $key );
            
            if ( ! empty( $forms_names ) ) {
                $forms_array = json_decode( $forms_names, true );
                if ( is_array( $forms_array ) && ! empty( $forms_array ) ) {
                    $this->active_option_key = $key; // Guardamos qué clave está activa
                    return $forms_array;
                }
            }
        }
        
        return false;
    }

    /**
     * Obtiene la lista de formularios que NO tienen envíos guardados.
     * @return array Array de Form ID => Form Name que son "fantasma".
     */
    private function get_ghost_forms() {
        global $wpdb;
        $forms_array = $this->get_registered_forms();
        
        if ( ! $forms_array ) {
            return [];
        }

        $table_name = $wpdb->prefix . 'e_submissions';
        $ghost_forms = [];

        foreach ( $forms_array as $form_id => $form_name ) {
            // Contar envíos (type = 'submission')
            $count = $wpdb->get_var( $wpdb->prepare( 
                "SELECT COUNT(ID) FROM $table_name WHERE form_id = %s AND type = 'submission'", 
                $form_id 
            ) );

            // Si el conteo es 0, es un formulario "fantasma"
            if ( $count == 0 ) {
                $ghost_forms[ $form_id ] = $form_name;
            }
        }
        
        return $ghost_forms;
    }

    /**
     * Función para actualizar la opción de la base de datos con la nueva lista.
     * @param array $new_forms_array Array de IDs => Nombres a guardar.
     */
    private function update_registered_forms( $new_forms_array ) {
        if ( empty( $this->active_option_key ) ) {
            return;
        }
        $new_forms_names = json_encode( $new_forms_array );
        update_option( $this->active_option_key, $new_forms_names );
    }

    /**
     * Renderiza el contenido de la página de administración.
     */
    public function render_admin_page() {
        $ghost_forms = $this->get_ghost_forms();
        $total_ghosts = count( $ghost_forms );
        $nonce = wp_create_nonce( 'efc_cleanup_nonce' );
        ?>
        <div class="wrap">
            <h1>🗑️ Limpiador de Formularios Elementor</h1>
            <p class="description">Identifica y elimina los nombres de formularios que aparecen en el desplegable de Elementor Submissions pero no tienen entradas guardadas.</p>
            
            <?php if ( ! empty( $this->active_option_key ) ) : ?>
                <div class="notice notice-info">
                    <p>La lista de formularios se está leyendo de la clave: <code><?php echo esc_html( $this->active_option_key ); ?></code>.</p>
                </div>
            <?php else : ?>
                 <div class="notice notice-error">
                    <p>🛑 **ERROR:** No se ha podido encontrar la lista de nombres de formularios en ninguna de las claves comunes de Elementor. **Debe buscar el nombre del formulario en phpMyAdmin y proporcionarme la clave `option_name`**.</p>
                </div>
            <?php endif; ?>

            <?php if ( $total_ghosts > 0 ) : ?>
                <div class="notice notice-warning">
                    <p>Se han encontrado <strong><?php echo esc_html( $total_ghosts ); ?></strong> formularios que no tienen envíos guardados.</p>
                </div>
                
                <form method="post" action="">
                    <input type="hidden" name="efc_action" value="clean_all">
                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>">
                    <?php submit_button( 'Limpiar TODOS los Formularios Fantasma (' . $total_ghosts . ')', 'delete', 'efc_clean_all', true, ['onclick' => "return confirm('ADVERTENCIA: ¿Estás seguro de que quieres eliminar TODOS los formularios listados?');"] ); ?>
                </form>

                <h2>Lista de Formularios a Eliminar</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 15%;">ID de Formulario</th>
                            <th scope="col">Nombre del Formulario</th>
                            <th scope="col" style="width: 15%;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $ghost_forms as $id => $name ) : ?>
                            <tr>
                                <td><code><?php echo esc_html( $id ); ?></code></td>
                                <td><?php 
                                    // Compara el ID con el que el usuario identificó
                                    if ($id === '60d910b') {
                                        echo '⭐ ' . esc_html( $name ) . ' (Formulario buscado)';
                                    } else {
                                        echo esc_html( $name );
                                    }
                                ?></td>
                                <td>
                                    <form method="post" action="" style="display: inline-block;">
                                        <input type="hidden" name="efc_action" value="clean_single">
                                        <input type="hidden" name="form_id_to_clean" value="<?php echo esc_attr( $id ); ?>">
                                        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>">
                                        <button type="submit" class="button button-small button-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar el formulario: <?php echo esc_js( $name ); ?>?');">
                                            Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="notice notice-success">
                    <p>🎉 ¡Genial! No se encontraron nombres de formularios sin envíos para limpiar.</p>
                </div>
            <?php endif; ?>
        </div>
        <style>
            .button-danger {
                background: #dc3232;
                border-color: #dc3232 #dc3232 #b32d2e;
                color: #fff;
                box-shadow: 0 1px 0 #b32d2e;
            }
            .button-danger:hover {
                background: #e46e6e;
                border-color: #e46e6e #e46e6e #dc3232;
                color: #fff;
            }
        </style>
        <?php
    }

    /**
     * Procesa las solicitudes de limpieza (masiva o individual).
     */
    public function process_cleanup_actions() {
        if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['efc_action'] ) ) {
            return;
        }

        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'efc_cleanup_nonce' ) ) {
            wp_die( '¡Acción no autorizada!' );
        }

        $forms_array = $this->get_registered_forms();
        if ( ! $forms_array ) {
            return;
        }
        
        $action = sanitize_text_field( $_POST['efc_action'] );
        $new_forms_array = $forms_array;
        $message = '';
        
        // --- LIMPIEZA TOTAL ---
        if ( $action === 'clean_all' ) {
            $ghost_forms = $this->get_ghost_forms();
            $ghost_ids = array_keys( $ghost_forms );
            
            $new_forms_array = array_diff_key( $forms_array, array_flip( $ghost_ids ) );
            $this->update_registered_forms( $new_forms_array );
            $message = count( $ghost_forms ) . ' formularios fantasma eliminados exitosamente.';
            
        // --- LIMPIEZA INDIVIDUAL ---
        } elseif ( $action === 'clean_single' && isset( $_POST['form_id_to_clean'] ) ) {
            $form_id_to_clean = sanitize_text_field( $_POST['form_id_to_clean'] );
            
            if ( isset( $new_forms_array[ $form_id_to_clean ] ) ) {
                $name = $new_forms_array[ $form_id_to_clean ];
                unset( $new_forms_array[ $form_id_to_clean ] );
                $this->update_registered_forms( $new_forms_array );
                $message = 'El formulario "' . esc_html( $name ) . '" fue eliminado exitosamente.';
            }
        }

        if ( $message ) {
            add_settings_error( 'efc-forms-cleaner', 'cleanup_success', $message, 'success' );
            set_transient( 'settings_errors', get_settings_errors(), 30 );
            $redirect_url = admin_url( 'admin.php?page=efc-forms-cleaner' );
            wp_safe_redirect( $redirect_url );
            exit;
        }
    }
}

new ElementorFormsCleanerManager();