<?php
/**
 * Plugin Name: ERP Core
 * Plugin URI: https://microcodice.it/erp-core
 * Description: Trasforma WordPress in un sistema ERP completo e modulare
 * Version: 1.0.5
 * Author: Microcodice
 * Author URI: https://microcodice.it
 * Text Domain: erp-core
 * Domain Path: /languages
 * License: GPL v2 or later
 */

// Impedisce l'accesso diretto
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Definizioni delle costanti globali
define( 'ERP_CORE_VERSION', '1.0.0' );
define( 'ERP_CORE_FILE', __FILE__ );
define( 'ERP_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'ERP_CORE_URL', plugin_dir_url( __FILE__ ) );
define( 'ERP_CORE_ASSETS_URL', ERP_CORE_URL . 'assets/' );
define( 'ERP_CORE_MODULES_PATH', ERP_CORE_PATH . 'modules/' );
define( 'ERP_CORE_TEMPLATES_PATH', ERP_CORE_PATH . 'templates/' );

/**
 * Classe principale del plugin ERP Core
 */
final class ERP_Core {
    /**
     * Istanza singola del plugin (singleton)
     * @var ERP_Core
     */
    private static $instance = null;

    /**
     * Ottiene l'istanza singola del plugin
     * @return ERP_Core
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Costruttore della classe principale
     */
    private function __construct() {
        // Carica i file di traduzione
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

        // Inizializza il plugin
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Carica i file di traduzione
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'erp-core', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    /**
     * Include i file necessari al funzionamento del plugin
     */
    private function includes() {
        // Funzioni di utilità
        require_once ERP_CORE_PATH . 'includes/class-erp-core-utils.php';
        
        // Gestione database e tabelle personalizzate
        require_once ERP_CORE_PATH . 'includes/class-erp-core-db.php';
        
        // Gestione dei moduli
        require_once ERP_CORE_PATH . 'includes/class-erp-core-modules.php';
        
        // Custom Post Types
        require_once ERP_CORE_PATH . 'includes/class-erp-core-cpt.php';
        
        // Gestione API REST
        require_once ERP_CORE_PATH . 'includes/class-erp-core-api.php';
        
        // ACL e gestione permessi
        require_once ERP_CORE_PATH . 'includes/class-erp-core-acl.php';
        
        // Interfaccia admin
        require_once ERP_CORE_PATH . 'admin/class-erp-core-admin.php';
        
        // Integrazione WooCommerce
        if ( class_exists( 'WooCommerce' ) ) {
            require_once ERP_CORE_PATH . 'includes/class-erp-core-woocommerce.php';
        }
    }

    /**
     * Inizializza gli hook principali
     */
    private function init_hooks() {
        // Attivazione e disattivazione
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
        
        // Inizializza i componenti del plugin
        add_action( 'init', array( $this, 'init' ), 0 );
        
        // Aggiunge la voce di menu nell'admin di WordPress
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
        
        // Registra script e stili per l'admin
        add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );
    }

    /**
     * Eseguito quando il plugin viene attivato
     */
    public function activate() {
        // Crea le tabelle nel database
        require_once ERP_CORE_PATH . 'includes/class-erp-core-installer.php';
        $installer = new ERP_Core_Installer();
        $installer->install();
        
        // Imposta flag di attivazione per eventuali redirect
        set_transient( 'erp_core_activated', true, 30 );
    }

    /**
     * Eseguito quando il plugin viene disattivato
     */
    public function deactivate() {
        // Pulizia temporanea (non rimuove i dati)
        flush_rewrite_rules();
    }

    /**
     * Inizializza i componenti principali
     */
    public function init() {
        // Registra i CPT
        ERP_Core_CPT::instance()->register_post_types();
        
        // Inizializza il caricatore dei moduli
        ERP_Core_Modules::instance()->init();
        
        // Inizializza le API REST
        ERP_Core_API::instance()->init();
        
        // Inizializza il sistema ACL
        ERP_Core_ACL::instance()->init();
    }

    /**
     * Registra la voce di menu nell'admin di WordPress
     */
    public function register_admin_menu() {
        add_menu_page(
            __( 'ERP Core', 'erp-core' ),
            __( 'ERP Core', 'erp-core' ),
            'manage_options',
            'erp-dashboard',
            array( $this, 'render_admin_page' ),
            'dashicons-chart-area',
            3
        );
        
        // Nasconde i submenu, verranno gestiti dall'SPA Vue
        add_submenu_page(
            'erp-dashboard',
            __( 'Dashboard', 'erp-core' ),
            __( 'Dashboard', 'erp-core' ),
            'manage_options',
            'erp-dashboard'
        );
    }

    /**
     * Registra script e stili per l'admin
     */
    public function register_scripts( $hook ) {
        // Carica script e stili solo nelle pagine del plugin
        if ( strpos( $hook, 'erp-' ) === false ) {
            return;
        }
        
        // Registra lo stile principale
        wp_register_style(
            'erp-admin-style',
            ERP_CORE_ASSETS_URL . 'css/admin.css',
            array(),
            ERP_CORE_VERSION
        );
		
		// Registra Lucide Icons (da CDN)
		wp_register_script(
			'lucide-icons',
			'https://unpkg.com/lucide@latest',
			array(),
			ERP_CORE_VERSION,
			true
		);

		// Registra Lucide per Vue
		wp_register_script(
			'lucide-vue',
			'https://unpkg.com/lucide-vue-next@latest',
			array('lucide-icons', 'erp-admin-app'),
			ERP_CORE_VERSION,
			true
		);

		// Script di inizializzazione per Lucide
		wp_add_inline_script(
			'lucide-icons',
			'document.addEventListener("DOMContentLoaded", function() { if(typeof lucide !== "undefined") { lucide.createIcons(); } });'
		);		
		

        
        // Registra lo script Vue principale
        wp_register_script(
            'erp-admin-app',
            ERP_CORE_ASSETS_URL . 'js/app.js',
            array(),
            ERP_CORE_VERSION,
            true
        );
        
        // Passa dati a JavaScript
        wp_localize_script(
            'erp-admin-app',
            'erpCoreData',
            array(
                'apiUrl' => esc_url_raw( rest_url( 'erp/v1/' ) ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'adminUrl' => admin_url(),
                'modules' => ERP_Core_Modules::instance()->get_active_modules(),
                'currentUser' => $this->get_current_user_data(),
                'version' => ERP_CORE_VERSION,
                'themeMode' => get_user_meta( get_current_user_id(), 'erp_theme_mode', true ) ?: 'dark'
            )
        );
        
        // Carica gli stili e gli script
        wp_enqueue_style( 'erp-admin-style' );
        wp_enqueue_script( 'erp-admin-app' );
		
		// Carica Lucide Icons
		wp_enqueue_script( 'lucide-icons' );
		wp_enqueue_script( 'lucide-vue' );
    }

    /**
     * Renderizza la pagina admin principale (container per Vue SPA)
     */
    public function render_admin_page() {
        echo '<div id="erp-app" class="erp-app-container"></div>';
    }

    /**
     * Ottiene i dati dell'utente corrente per l'app Vue
     * @return array
     */
    private function get_current_user_data() {
        $current_user = wp_get_current_user();
        
        return array(
            'id' => $current_user->ID,
            'name' => $current_user->display_name,
            'email' => $current_user->user_email,
            'avatar' => get_avatar_url( $current_user->ID ),
            'permissions' => ERP_Core_ACL::instance()->get_user_permissions( $current_user->ID )
        );
    }
}

/**
 * Funzione principale per accedere all'istanza del plugin
 * @return ERP_Core
 */
function ERP_Core() {
    return ERP_Core::instance();
}

// Inizializza il plugin
ERP_Core();