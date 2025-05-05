<?php
/**
 * Entry point per il modulo Prodotti
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
 * Inizializza e registra il modulo con l'ERP Core
 */
class ERP_Prodotti_Module {

    /**
     * Istanza singleton
     *
     * @var ERP_Prodotti_Module
     */
    private static $instance = null;

    /**
     * Ottiene l'istanza singleton
     *
     * @return ERP_Prodotti_Module
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Costruttore della classe
     * Registra i hook necessari per inizializzare il modulo
     */
    private function __construct() {
        // Registra il modulo con l'ERP Core
        add_action('erp_core_init_modules', array($this, 'register_module'));
        
        // Registra gli scripts e gli stili
        add_action('admin_enqueue_scripts', array($this, 'register_assets'));
        
        // Inizializza le classi del modulo
        $this->includes();
    }

    /**
     * Registra il modulo con l'ERP Core
     */
    public function register_module() {
        // Verifica se la funzione esiste
        if (function_exists('erp_register_module')) {
            erp_register_module(array(
                'id'          => 'erp-prodotti',
                'title'       => __('Prodotti', 'erp-core'),
                'description' => __('Gestione prodotti, varianti e attributi', 'erp-core'),
                'icon'        => 'box',
                'menu_title'  => __('Prodotti', 'erp-core'),
                'capability'  => 'erp_prodotti_view',
                'position'    => 30,
                'main_menu'   => true,
                'modules'     => array(
                    'elenco' => array(
                        'title'      => __('Tutti i prodotti', 'erp-core'),
                        'capability' => 'erp_prodotti_view',
                        'icon'       => 'layers',
                        'url'        => 'admin.php?page=erp-dashboard#/prodotti/elenco'
                    ),
                    'categorie' => array(
                        'title'      => __('Categorie', 'erp-core'),
                        'capability' => 'erp_prodotti_view',
                        'icon'       => 'folder',
                        'url'        => 'admin.php?page=erp-dashboard#/prodotti/categorie'
                    ),
                    'attributi' => array(
                        'title'      => __('Attributi', 'erp-core'),
                        'capability' => 'erp_prodotti_view',
                        'icon'       => 'tag',
                        'url'        => 'admin.php?page=erp-dashboard#/prodotti/attributi'
                    ),
                    'varianti' => array(
                        'title'      => __('Varianti', 'erp-core'),
                        'capability' => 'erp_prodotti_view',
                        'icon'       => 'box',
                        'url'        => 'admin.php?page=erp-dashboard#/prodotti/varianti'
                    )
                )
            ));
        }
    }

    /**
     * Include i file necessari
     */
    private function includes() {
        // Include la classe principale
        require_once dirname(__FILE__) . '/includes/class-erp-prodotti.php';
        
        // Include la classe installer per l'attivazione
        require_once dirname(__FILE__) . '/includes/class-erp-prodotti-installer.php';
        
        // Registra l'attivazione del modulo
        register_activation_hook(__FILE__, array('ERP_Prodotti_Installer', 'install'));
    }

    /**
     * Registra gli assets (CSS e JS)
     */
    public function register_assets() {
        $screen = get_current_screen();
        
        // Carica gli stili e gli script solo nelle pagine dell'ERP
        if (strpos($screen->id, 'erp') !== false || strpos($screen->id, 'toplevel_page_erp-dashboard') !== false) {
            // Definisci l'URL base del modulo
            $base_url = plugins_url('', __FILE__);
            
            // Registra e carica gli stili CSS
            wp_register_style('erp-prodotti-styles', $base_url . '/assets/css/prodotti.css', array(), '1.0.0');
            wp_enqueue_style('erp-prodotti-styles');
            
            // Registra gli script JS (che saranno caricati tramite Vue)
            wp_register_script('erp-prodotti-scripts', $base_url . '/assets/js/prodotti-admin.js', array('jquery'), '1.0.0', true);
            
            // Aggiunge la configurazione per il modulo
            wp_localize_script('erp-prodotti-scripts', 'erpProdottiConfig', array(
                'apiUrl' => rest_url('erp/v1/prodotti'),
                'nonce'  => wp_create_nonce('wp_rest'),
                'i18n'   => array(
                    'errorLoading'       => __('Errore nel caricamento dei dati', 'erp-core'),
                    'productCreated'     => __('Prodotto creato con successo', 'erp-core'),
                    'productUpdated'     => __('Prodotto aggiornato con successo', 'erp-core'),
                    'productDeleted'     => __('Prodotto eliminato con successo', 'erp-core'),
                    'confirmDelete'      => __('Sei sicuro di voler eliminare questo prodotto?', 'erp-core'),
                    'deleteWarning'      => __('Questa azione non può essere annullata', 'erp-core'),
                )
            ));
            
            wp_enqueue_script('erp-prodotti-scripts');
        }
    }
}

// Inizializza il modulo
ERP_Prodotti_Module::get_instance();