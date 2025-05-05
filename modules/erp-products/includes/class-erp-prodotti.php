<?php
/**
 * Classe principale del modulo Prodotti
 *
 * @package ERP_Core
 * @subpackage ERP_Prodotti
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe principale del modulo Prodotti
 */
class ERP_Prodotti {

    /**
     * Istanza singleton
     *
     * @var ERP_Prodotti
     */
    private static $instance = null;

    /**
     * Percorso del modulo
     *
     * @var string
     */
    private $module_path;

    /**
     * URL del modulo
     *
     * @var string
     */
    private $module_url;

    /**
     * Costruttore della classe
     */
    private function __construct() {
        $this->module_path = ERP_CORE_MODULES_PATH . '/erp-prodotti';
        $this->module_url = ERP_CORE_MODULES_URL . '/erp-prodotti';

        $this->includes();
        $this->init_hooks();
    }

    /**
     * Ottiene l'istanza singleton
     *
     * @return ERP_Prodotti
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Include i file necessari
     *
     * @return void
     */
    private function includes() {
        // Include i file del modulo
        include_once $this->module_path . '/includes/class-erp-prodotti-installer.php';
        include_once $this->module_path . '/includes/post-types/class-erp-product-cpt.php';
        include_once $this->module_path . '/includes/taxonomies/class-erp-product-tax.php';
        include_once $this->module_path . '/includes/meta-boxes/class-erp-product-metabox.php';
        include_once $this->module_path . '/includes/woocommerce/class-erp-wc-integration.php';
        include_once $this->module_path . '/api/api.php';
        include_once $this->module_path . '/db/class-erp-prodotti-db.php';
    }

    /**
     * Inizializza gli hook
     *
     * @return void
     */
    private function init_hooks() {
        // Registra il modulo nell'ERP Core
        add_action('erp_modules_init', array($this, 'register_module'));
        
        // Carica il modulo
        add_action('erp_module_loaded_erp-prodotti', array($this, 'module_loaded'));
        
        // Registra gli assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Registra le API
        add_action('rest_api_init', array($this, 'register_api'));
    }

    /**
     * Registra il modulo nell'ERP Core
     *
     * @return void
     */
    public function register_module() {
        erp_register_module('erp-prodotti', array(
            'title'       => __('Prodotti', 'erp-core'),
            'description' => __('Gestione prodotti, varianti e attributi', 'erp-core'),
            'icon'        => 'box',
            'path'        => $this->module_path,
            'url'         => $this->module_url,
        ));
    }

    /**
     * Gestisce il caricamento del modulo
     *
     * @return void
     */
    public function module_loaded() {
        // Inizializza i post types
        $cpt = new ERP_Product_CPT();
        $cpt->init();
        
        // Inizializza le tassonomie
        $tax = new ERP_Product_Tax();
        $tax->init();
        
        // Inizializza i metabox
        $metabox = new ERP_Product_Metabox();
        $metabox->init();
        
        // Inizializza l'integrazione WooCommerce
        $wc = new ERP_WC_Integration();
        $wc->init();
        
        // Registra i dashboard cards
        $this->register_dashboard_cards();
    }

    /**
     * Registra le dashboard cards
     *
     * @return void
     */
    public function register_dashboard_cards() {
        $manifest = json_decode(file_get_contents($this->module_path . '/manifest.json'), true);
        
        if (isset($manifest['dashboard_cards']) && is_array($manifest['dashboard_cards'])) {
            foreach ($manifest['dashboard_cards'] as $card) {
                erp_register_dashboard_card($card);
            }
        }
    }

    /**
     * Carica gli script e gli stili
     *
     * @return void
     */
    public function enqueue_scripts() {
        $screen = get_current_screen();
        
        if (erp_is_admin_page()) {
            wp_enqueue_style('erp-prodotti-css', $this->module_url . '/assets/css/prodotti.css', array(), '1.0.0');
            wp_enqueue_script('erp-prodotti-admin', $this->module_url . '/assets/js/prodotti-admin.js', array('jquery'), '1.0.0', true);
        }
    }

    /**
     * Registra le API REST
     *
     * @return void
     */
    public function register_api() {
        $controller = new ERP_Prodotti_Controller();
        $controller->register_routes();
    }

    /**
     * Ottiene il percorso del modulo
     *
     * @return string
     */
    public function get_module_path() {
        return $this->module_path;
    }

    /**
     * Ottiene l'URL del modulo
     *
     * @return string
     */
    public function get_module_url() {
        return $this->module_url;
    }
}

// Inizializza il modulo
ERP_Prodotti::get_instance();
