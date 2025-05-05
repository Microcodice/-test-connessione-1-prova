<?php
/**
 * Gestione dei permessi e del sistema ACL
 * 
 * Questa classe implementa il sistema di Access Control List (ACL) per
 * controllare i permessi di accesso alle varie funzionalità dell'ERP.
 * 
 * @package ERP_Core
 */

// Impedisce l'accesso diretto
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ERP_Core_ACL {
    /**
     * Istanza singola della classe (singleton)
     * @var ERP_Core_ACL
     */
    private static $instance = null;

    /**
     * Array con i permessi registrati
     * @var array
     */
    private $permissions = array();

    /**
     * Array con i ruoli predefiniti
     * @var array
     */
    private $roles = array();

    /**
     * Ottiene l'istanza singola della classe
     * @return ERP_Core_ACL
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
     * Inizializza il sistema ACL
     */
    public function init() {
        // Registra i permessi base
        $this->register_default_permissions();
        
        // Registra i ruoli base
        $this->register_default_roles();
        
        // Applica filtri per eventuali personalizzazioni
        do_action( 'erp_core_acl_init', $this );
    }

    /**
     * Registra i permessi di default
     */
    private function register_default_permissions() {
        $this->register_permission( 'erp_dashboard_view', __( 'Visualizza Dashboard', 'erp-core' ), 'dashboard' );
        
        // Permessi per il modulo Prodotti
        $this->register_permission( 'erp_products_view', __( 'Visualizza Prodotti', 'erp-core' ), 'products' );
        $this->register_permission( 'erp_products_create', __( 'Crea Prodotti', 'erp-core' ), 'products' );
        $this->register_permission( 'erp_products_edit', __( 'Modifica Prodotti', 'erp-core' ), 'products' );
        $this->register_permission( 'erp_products_delete', __( 'Elimina Prodotti', 'erp-core' ), 'products' );
        
        // Permessi per il modulo Clienti
        $this->register_permission( 'erp_clients_view', __( 'Visualizza Clienti', 'erp-core' ), 'clients' );
        $this->register_permission( 'erp_clients_create', __( 'Crea Clienti', 'erp-core' ), 'clients' );
        $this->register_permission( 'erp_clients_edit', __( 'Modifica Clienti', 'erp-core' ), 'clients' );
        $this->register_permission( 'erp_clients_delete', __( 'Elimina Clienti', 'erp-core' ), 'clients' );
        
        // Permessi per il modulo Fornitori
        $this->register_permission( 'erp_suppliers_view', __( 'Visualizza Fornitori', 'erp-core' ), 'suppliers' );
        $this->register_permission( 'erp_suppliers_create', __( 'Crea Fornitori', 'erp-core' ), 'suppliers' );
        $this->register_permission( 'erp_suppliers_edit', __( 'Modifica Fornitori', 'erp-core' ), 'suppliers' );
        $this->register_permission( 'erp_suppliers_delete', __( 'Elimina Fornitori', 'erp-core' ), 'suppliers' );
        
        // Permessi per il modulo Documenti
        $this->register_permission( 'erp_documents_view', __( 'Visualizza Documenti', 'erp-core' ), 'documents' );
        $this->register_permission( 'erp_documents_create', __( 'Crea Documenti', 'erp-core' ), 'documents' );
        $this->register_permission( 'erp_documents_edit', __( 'Modifica Documenti', 'erp-core' ), 'documents' );
        $this->register_permission( 'erp_documents_delete', __( 'Elimina Documenti', 'erp-core' ), 'documents' );
        $this->register_permission( 'erp_documents_print', __( 'Stampa Documenti', 'erp-core' ), 'documents' );
        
        // Permessi per il modulo Magazzino
        $this->register_permission( 'erp_inventory_view', __( 'Visualizza Magazzino', 'erp-core' ), 'inventory' );
        $this->register_permission( 'erp_inventory_create', __( 'Crea Movimenti', 'erp-core' ), 'inventory' );
        $this->register_permission( 'erp_inventory_edit', __( 'Modifica Movimenti', 'erp-core' ), 'inventory' );
        $this->register_permission( 'erp_inventory_delete', __( 'Elimina Movimenti', 'erp-core' ), 'inventory' );
        
        // Permessi per il modulo Contabilità
        $this->register_permission( 'erp_accounting_view', __( 'Visualizza Contabilità', 'erp-core' ), 'accounting' );
        $this->register_permission( 'erp_accounting_create', __( 'Crea Registrazioni', 'erp-core' ), 'accounting' );
        $this->register_permission( 'erp_accounting_edit', __( 'Modifica Registrazioni', 'erp-core' ), 'accounting' );
        $this->register_permission( 'erp_accounting_delete', __( 'Elimina Registrazioni', 'erp-core' ), 'accounting' );
        
        // Permessi per il modulo WooCommerce
        $this->register_permission( 'erp_woocommerce_view', __( 'Visualizza Sincronizzazione', 'erp-core' ), 'woocommerce' );
        $this->register_permission( 'erp_woocommerce_sync', __( 'Esegui Sincronizzazione', 'erp-core' ), 'woocommerce' );
        $this->register_permission( 'erp_woocommerce_settings', __( 'Modifica Impostazioni', 'erp-core' ), 'woocommerce' );
        
        // Permessi per il modulo Report
        $this->register_permission( 'erp_reports_view', __( 'Visualizza Report', 'erp-core' ), 'reports' );
        $this->register_permission( 'erp_reports_export', __( 'Esporta Report', 'erp-core' ), 'reports' );
        
        // Permessi per il modulo Sistema
        $this->register_permission( 'erp_system_view', __( 'Visualizza Impostazioni', 'erp-core' ), 'system' );
        $this->register_permission( 'erp_system_edit', __( 'Modifica Impostazioni', 'erp-core' ), 'system' );
        $this->register_permission( 'erp_system_users', __( 'Gestione Utenti', 'erp-core' ), 'system' );
        $this->register_permission( 'erp_system_permissions', __( 'Gestione Permessi', 'erp-core' ), 'system' );
        $this->register_permission( 'erp_system_logs', __( 'Visualizza Log', 'erp-core' ), 'system' );
        
        // Permessi per il modulo Integrazioni
        $this->register_permission( 'erp_integrations_view', __( 'Visualizza Integrazioni', 'erp-core' ), 'integrations' );
        $this->register_permission( 'erp_integrations_edit', __( 'Modifica Integrazioni', 'erp-core' ), 'integrations' );
    }

    /**
     * Registra i ruoli predefiniti
     */
    private function register_default_roles() {
        // Ruolo ERP Admin (tutti i permessi)
        $this->roles['erp_admin'] = array(
            'name' => __( 'ERP Admin', 'erp-core' ),
            'permissions' => array_keys( $this->permissions )
        );
        
        // Ruolo ERP Magazzino
        $this->roles['erp_magazzino'] = array(
            'name' => __( 'ERP Magazzino', 'erp-core' ),
            'permissions' => array(
                'erp_dashboard_view',
                'erp_products_view',
                'erp_products_edit',
                'erp_inventory_view',
                'erp_inventory_create',
                'erp_inventory_edit'
            )
        );
        
        // Ruolo ERP Vendite
        $this->roles['erp_vendite'] = array(
            'name' => __( 'ERP Vendite', 'erp-core' ),
            'permissions' => array(
                'erp_dashboard_view',
                'erp_products_view',
                'erp_clients_view',
                'erp_clients_create',
                'erp_clients_edit',
                'erp_documents_view',
                'erp_documents_create',
                'erp_documents_print',
                'erp_reports_view'
            )
        );
        
        // Ruolo ERP Contabilità
        $this->roles['erp_contabilita'] = array(
            'name' => __( 'ERP Contabilità', 'erp-core' ),
            'permissions' => array(
                'erp_dashboard_view',
                'erp_clients_view',
                'erp_suppliers_view',
                'erp_documents_view',
                'erp_documents_print',
                'erp_accounting_view',
                'erp_accounting_create',
                'erp_accounting_edit',
                'erp_reports_view',
                'erp_reports_export'
            )
        );
    }

    /**
     * Registra un nuovo permesso
     * 
     * @param string $key        Chiave univoca del permesso
     * @param string $name       Nome descrittivo del permesso
     * @param string $module     Nome del modulo a cui appartiene
     * @param string $description Descrizione facoltativa del permesso
     */
    public function register_permission( $key, $name, $module, $description = '' ) {
        $this->permissions[ $key ] = array(
            'name' => $name,
            'module' => $module,
            'description' => $description
        );
    }

    /**
     * Registra un nuovo ruolo
     * 
     * @param string $key        Chiave univoca del ruolo
     * @param string $name       Nome descrittivo del ruolo
     * @param array  $permissions Array di permessi assegnati al ruolo
     */
    public function register_role( $key, $name, $permissions = array() ) {
        $this->roles[ $key ] = array(
            'name' => $name,
            'permissions' => $permissions
        );
    }

    /**
     * Verifica se un utente ha un determinato permesso
     * 
     * @param string $permission Nome del permesso da verificare
     * @param int    $user_id    ID dell'utente da verificare (default: utente corrente)
     * @return bool
     */
    public function user_has_permission( $permission, $user_id = 0 ) {
        if ( empty( $user_id ) ) {
            $user_id = get_current_user_id();
        }
        
        // Gli amministratori WordPress hanno sempre tutti i permessi
        if ( user_can( $user_id, 'administrator' ) ) {
            return true;
        }
        
        // Verifica nei permessi personalizzati
        $user_permissions = $this->get_user_permissions( $user_id );
        
        return in_array( $permission, $user_permissions );
    }

    /**
     * Ottiene tutti i permessi di un utente
     * 
     * @param int $user_id ID dell'utente (default: utente corrente)
     * @return array
     */
    public function get_user_permissions( $user_id = 0 ) {
        if ( empty( $user_id ) ) {
            $user_id = get_current_user_id();
        }
        
        // Gli amministratori WordPress hanno sempre tutti i permessi
        if ( user_can( $user_id, 'administrator' ) ) {
            return array_keys( $this->permissions );
        }
        
        // Recupera i ruoli dell'utente in WordPress
        $user = get_userdata( $user_id );
        if ( ! $user || ! $user->roles ) {
            return array();
        }
        
        // Trova i permessi associati ai ruoli
        $user_permissions = array();
        
        foreach ( $user->roles as $role ) {
            if ( isset( $this->roles[ $role ] ) && ! empty( $this->roles[ $role ]['permissions'] ) ) {
                $user_permissions = array_merge( $user_permissions, $this->roles[ $role ]['permissions'] );
            }
        }
        
        // Recupera eventuali permessi personalizzati
        $custom_permissions = get_user_meta( $user_id, 'erp_custom_permissions', true );
        if ( ! empty( $custom_permissions ) && is_array( $custom_permissions ) ) {
            $user_permissions = array_merge( $user_permissions, $custom_permissions );
        }
        
        return array_unique( $user_permissions );
    }

    /**
     * Assegna un permesso personalizzato a un utente
     * 
     * @param int    $user_id    ID dell'utente
     * @param string $permission Nome del permesso da assegnare
     * @return bool
     */
    public function add_user_permission( $user_id, $permission ) {
        if ( empty( $user_id ) || empty( $permission ) ) {
            return false;
        }
        
        // Verifica che il permesso esista
        if ( ! isset( $this->permissions[ $permission ] ) ) {
            return false;
        }
        
        // Recupera permessi esistenti
        $custom_permissions = get_user_meta( $user_id, 'erp_custom_permissions', true );
        if ( ! is_array( $custom_permissions ) ) {
            $custom_permissions = array();
        }
        
        // Aggiunge il permesso se non è già presente
        if ( ! in_array( $permission, $custom_permissions ) ) {
            $custom_permissions[] = $permission;
            update_user_meta( $user_id, 'erp_custom_permissions', $custom_permissions );
        }
        
        return true;
    }

    /**
     * Rimuove un permesso personalizzato da un utente
     * 
     * @param int    $user_id    ID dell'utente
     * @param string $permission Nome del permesso da rimuovere
     * @return bool
     */
    public function remove_user_permission( $user_id, $permission ) {
        if ( empty( $user_id ) || empty( $permission ) ) {
            return false;
        }
        
        // Recupera permessi esistenti
        $custom_permissions = get_user_meta( $user_id, 'erp_custom_permissions', true );
        if ( ! is_array( $custom_permissions ) ) {
            return false;
        }
        
        // Cerca il permesso nell'array
        $key = array_search( $permission, $custom_permissions );
        if ( $key !== false ) {
            unset( $custom_permissions[ $key ] );
            update_user_meta( $user_id, 'erp_custom_permissions', array_values( $custom_permissions ) );
            return true;
        }
        
        return false;
    }
    
    /**
     * Imposta tutti i permessi personalizzati per un utente
     * 
     * @param int   $user_id     ID dell'utente
     * @param array $permissions Array di permessi da assegnare
     * @return bool
     */
    public function set_user_permissions( $user_id, $permissions ) {
        if ( empty( $user_id ) || ! is_array( $permissions ) ) {
            return false;
        }
        
        // Filtra i permessi validi
        $valid_permissions = array();
        foreach ( $permissions as $permission ) {
            if ( isset( $this->permissions[ $permission ] ) ) {
                $valid_permissions[] = $permission;
            }
        }
        
        // Aggiorna i meta dell'utente
        update_user_meta( $user_id, 'erp_custom_permissions', $valid_permissions );
        
        return true;
    }
    
    /**
     * Ottiene tutti i permessi disponibili
     * 
     * @return array
     */
    public function get_all_permissions() {
        return $this->permissions;
    }
    
    /**
     * Ottiene tutti i permessi raggruppati per modulo
     * 
     * @return array
     */
    public function get_permissions_by_module() {
        $result = array();
        
        foreach ( $this->permissions as $key => $permission ) {
            $module = $permission['module'];
            
            if ( ! isset( $result[ $module ] ) ) {
                $result[ $module ] = array();
            }
            
            $result[ $module ][ $key ] = $permission;
        }
        
        return $result;
    }
    
    /**
     * Ottiene tutti i ruoli disponibili
     * 
     * @return array
     */
    public function get_all_roles() {
        return $this->roles;
    }
    
    /**
     * Ottiene i permessi associati a un ruolo
     * 
     * @param string $role Chiave del ruolo
     * @return array
     */
    public function get_role_permissions( $role ) {
        if ( isset( $this->roles[ $role ] ) && ! empty( $this->roles[ $role ]['permissions'] ) ) {
            return $this->roles[ $role ]['permissions'];
        }
        
        return array();
    }
    
    /**
     * Verifica se l'API ha il permesso di eseguire un'azione
     * 
     * @param string $permission Nome del permesso da verificare
     * @param string $api_key    Chiave API
     * @return bool
     */
    public function api_has_permission( $permission, $api_key ) {
        global $wpdb;
        
        // Verifica che la chiave API esista e sia attiva
        $api = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}erp_api_keys WHERE api_key = %s AND is_active = 1",
                $api_key
            )
        );
        
        if ( ! $api ) {
            return false;
        }
        
        // Aggiorna l'ultimo accesso
        $wpdb->update(
            $wpdb->prefix . 'erp_api_keys',
            array( 'last_access' => current_time( 'mysql' ) ),
            array( 'id' => $api->id )
        );
        
        // Se ha 'all' come permesso, ha accesso a tutto
        if ( $api->permissions === 'all' ) {
            return true;
        }
        
        // Controlla i permessi specifici
        $api_permissions = explode( ',', $api->permissions );
        
        // Se il permesso richiesto è generico (modulo_*), estrae il modulo
        if ( strpos( $permission, '_' ) !== false ) {
            list( $module, $action ) = explode( '_', $permission, 2 );
            
            // Se ha permesso sul modulo completo, concedi l'accesso
            if ( in_array( $module . '_*', $api_permissions ) ) {
                return true;
            }
        }
        
        // Altrimenti verifica il permesso specifico
        return in_array( $permission, $api_permissions );
    }
}
