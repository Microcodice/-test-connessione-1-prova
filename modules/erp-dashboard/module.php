<?php
/**
 * Modulo Dashboard dell'ERP Core
 * 
 * Questo modulo gestisce la dashboard principale dell'ERP,
 * fornendo API e funzionalità di visualizzazione delle statistiche.
 * 
 * @package ERP_Core
 * @subpackage Modules/Dashboard
 */

// Impedisce l'accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

class ERP_Dashboard_Module {
    /**
     * Istanza singola della classe (singleton)
     * @var ERP_Dashboard_Module
     */
    private static $instance = null;

    /**
     * Ottiene l'istanza singola della classe
     * @return ERP_Dashboard_Module
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Costruttore della classe
     */
    private function __construct() {
        // Inizializza il modulo
        add_action('init', array($this, 'init'));
        
        // Registra le API
        add_action('erp_register_api_routes', array($this, 'register_api_routes'));
        
        // Registra gli assets del modulo
        add_action('admin_enqueue_scripts', array($this, 'register_assets'), 30);
    }

    /**
     * Inizializza il modulo
     */
    public function init() {
        // Aggiunge le card della dashboard
        add_filter('erp_dashboard_cards', array($this, 'register_dashboard_cards'));
    }

    /**
     * Registra gli asset del modulo (CSS e JS)
     * 
     * @param string $hook Hook corrente di WordPress
     */
    public function register_assets($hook) {
        // Verifica che siamo nella pagina dell'ERP
        if (strpos($hook, 'erp-') === false) {
            return;
        }
        
        // Carica gli stili del modulo
        wp_register_style(
            'erp-dashboard-module-style',
            plugin_dir_url(__FILE__) . 'assets/css/module.css',
            array('erp-admin-style'),
            filemtime(plugin_dir_path(__FILE__) . 'assets/css/module.css')
        );
        
        // Carica gli script del modulo
        wp_register_script(
            'erp-dashboard-module-script',
            plugin_dir_url(__FILE__) . 'assets/js/module.js',
            array('erp-admin-app'),
            filemtime(plugin_dir_path(__FILE__) . 'assets/js/module.js'),
            true
        );
        
        // Enqueue degli asset
        wp_enqueue_style('erp-dashboard-module-style');
        wp_enqueue_script('erp-dashboard-module-script');
        
        // Debug per verificare che gli asset vengano caricati
        error_log('ERP Dashboard Module: Assets registrati su hook ' . $hook);
    }

    /**
     * Registra le rotte delle API
     * 
     * @param string $namespace Namespace delle API
     */
    public function register_api_routes($namespace) {
        // API per conteggi
        register_rest_route($namespace, '/dashboard/count/products', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_products_count'),
            'permission_callback' => array($this, 'check_permission'),
        ));
        
        register_rest_route($namespace, '/dashboard/count/clients', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_clients_count'),
            'permission_callback' => array($this, 'check_permission'),
        ));
        
        register_rest_route($namespace, '/dashboard/count/suppliers', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_suppliers_count'),
            'permission_callback' => array($this, 'check_permission'),
        ));
        
        register_rest_route($namespace, '/dashboard/count/pending-orders', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_pending_orders_count'),
            'permission_callback' => array($this, 'check_permission'),
        ));
        
        // API per valori
        register_rest_route($namespace, '/dashboard/value/inventory', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_inventory_value'),
            'permission_callback' => array($this, 'check_permission'),
        ));
        
        register_rest_route($namespace, '/dashboard/value/monthly-revenue', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_monthly_revenue'),
            'permission_callback' => array($this, 'check_permission'),
        ));
    }

    /**
     * Verifica i permessi per accedere alle API
     * 
     * @param WP_REST_Request $request Oggetto richiesta
     * @return bool
     */
    public function check_permission($request) {
        // Per ora, permetti l'accesso se l'utente è loggato
        return is_user_logged_in();
    }

    /**
     * Registra le card per la dashboard
     * 
     * @param array $cards Array di card esistenti
     * @return array Array aggiornato di card
     */
    public function register_dashboard_cards($cards) {
        // Ottieni il manifest del modulo
        $manifest_file = plugin_dir_path(__FILE__) . 'manifest.json';
        
        if (file_exists($manifest_file)) {
            $manifest = json_decode(file_get_contents($manifest_file), true);
            
            if (isset($manifest['dashboard_cards']) && is_array($manifest['dashboard_cards'])) {
                // Aggiungi le card dal manifest
                foreach ($manifest['dashboard_cards'] as $card) {
                    $cards[] = $card;
                }
            }
        }
        
        return $cards;
    }

    /**
     * Implementazione delle API
     */
    
    /**
     * Callback per il conteggio dei prodotti
     * 
     * @param WP_REST_Request $request Oggetto richiesta
     * @return WP_REST_Response
     */
    public function get_products_count($request) {
        // Dati di esempio per la versione iniziale
        return rest_ensure_response(1254);
    }

    /**
     * Callback per il conteggio dei clienti
     * 
     * @param WP_REST_Request $request Oggetto richiesta
     * @return WP_REST_Response
     */
    public function get_clients_count($request) {
        // Dati di esempio per la versione iniziale
        return rest_ensure_response(857);
    }

    /**
     * Callback per il conteggio dei fornitori
     * 
     * @param WP_REST_Request $request Oggetto richiesta
     * @return WP_REST_Response
     */
    public function get_suppliers_count($request) {
        // Dati di esempio per la versione iniziale
        return rest_ensure_response(54);
    }

    /**
     * Callback per il conteggio degli ordini in attesa
     * 
     * @param WP_REST_Request $request Oggetto richiesta
     * @return WP_REST_Response
     */
    public function get_pending_orders_count($request) {
        // Dati di esempio per la versione iniziale
        return rest_ensure_response(126);
    }

    /**
     * Callback per il valore del magazzino
     * 
     * @param WP_REST_Request $request Oggetto richiesta
     * @return WP_REST_Response
     */
    public function get_inventory_value($request) {
        // Dati di esempio per la versione iniziale
        return rest_ensure_response('€75.430');
    }

    /**
     * Callback per il fatturato mensile
     * 
     * @param WP_REST_Request $request Oggetto richiesta
     * @return WP_REST_Response
     */
    public function get_monthly_revenue($request) {
        // Dati di esempio per la versione iniziale
        return rest_ensure_response('€24.530');
    }
}

// Inizializza il modulo
ERP_Dashboard_Module::instance();