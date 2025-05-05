<?php
/**
 * Gestione dei moduli ERP
 * 
 * Questa classe gestisce il caricamento e la configurazione dei moduli
 * che estendono le funzionalità dell'ERP Core.
 * 
 * @package ERP_Core
 */

// Impedisce l'accesso diretto
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ERP_Core_Modules {
    /**
     * Istanza singola della classe (singleton)
     * @var ERP_Core_Modules
     */
    private static $instance = null;

    /**
     * Array con i moduli caricati
     * @var array
     */
    private $modules = array();

    /**
     * Ottiene l'istanza singola della classe
     * @return ERP_Core_Modules
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
        // Singleton
    }

    /**
     * Inizializza il gestore dei moduli
     */
    public function init() {
        // Carica i moduli disponibili
        $this->load_modules();
        
        // Registra gli scripts e gli stili dei moduli
        add_action( 'admin_enqueue_scripts', array( $this, 'register_module_assets' ), 20 );
        
        // Applica filtri per eventuali personalizzazioni
        do_action( 'erp_core_modules_init', $this );
    }

    /**
     * Carica tutti i moduli disponibili
     */
    private function load_modules() {
        // Percorso dove si trovano i moduli
        $modules_dir = ERP_CORE_MODULES_PATH;
        
        // Verifica se la directory esiste
        if ( ! file_exists( $modules_dir ) || ! is_dir( $modules_dir ) ) {
            return;
        }
        
        // Legge le directory dei moduli
        $modules = array_filter( glob( $modules_dir . '*' ), 'is_dir' );
        
        foreach ( $modules as $module_path ) {
            $this->load_module( basename( $module_path ) );
        }
    }

    /**
     * Carica un singolo modulo
     * 
     * @param string $module_slug Slug del modulo
     * @return bool True se il modulo è stato caricato, false altrimenti
     */
    public function load_module( $module_slug ) {
        // Percorso del modulo
        $module_path = ERP_CORE_MODULES_PATH . $module_slug;
        
        // Verifica se il modulo esiste
        if ( ! file_exists( $module_path ) || ! is_dir( $module_path ) ) {
            return false;
        }
        
        // Verifica se esiste il file manifest.json
        $manifest_file = $module_path . '/manifest.json';
        if ( ! file_exists( $manifest_file ) ) {
            return false;
        }
        
        // Legge il manifest
        $manifest = json_decode( file_get_contents( $manifest_file ), true );
        if ( ! $manifest || ! isset( $manifest['name'] ) ) {
            return false;
        }
        
        // Verifica se il modulo è già caricato
        if ( isset( $this->modules[ $manifest['name'] ] ) ) {
            return true;
        }
        
        // Verifica dipendenze
        if ( isset( $manifest['requires'] ) && is_array( $manifest['requires'] ) ) {
            foreach ( $manifest['requires'] as $required_module ) {
                if ( ! isset( $this->modules[ $required_module ] ) ) {
                    // Tenta di caricare il modulo richiesto
                    if ( ! $this->load_module( $required_module ) ) {
                        // La dipendenza non può essere soddisfatta
                        return false;
                    }
                }
            }
        }
        
        // Carica il file principale del modulo
        $module_file = $module_path . '/module.php';
        if ( file_exists( $module_file ) ) {
            require_once $module_file;
        }
        
        // Aggiunge il modulo all'elenco
        $this->modules[ $manifest['name'] ] = array(
            'path' => $module_path,
            'url' => ERP_CORE_URL . 'modules/' . $module_slug,
            'manifest' => $manifest
        );
        
        // Esegue l'hook di inizializzazione
        do_action( 'erp_module_loaded', $manifest['name'], $manifest );
        do_action( 'erp_module_' . $manifest['name'] . '_loaded', $manifest );
        
        return true;
    }

    /**
     * Registra gli assets (CSS e JS) dei moduli
     * 
     * @param string $hook Hook corrente di WordPress
     */
    public function register_module_assets( $hook ) {
        // Esegui solo nelle pagine dell'ERP
        if ( strpos( $hook, 'erp-' ) === false ) {
            return;
        }
        
        foreach ( $this->modules as $module_slug => $module ) {
            // CSS del modulo
            $css_file = $module['path'] . '/assets/css/module.css';
            if ( file_exists( $css_file ) ) {
                wp_register_style(
                    'erp-module-' . $module_slug,
                    $module['url'] . '/assets/css/module.css',
                    array( 'erp-admin-style' ),
                    filemtime( $css_file )
                );
                wp_enqueue_style( 'erp-module-' . $module_slug );
            }
            
            // JS del modulo
            $js_file = $module['path'] . '/assets/js/module.js';
            if ( file_exists( $js_file ) ) {
                wp_register_script(
                    'erp-module-' . $module_slug,
                    $module['url'] . '/assets/js/module.js',
                    array( 'erp-admin-app' ),
                    filemtime( $js_file ),
                    true
                );
                wp_enqueue_script( 'erp-module-' . $module_slug );
            }
        }
    }

    /**
     * Ottiene le informazioni di un modulo
     * 
     * @param string $module_slug Slug del modulo
     * @return array|null Informazioni del modulo o null se non esiste
     */
    public function get_module( $module_slug ) {
        if ( isset( $this->modules[ $module_slug ] ) ) {
            return $this->modules[ $module_slug ];
        }
        
        return null;
    }

    /**
     * Ottiene tutti i moduli caricati
     * 
     * @return array Elenco dei moduli
     */
    public function get_all_modules() {
        return $this->modules;
    }

    /**
     * Ottiene i moduli attivi per l'uso nell'app Vue
     * 
     * @return array Array di moduli attivi con informazioni essenziali
     */
    public function get_active_modules() {
        $active_modules = array();
        
        foreach ( $this->modules as $module_slug => $module ) {
            // Estrae solo le informazioni necessarie per Vue
            $active_modules[ $module_slug ] = array(
                'name' => $module_slug,
                'title' => $module['manifest']['title'],
                'description' => isset( $module['manifest']['description'] ) ? $module['manifest']['description'] : '',
                'icon' => isset( $module['manifest']['icon'] ) ? $module['manifest']['icon'] : 'box',
                'route' => isset( $module['manifest']['route'] ) ? $module['manifest']['route'] : '/' . $module_slug,
                'permission_base' => isset( $module['manifest']['permission_base'] ) ? $module['manifest']['permission_base'] : 'erp_' . $module_slug,
                'tabs' => isset( $module['manifest']['tabs'] ) ? $module['manifest']['tabs'] : array(),
                'dashboard_cards' => isset( $module['manifest']['dashboard_cards'] ) ? $module['manifest']['dashboard_cards'] : array(),
                'reports' => isset( $module['manifest']['reports'] ) ? $module['manifest']['reports'] : array(),
            );
        }
        
        return $active_modules;
    }

    /**
     * Ottiene tutte le dashboard card da visualizzare
     * 
     * @return array Array di card per la dashboard
     */
    public function get_dashboard_cards() {
        $cards = array();
        
        foreach ( $this->modules as $module_slug => $module ) {
            if ( isset( $module['manifest']['dashboard_cards'] ) && is_array( $module['manifest']['dashboard_cards'] ) ) {
                foreach ( $module['manifest']['dashboard_cards'] as $card ) {
                    $cards[] = array(
                        'id' => $card['id'],
                        'label' => $card['label'],
                        'icon' => isset( $card['icon'] ) ? $card['icon'] : '',
                        'api' => isset( $card['api'] ) ? $card['api'] : '',
                        'permission' => isset( $card['permission'] ) ? $card['permission'] : '',
                        'module' => $module_slug
                    );
                }
            }
        }
        
        return $cards;
    }

    /**
     * Ottiene tutti i report disponibili
     * 
     * @return array Array di report disponibili
     */
    public function get_reports() {
        $reports = array();
        
        foreach ( $this->modules as $module_slug => $module ) {
            if ( isset( $module['manifest']['reports'] ) && is_array( $module['manifest']['reports'] ) ) {
                foreach ( $module['manifest']['reports'] as $report ) {
                    $reports[] = array(
                        'id' => $report['id'],
                        'label' => $report['label'],
                        'route' => isset( $report['route'] ) ? $report['route'] : '',
                        'permission' => isset( $report['permission'] ) ? $report['permission'] : '',
                        'component' => isset( $report['component'] ) ? $report['component'] : '',
                        'module' => $module_slug
                    );
                }
            }
        }
        
        return $reports;
    }

    /**
     * Verifica se un modulo è installato
     * 
     * @param string $module_slug Slug del modulo da verificare
     * @return bool True se il modulo è installato, false altrimenti
     */
    public function is_module_installed( $module_slug ) {
        return isset( $this->modules[ $module_slug ] );
    }

    /**
     * Registra un nuovo modulo
     * 
     * @param string $module_slug Slug del modulo
     * @param array  $manifest    Dati del manifest
     * @param string $path        Percorso del modulo
     */
    public function register_module( $module_slug, $manifest, $path ) {
        if ( ! isset( $this->modules[ $module_slug ] ) ) {
            $this->modules[ $module_slug ] = array(
                'path' => $path,
                'url' => plugins_url( '', $path ),
                'manifest' => $manifest
            );
        }
    }

    /**
     * Ottiene i componenti Vue dei moduli
     * 
     * @return array Array con tutti i componenti Vue
     */
    public function get_vue_components() {
        $components = array();
        
        foreach ( $this->modules as $module_slug => $module ) {
            // Ottieni il file entrypoint
            if ( isset( $module['manifest']['entrypoint'] ) ) {
                $entrypoint = $module['manifest']['entrypoint'];
                $components[ $module_slug ] = array(
                    'name' => $module_slug,
                    'path' => $module['url'] . '/vue/' . $entrypoint,
                    'route' => isset( $module['manifest']['route'] ) ? $module['manifest']['route'] : '/' . $module_slug
                );
            }
        }
        
        return $components;
    }

    /**
     * Registra le route dei moduli nell'app Vue
     * 
     * @return array Array con le route per Vue Router
     */
    public function get_vue_routes() {
        $routes = array();
        
        foreach ( $this->modules as $module_slug => $module ) {
            if ( isset( $module['manifest']['route'] ) ) {
                $route = $module['manifest']['route'];
                $component = isset( $module['manifest']['entrypoint'] ) ? str_replace( '.vue', '', $module['manifest']['entrypoint'] ) : $module_slug;
                
                $routes[] = array(
                    'path' => $route,
                    'component' => $component,
                    'meta' => array(
                        'title' => $module['manifest']['title'],
                        'module' => $module_slug,
                        'icon' => isset( $module['manifest']['icon'] ) ? $module['manifest']['icon'] : 'box',
                        'permission' => isset( $module['manifest']['permission_base'] ) ? $module['manifest']['permission_base'] . '_view' : 'erp_' . $module_slug . '_view'
                    )
                );
            }
        }
        
        return $routes;
    }
}
