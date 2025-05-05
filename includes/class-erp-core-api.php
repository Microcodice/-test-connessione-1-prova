<?php
/**
 * Gestione delle API REST
 * 
 * Questa classe gestisce le API REST dell'ERP Core, permettendo
 * l'interazione con i dati tramite richieste HTTP.
 * 
 * @package ERP_Core
 */

// Impedisce l'accesso diretto
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ERP_Core_API {
    /**
     * Istanza singola della classe (singleton)
     * @var ERP_Core_API
     */
    private static $instance = null;

    /**
     * Namespace delle API
     * @var string
     */
    private $namespace = 'erp/v1';

    /**
     * Ottiene l'istanza singola della classe
     * @return ERP_Core_API
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
     * Inizializza le API REST
     */
    public function init() {
        // Registra gli endpoint delle API
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
        
        // Aggiungi supporto per autenticazione con chiave API
        add_filter( 'determine_current_user', array( $this, 'authenticate_api_key' ), 20 );
        
        // Applica filtri per eventuali personalizzazioni
        do_action( 'erp_core_api_init', $this );
    }

    /**
     * Registra le route delle API
     */
    public function register_routes() {
        // API Dashboard
        register_rest_route( $this->namespace, '/dashboard/cards', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_dashboard_cards' ),
            'permission_callback' => array( $this, 'check_dashboard_permission' ),
        ) );
        
        // API Sistema
        register_rest_route( $this->namespace, '/system/info', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_system_info' ),
            'permission_callback' => array( $this, 'check_system_permission' ),
        ) );
        
        // API Utenti
        register_rest_route( $this->namespace, '/users', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_users' ),
            'permission_callback' => array( $this, 'check_users_permission' ),
        ) );
        
        // API Permessi
        register_rest_route( $this->namespace, '/permissions', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_permissions' ),
            'permission_callback' => array( $this, 'check_users_permission' ),
        ) );
        
        // API Moduli
        register_rest_route( $this->namespace, '/modules', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_modules' ),
            'permission_callback' => array( $this, 'check_modules_permission' ),
        ) );
        
        // API Log
        register_rest_route( $this->namespace, '/logs', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_logs' ),
            'permission_callback' => array( $this, 'check_system_permission' ),
        ) );
        
        // Permette ai moduli di registrare le proprie API
        do_action( 'erp_register_api_routes', $this->namespace );
    }

    /**
     * Callback per ottenere le card della dashboard
     * 
     * @param WP_REST_Request $request Oggetto richiesta
     * @return WP_REST_Response Risposta API
     */
    public function get_dashboard_cards( $request ) {
        $cards = ERP_Core_Modules::instance()->get_dashboard_cards();
        
        // Filtra le card in base ai permessi dell'utente
        $filtered_cards = array();
        foreach ( $cards as $card ) {
            if ( empty( $card['permission'] ) || ERP_Core_ACL::instance()->user_has_permission( $card['permission'] ) ) {
                $filtered_cards[] = $card;
            }
        }
        
        return rest_ensure_response( $filtered_cards );
    }

    /**
     * Callback per ottenere informazioni di sistema
     * 
     * @param WP_REST_Request $request Oggetto richiesta
     * @return WP_REST_Response Risposta API
     */
    public function get_system_info( $request ) {
        global $wpdb;
        
        // Raccogli informazioni sul sistema
        $system_info = array(
            'erp_version' => ERP_CORE_VERSION,
            'wordpress_version' => get_bloginfo( 'version' ),
            'php_version' => phpversion(),
            'mysql_version' => $wpdb->db_version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'],
            'memory_limit' => ini_get( 'memory_limit' ),
            'max_execution_time' => ini_get( 'max_execution_time' ),
            'upload_max_filesize' => ini_get( 'upload_max_filesize' ),
            'post_max_size' => ini_get( 'post_max_size' ),
            'max_input_vars' => ini_get( 'max_input_vars' ),
            'timezone' => get_option( 'timezone_string' ),
            'active_modules' => count( ERP_Core_Modules::instance()->get_all_modules() )
        );
        
        return rest_ensure_response( $system_info );
    }

    /**
     * Callback per ottenere gli utenti
     * 
     * @param WP_REST_Request $request Oggetto richiesta
     * @return WP_REST_Response Risposta API
     */
    public function get_users( $request ) {
        $args = array(
            'number' => $request->get_param( 'per_page' ) ? $request->get_param( 'per_page' ) : 20,
            'paged' => $request->get_param( 'page' ) ? $request->get_param( 'page' ) : 1,
            'role__in' => array( 'administrator', 'erp_admin', 'erp_magazzino', 'erp_vendite', 'erp_contabilita' )
        );
        
        // Cerca per nome o email
        if ( $request->get_param( 'search' ) ) {
            $args['search'] = '*' . $request->get_param( 'search' ) . '*';
        }
        
        // Ottieni gli utenti
        $user_query = new WP_User_Query( $args );
        $users = array();
        
        foreach ( $user_query->get_results() as $user ) {
            $users[] = array(
                'id' => $user->ID,
                'username' => $user->user_login,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'role' => reset( $user->roles ),
                'avatar' => get_avatar_url( $user->ID )
            );
        }
        
        return rest_ensure_response( array(
            'users' => $users,
            'total' => $user_query->get_total()
        ) );
    }

    /**
     * Callback per ottenere i permessi
     * 
     * @param WP_REST_Request $request Oggetto richiesta
     * @return WP_REST_Response Risposta API
     */
    public function get_permissions( $request ) {
        $permissions = ERP_Core_ACL::instance()->get_permissions_by_module();
        
        // Se è specificato un utente, restituisci i suoi permessi
        if ( $request->get_param( 'user_id' ) ) {
            $user_id = intval( $request->get_param( 'user_id' ) );
            $user_permissions = ERP_Core_ACL::instance()->get_user_permissions( $user_id );
            
            return rest_ensure_response( array(
                'permissions' => $permissions,
                'user_permissions' => $user_permissions
            ) );
        }
        
        return rest_ensure_response( $permissions );
    }

    /**
     * Callback per ottenere i moduli
     * 
     * @param WP_REST_Request $request Oggetto richiesta
     * @return WP_REST_Response Risposta API
     */
    public function get_modules( $request ) {
        $modules = ERP_Core_Modules::instance()->get_active_modules();
        
        return rest_ensure_response( $modules );
    }

    /**
     * Callback per ottenere i log del sistema
     * 
     * @param WP_REST_Request $request Oggetto richiesta
     * @return WP_REST_Response Risposta API
     */
    public function get_logs( $request ) {
        global $wpdb;
        
        $limit = $request->get_param( 'per_page' ) ? intval( $request->get_param( 'per_page' ) ) : 50;
        $page = $request->get_param( 'page' ) ? intval( $request->get_param( 'page' ) ) : 1;
        $offset = ( $page - 1 ) * $limit;
        
        // Filtri
        $where = '';
        $params = array();
        
        if ( $request->get_param( 'event_type' ) ) {
            $where .= ' AND event_type = %s';
            $params[] = $request->get_param( 'event_type' );
        }
        
        if ( $request->get_param( 'user_id' ) ) {
            $where .= ' AND user_id = %d';
            $params[] = intval( $request->get_param( 'user_id' ) );
        }
        
        if ( $request->get_param( 'date_from' ) ) {
            $where .= ' AND date_created >= %s';
            $params[] = $request->get_param( 'date_from' ) . ' 00:00:00';
        }
        
        if ( $request->get_param( 'date_to' ) ) {
            $where .= ' AND date_created <= %s';
            $params[] = $request->get_param( 'date_to' ) . ' 23:59:59';
        }
        
        // Query per contare il totale
        $count_query = "SELECT COUNT(*) FROM {$wpdb->prefix}erp_event_log WHERE 1=1" . $where;
        $total = $wpdb->get_var( $wpdb->prepare( $count_query, $params ) );
        
        // Query per i log
        $query = "SELECT * FROM {$wpdb->prefix}erp_event_log WHERE 1=1" . $where . " ORDER BY date_created DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        $logs = $wpdb->get_results( $wpdb->prepare( $query, $params ) );
        
        // Aggiunge le informazioni sugli utenti
        foreach ( $logs as &$log ) {
            $user = get_userdata( $log->user_id );
            $log->user_name = $user ? $user->display_name : __( 'Utente cancellato', 'erp-core' );
        }
        
        return rest_ensure_response( array(
            'logs' => $logs,
            'total' => $total
        ) );
    }

    /**
     * Verifica il permesso per la dashboard
     * 
     * @param WP_REST_Request $request Oggetto richiesta
     * @return bool
     */
    public function check_dashboard_permission( $request ) {
        return ERP_Core_ACL::instance()->user_has_permission( 'erp_dashboard_view' );
    }

    /**
     * Verifica il permesso per le impostazioni di sistema
     * 
     * @param WP_REST_Request $request Oggetto richiesta
     * @return bool
     */
    public function check_system_permission( $request ) {
        return ERP_Core_ACL::instance()->user_has_permission( 'erp_system_view' );
    }

    /**
     * Verifica il permesso per la gestione utenti
     * 
     * @param WP_REST_Request $request Oggetto richiesta
     * @return bool
     */
    public function check_users_permission( $request ) {
        return ERP_Core_ACL::instance()->user_has_permission( 'erp_system_users' );
    }

    /**
     * Verifica il permesso per la gestione moduli
     * 
     * @param WP_REST_Request $request Oggetto richiesta
     * @return bool
     */
    public function check_modules_permission( $request ) {
        return ERP_Core_ACL::instance()->user_has_permission( 'erp_system_view' );
    }

    /**
     * Autenticazione con chiave API
     * 
     * @param int|bool $user_id ID utente corrente
     * @return int|bool ID utente se autenticato, false altrimenti
     */
    public function authenticate_api_key( $user_id ) {
        // Se l'utente è già autenticato, non fare nulla
        if ( $user_id ) {
            return $user_id;
        }
        
        // Verifica se è una richiesta REST API
        if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
            return $user_id;
        }
        
        // Ottieni il token dall'header Authorization
        $auth_header = isset( $_SERVER['HTTP_AUTHORIZATION'] ) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
        
        // Formato: Authorization: Bearer <token>
        if ( ! empty( $auth_header ) && preg_match( '/Bearer\s(\S+)/', $auth_header, $matches ) ) {
            $api_key = $matches[1];
            
            // Cerca la chiave nel database
            global $wpdb;
            
            $api = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}erp_api_keys WHERE api_key = %s AND is_active = 1",
                    $api_key
                )
            );
            
            if ( $api ) {
                // Aggiorna l'ultimo accesso
                $wpdb->update(
                    $wpdb->prefix . 'erp_api_keys',
                    array( 'last_access' => current_time( 'mysql' ) ),
                    array( 'id' => $api->id )
                );
                
                // Registra l'accesso nel log
                ERP_Core_Utils::instance()->log_event(
                    'api_access',
                    sprintf( __( 'Accesso API con chiave %s', 'erp-core' ), substr( $api_key, 0, 8 ) . '...' ),
                    'api_key',
                    $api->id,
                    $api->user_id
                );
                
                // Restituisci l'ID utente associato alla chiave
                return $api->user_id;
            }
        }
        
        return $user_id;
    }

    /**
     * Formatta una risposta di errore
     * 
     * @param string $code    Codice di errore
     * @param string $message Messaggio di errore
     * @param int    $status  Codice di stato HTTP
     * @return WP_REST_Response Risposta di errore
     */
    public function error_response( $code, $message, $status = 400 ) {
        return new WP_REST_Response( array(
            'success' => false,
            'error' => array(
                'code' => $code,
                'message' => $message
            )
        ), $status );
    }

    /**
     * Formatta una risposta di successo
     * 
     * @param mixed $data    Dati da restituire
     * @param string $message Messaggio di successo
     * @return WP_REST_Response Risposta di successo
     */
    public function success_response( $data, $message = '' ) {
        $response = array(
            'success' => true,
            'data' => $data
        );
        
        if ( ! empty( $message ) ) {
            $response['message'] = $message;
        }
        
        return new WP_REST_Response( $response, 200 );
    }
}