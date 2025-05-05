<?php
/**
 * Gestione dell'interfaccia amministrativa
 * 
 * Questa classe gestisce l'interfaccia amministrativa dell'ERP Core,
 * inclusa la SPA (Single Page Application) basata su Vue.js.
 * 
 * @package ERP_Core
 */

// Impedisce l'accesso diretto
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ERP_Core_Admin {
    /**
     * Istanza singola della classe (singleton)
     * @var ERP_Core_Admin
     */
    private static $instance = null;

    /**
     * Ottiene l'istanza singola della classe
     * @return ERP_Core_Admin
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Costruttore della classe
     */
    private function __construct() {
        // Inizializza l'interfaccia admin
        add_action( 'admin_init', array( $this, 'init' ) );
        
        // Aggiunge azioni per nascondere l'interfaccia WordPress
        add_action( 'admin_head', array( $this, 'hide_wp_interface' ) );
        
        // Aggiungi script admin per la SPA
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 99 );
        
        // Aggiungi endpoint AJAX per azioni specifiche
        add_action( 'wp_ajax_erp_get_current_user', array( $this, 'ajax_get_current_user' ) );
        add_action( 'wp_ajax_erp_save_user_preference', array( $this, 'ajax_save_user_preference' ) );
    }

    /**
     * Inizializza l'interfaccia admin
     */
public function init() {
    // Registra la pagina amministrativa
    add_action('admin_menu', array($this, 'register_admin_menu'));
}

/**
 * Registra la voce di menu nell'admin di WordPress
 */
public function register_admin_menu() {
    add_menu_page(
        __('ERP Core', 'erp-core'),
        __('ERP Core', 'erp-core'),
        'manage_options',
        'erp-dashboard',
        array($this, 'render_admin_page'),
        'dashicons-chart-area',
        3
    );
}

    /**
     * Nasconde l'interfaccia WordPress nelle pagine dell'ERP
     */
    public function hide_wp_interface() {
        global $pagenow;
        
        // Controlla se siamo in una pagina ERP
        if ( $pagenow === 'admin.php' && isset( $_GET['page'] ) && strpos( $_GET['page'], 'erp-' ) === 0 ) {
            ?>
            <style type="text/css">
                /* Nascondi elementi WordPress */
                #wpadminbar, #adminmenumain, #wpfooter {
                    display: none !important;
                }
                
                /* Espandi il contenuto */
                #wpcontent, #wpbody-content {
                    margin-left: 0 !important;
                    padding-left: 0 !important;
                }
                
                /* Stile per il contenitore principale */
                html.wp-toolbar {
                    padding-top: 0 !important;
                }
                
                /* Rimuovi margini e padding */
                body, html {
                    width: 100%;
                    height: 100%;
                    margin: 0;
                    padding: 0;
                    overflow: hidden;
                }
                
                /* Nascondi elementi WordPress aggiuntivi */
                .notice, .updated, .error, .is-dismissible {
                    display: none !important;
                }
                
                /* Assicura che il contenitore ERP occupi tutto lo spazio */
                .erp-app-container {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    z-index: 9999;
                }
            </style>
            <?php
        }
    }

    /**
     * Registra e carica gli script per l'admin
     * 
     * @param string $hook Hook corrente di WordPress
     */
    public function enqueue_admin_scripts( $hook ) {
        // Carica script e stili solo nelle pagine del plugin
        if ( strpos( $hook, 'erp-' ) === false ) {
            return;
        }
        
        // Deregistra gli script di WordPress che possono causare conflitti
        wp_deregister_script( 'heartbeat' );
        
        // Registra script Vue.js (in modalità produzione)
        wp_register_script(
            'vue',
            'https://cdn.jsdelivr.net/npm/vue@3.2.47/dist/vue.global.prod.js',
            array(),
            '3.2.47',
            true
        );
        
        // Registra script Quasar Framework
        wp_register_script(
            'quasar',
            'https://cdn.jsdelivr.net/npm/quasar@2.11.10/dist/quasar.umd.prod.js',
            array( 'vue' ),
            '2.11.10',
            true
        );
        
        // Registra script Vue Router
        wp_register_script(
            'vue-router',
            'https://cdn.jsdelivr.net/npm/vue-router@4.1.6/dist/vue-router.global.prod.js',
            array( 'vue' ),
            '4.1.6',
            true
        );
        
        // Registra Axios per le richieste HTTP
        wp_register_script(
            'axios',
            'https://cdn.jsdelivr.net/npm/axios@1.3.4/dist/axios.min.js',
            array(),
            '1.3.4',
            true
        );
        
        // Registra lo script principale dell'app
        wp_register_script(
            'erp-admin-app',
            ERP_CORE_ASSETS_URL . 'js/app.js',
            array( 'vue', 'quasar', 'vue-router', 'axios' ),
            ERP_CORE_VERSION,
            true
        );
        
        // Registra i componenti base dell'app
        wp_register_script(
            'erp-admin-components',
            ERP_CORE_ASSETS_URL . 'js/components.js',
            array( 'erp-admin-app' ),
            ERP_CORE_VERSION,
            true
        );
        
		// Registra gli stili Quasar
		wp_register_style(
			'quasar-css',
			'https://cdn.jsdelivr.net/npm/quasar@2.11.10/dist/quasar.prod.css',
			array(),
			'2.11.10'
		);

		// AGGIUNGI QUI - Registra Lucide Icons
		wp_register_script(
			'lucide-icons',
			'https://unpkg.com/lucide@latest',
			array(),
			ERP_CORE_VERSION,
			true
		);

		// AGGIUNGI QUI - Registra Lucide per Vue
		wp_register_script(
			'lucide-vue',
			'https://unpkg.com/lucide-vue-next@latest',
			array('lucide-icons'),
			ERP_CORE_VERSION,
			true
		);

		// AGGIUNGI QUI - Script di inizializzazione per Lucide
		wp_add_inline_script(
			'lucide-icons',
			'document.addEventListener("DOMContentLoaded", function() { if(typeof lucide !== "undefined") { lucide.createIcons(); } });'
		);
        
        // Registra lo stile principale dell'app
        wp_register_style(
            'erp-admin-style',
            ERP_CORE_ASSETS_URL . 'css/app.css',
            array( 'quasar-css' ),
            ERP_CORE_VERSION
        );
        
        // Registra stili per il tema scuro (predefinito)
        wp_register_style(
            'erp-admin-dark-theme',
            ERP_CORE_ASSETS_URL . 'css/theme-dark.css',
            array( 'erp-admin-style' ),
            ERP_CORE_VERSION
        );
        
        // Registra stili per il tema chiaro
        wp_register_style(
            'erp-admin-light-theme',
            ERP_CORE_ASSETS_URL . 'css/theme-light.css',
            array( 'erp-admin-style' ),
            ERP_CORE_VERSION
        );
        
        // Carica lo stile appropriato in base alla preferenza dell'utente
        $user_theme = get_user_meta( get_current_user_id(), 'erp_theme_mode', true ) ?: 'dark';
        if ( $user_theme === 'dark' ) {
            wp_enqueue_style( 'erp-admin-dark-theme' );
        } else {
            wp_enqueue_style( 'erp-admin-light-theme' );
        }
        
		// Registra e carica Vue.js
		wp_register_script(
			'vue',
			'https://unpkg.com/vue@3/dist/vue.global.js',
			array(),
			'3.2.47',
			false // Caricalo nell'header
		);

		// Carica gli script e gli stili necessari
		wp_enqueue_script( 'vue' ); // Carica esplicitamente Vue
		wp_enqueue_script( 'vue-router' ); // Carica VueRouter prima di app.js
		wp_enqueue_script( 'axios' ); // Carica Axios prima di app.js
		wp_enqueue_script( 'erp-admin-components' );
		wp_enqueue_style( 'erp-admin-style' );

		// Carica Lucide Icons
		wp_enqueue_script( 'lucide-icons' );
		wp_enqueue_script( 'lucide-vue' );
        
        // Passa dati a JavaScript
        wp_localize_script(
            'erp-admin-app',
            'erpCoreData',
            array(
                'apiUrl' => esc_url_raw( rest_url( 'erp/v1/' ) ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'adminUrl' => admin_url(),
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'modules' => ERP_Core_Modules::instance()->get_active_modules(),
                'dashboardCards' => ERP_Core_Modules::instance()->get_dashboard_cards(),
                'reports' => ERP_Core_Modules::instance()->get_reports(),
                'routes' => ERP_Core_Modules::instance()->get_vue_routes(),
                'currentUser' => $this->get_current_user_data(),
                'version' => ERP_CORE_VERSION,
                'themeMode' => $user_theme,
                'pluginUrl' => ERP_CORE_URL,
                'assetUrl' => ERP_CORE_ASSETS_URL,
                'icons' => $this->get_icon_list()
            )
        );
    }

    /**
     * Ottiene dati dell'utente corrente per l'app
     * 
     * @return array Dati utente
     */
    private function get_current_user_data() {
        $current_user = wp_get_current_user();
        
        return array(
            'id' => $current_user->ID,
            'username' => $current_user->user_login,
            'name' => $current_user->display_name,
            'email' => $current_user->user_email,
            'avatar' => get_avatar_url( $current_user->ID ),
            'role' => reset( $current_user->roles ),
            'permissions' => ERP_Core_ACL::instance()->get_user_permissions( $current_user->ID )
        );
    }

    /**
     * Ottiene la lista di icone disponibili
     * 
     * @return array Lista di icone
     */
    private function get_icon_list() {
        return array(
            'dashboard' => 'dashboard',
            'products' => 'inventory_2',
            'clients' => 'people',
            'suppliers' => 'local_shipping',
            'documents' => 'description',
            'inventory' => 'inventory',
            'accounting' => 'account_balance',
            'woocommerce' => 'shopping_cart',
            'reports' => 'bar_chart',
            'system' => 'settings',
            'integrations' => 'link',
            'exit' => 'exit_to_app',
            'user' => 'person',
            'add' => 'add',
            'edit' => 'edit',
            'delete' => 'delete',
            'save' => 'save',
            'cancel' => 'cancel',
            'search' => 'search',
            'filter' => 'filter_list',
            'print' => 'print',
            'export' => 'download',
            'import' => 'upload',
            'refresh' => 'refresh',
            'more' => 'more_vert',
            'settings' => 'settings',
            'help' => 'help',
            'info' => 'info',
            'warning' => 'warning',
            'error' => 'error',
            'success' => 'check_circle',
            'menu' => 'menu',
            'close' => 'close',
            'arrow_back' => 'arrow_back',
            'arrow_forward' => 'arrow_forward',
            'calendar' => 'calendar_today',
            'notification' => 'notifications',
            'email' => 'email',
            'phone' => 'phone',
            'location' => 'location_on',
            'link' => 'link',
            'home' => 'home',
            'company' => 'business',
            'lock' => 'lock',
            'unlock' => 'lock_open',
            'star' => 'star',
            'favorite' => 'favorite',
            'attachment' => 'attachment',
            'cloud' => 'cloud',
            'download' => 'cloud_download',
            'upload' => 'cloud_upload',
            'sync' => 'sync',
            'history' => 'history',
            'visibility' => 'visibility',
            'visibility_off' => 'visibility_off',
            'expand_more' => 'expand_more',
            'expand_less' => 'expand_less',
            'list' => 'list',
            'grid' => 'grid_view',
            'sort' => 'sort',
            'sort_by_alpha' => 'sort_by_alpha',
            'today' => 'today',
            'event' => 'event',
            'schedule' => 'schedule',
            'picture' => 'image',
            'folder' => 'folder',
            'folder_open' => 'folder_open',
            'file' => 'insert_drive_file',
            'category' => 'category'
        );
    }

    /**
     * Gestisce la richiesta AJAX per ottenere i dati dell'utente corrente
     */
    public function ajax_get_current_user() {
        // Verifica il nonce per sicurezza
        check_ajax_referer( 'erp-admin-nonce', 'nonce' );
        
        // Restituisci i dati dell'utente
        wp_send_json_success( $this->get_current_user_data() );
    }

    /**
     * Gestisce la richiesta AJAX per salvare una preferenza utente
     */
    public function ajax_save_user_preference() {
        // Verifica il nonce per sicurezza
        check_ajax_referer( 'erp-admin-nonce', 'nonce' );
        
        // Ottieni i parametri
        $key = isset( $_POST['key'] ) ? sanitize_text_field( $_POST['key'] ) : '';
        $value = isset( $_POST['value'] ) ? sanitize_text_field( $_POST['value'] ) : '';
        
        if ( empty( $key ) ) {
            wp_send_json_error( array( 'message' => __( 'Chiave non specificata', 'erp-core' ) ) );
        }
        
        // Prefisso per evitare conflitti
        $meta_key = 'erp_' . $key;
        
        // Salva la preferenza
        $result = update_user_meta( get_current_user_id(), $meta_key, $value );
        
        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Preferenza salvata con successo', 'erp-core' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Errore nel salvare la preferenza', 'erp-core' ) ) );
        }
    }
	
    /**
     * Renderizza la pagina admin principale (container per Vue SPA)
     */
    public function render_admin_page() {
        echo '<div id="erp-app" class="erp-app-container">';
        echo '<div style="color: white; padding: 20px;">Test visibilità contenuto</div>';
        echo '</div>';
    }
}

// Inizializza l'admin
ERP_Core_Admin::instance();