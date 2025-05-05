<?php
/**
 * Classe Utility per funzioni condivise
 * 
 * Questa classe fornisce metodi di utilità condivisi tra le varie componenti
 * del plugin ERP Core.
 * 
 * @package ERP_Core
 */

// Impedisce l'accesso diretto
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ERP_Core_Utils {
    /**
     * Istanza singola della classe (singleton)
     * @var ERP_Core_Utils
     */
    private static $instance = null;

    /**
     * Ottiene l'istanza singola della classe
     * @return ERP_Core_Utils
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
     * Registra un evento nel log del sistema
     * 
     * @param string $event_type Tipo di evento
     * @param string $message    Messaggio descrittivo dell'evento
     * @param string $object_type Tipo di oggetto interessato (opzionale)
     * @param int    $object_id   ID dell'oggetto interessato (opzionale)
     * @param int    $user_id     ID dell'utente che ha generato l'evento (default: utente corrente)
     * @return int|bool ID del log inserito o false in caso di errore
     */
    public function log_event( $event_type, $message, $object_type = '', $object_id = 0, $user_id = 0 ) {
        global $wpdb;
        
        // Se l'utente non è specificato, usa l'utente corrente
        if ( empty( $user_id ) ) {
            $user_id = get_current_user_id();
        }
        
        // Inserisce il log nel database
        $result = $wpdb->insert(
            $wpdb->prefix . 'erp_event_log',
            array(
                'event_type'   => $event_type,
                'object_type'  => $object_type,
                'object_id'    => $object_id,
                'message'      => $message,
                'date_created' => current_time( 'mysql' ),
                'user_id'      => $user_id
            ),
            array( '%s', '%s', '%d', '%s', '%s', '%d' )
        );
        
        if ( $result ) {
            return $wpdb->insert_id;
        }
        
        return false;
    }

    /**
     * Sistema cache semplificato con transient WordPress
     * 
     * @param string $key   Chiave del cache
     * @param mixed  $value Valore da salvare
     * @param int    $ttl   Tempo di vita in secondi (default: 1 ora)
     */
    public function cache_set( $key, $value, $ttl = 3600 ) {
        $cache_key = 'erp_cache_' . md5( $key );
        set_transient( $cache_key, $value, $ttl );
    }

    /**
     * Recupera un valore dalla cache
     * 
     * @param string $key Chiave del cache
     * @return mixed Valore salvato o false se non presente
     */
    public function cache_get( $key ) {
        $cache_key = 'erp_cache_' . md5( $key );
        return get_transient( $cache_key );
    }

    /**
     * Elimina un valore dalla cache
     * 
     * @param string $key Chiave del cache
     * @return bool
     */
    public function cache_delete( $key ) {
        $cache_key = 'erp_cache_' . md5( $key );
        return delete_transient( $cache_key );
    }

    /**
     * Pulisce tutta la cache ERP Core
     * 
     * @return void
     */
    public function cache_flush_all() {
        global $wpdb;
        
        // Ottiene tutte le chiavi di transient che iniziano con erp_cache_
        $transients = $wpdb->get_col(
            "SELECT option_name FROM $wpdb->options 
            WHERE option_name LIKE '_transient_erp_cache_%' 
            OR option_name LIKE '_transient_timeout_erp_cache_%'"
        );
        
        // Elimina ogni transient
        foreach ( $transients as $transient ) {
            if ( strpos( $transient, '_transient_timeout_' ) === 0 ) {
                $transient_name = substr( $transient, strlen( '_transient_timeout_' ) );
                delete_transient( $transient_name );
            } elseif ( strpos( $transient, '_transient_' ) === 0 ) {
                $transient_name = substr( $transient, strlen( '_transient_' ) );
                delete_transient( $transient_name );
            }
        }
    }

    /**
     * Formatta un numero come valuta
     * 
     * @param float  $amount   Importo da formattare
     * @param string $currency Codice valuta (default: EUR)
     * @return string
     */
    public function format_currency( $amount, $currency = 'EUR' ) {
        $currencies = array(
            'EUR' => array( 'symbol' => '€', 'position' => 'right', 'decimals' => 2, 'thousand_sep' => '.', 'decimal_sep' => ',' ),
            'USD' => array( 'symbol' => '$', 'position' => 'left', 'decimals' => 2, 'thousand_sep' => ',', 'decimal_sep' => '.' ),
            'GBP' => array( 'symbol' => '£', 'position' => 'left', 'decimals' => 2, 'thousand_sep' => ',', 'decimal_sep' => '.' ),
        );
        
        // Impostazioni di default per EUR (se la valuta non è supportata)
        $settings = isset( $currencies[ $currency ] ) ? $currencies[ $currency ] : $currencies['EUR'];
        
        // Applica impostazioni personalizzate se definite
        $settings = apply_filters( 'erp_currency_format_settings', $settings, $currency );
        
        // Formatta il numero
        $formatted = number_format( 
            (float) $amount, 
            $settings['decimals'], 
            $settings['decimal_sep'], 
            $settings['thousand_sep'] 
        );
        
        // Posiziona il simbolo
        if ( $settings['position'] === 'left' ) {
            return $settings['symbol'] . $formatted;
        } else {
            return $formatted . $settings['symbol'];
        }
    }

    /**
     * Formatta una data secondo le impostazioni ERP
     * 
     * @param string $date      Data da formattare (formato MySQL)
     * @param bool   $with_time Se includere l'ora (default: false)
     * @return string
     */
    public function format_date( $date, $with_time = false ) {
        if ( empty( $date ) ) {
            return '';
        }
        
        // Formato data di default
        $date_format = get_option( 'erp_date_format', 'd/m/Y' );
        
        // Formatta la data (e ora se richiesto)
        if ( $with_time ) {
            $time_format = get_option( 'erp_time_format', 'H:i' );
            return date_i18n( $date_format . ' ' . $time_format, strtotime( $date ) );
        } else {
            return date_i18n( $date_format, strtotime( $date ) );
        }
    }

    /**
     * Genera un codice univoco per documenti o identificativi
     * 
     * @param string $prefix Prefisso da aggiungere
     * @param int    $length Lunghezza del codice (escluso il prefisso)
     * @return string
     */
    public function generate_unique_code( $prefix = '', $length = 8 ) {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        
        for ( $i = 0; $i < $length; $i++ ) {
            $code .= $chars[ rand( 0, strlen( $chars ) - 1 ) ];
        }
        
        return $prefix . $code;
    }

    /**
     * Registra la richiesta o risposta di un webhook
     * 
     * @param string $webhook_id ID del webhook
     * @param string $direction  Direzione ('in' per ricevuto, 'out' per inviato)
     * @param string $url        URL del webhook (per direzione 'out')
     * @param mixed  $data       Dati inviati o ricevuti
     * @param mixed  $response   Risposta ricevuta (solo per direzione 'out')
     * @param int    $status_code Codice di stato HTTP
     * @return int|bool ID del log inserito o false in caso di errore
     */
    public function log_webhook( $webhook_id, $direction, $url = '', $data = '', $response = '', $status_code = 0 ) {
        global $wpdb;
        
        // Converte gli array in JSON
        if ( is_array( $data ) || is_object( $data ) ) {
            $data = wp_json_encode( $data );
        }
        
        if ( is_array( $response ) || is_object( $response ) ) {
            $response = wp_json_encode( $response );
        }
        
        // Inserisce il log nel database
        $result = $wpdb->insert(
            $wpdb->prefix . 'erp_webhook_log',
            array(
                'webhook_id'    => $webhook_id,
                'direction'     => $direction,
                'url'           => $url,
                'data'          => $data,
                'response'      => $response,
                'status_code'   => $status_code,
                'date_created'  => current_time( 'mysql' )
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
        );
        
        if ( $result ) {
            return $wpdb->insert_id;
        }
        
        return false;
    }

    /**
     * Pulizia periodica dei log e dei file temporanei
     * 
     * @return void
     */
    public function cleanup_old_data() {
        global $wpdb;
        
        // Ottieni la configurazione per il periodo di conservazione
        $log_retention_days = get_option( 'erp_log_retention_days', 30 );
        
        // Calcola la data limite
        $date_limit = date( 'Y-m-d H:i:s', strtotime( "-{$log_retention_days} days" ) );
        
        // Elimina i log degli eventi più vecchi
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}erp_event_log WHERE date_created < %s",
            $date_limit
        ) );
        
        // Elimina i log dei webhook più vecchi
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}erp_webhook_log WHERE date_created < %s",
            $date_limit
        ) );
        
        // Pulisci la cartella dei file temporanei
        $temp_dir = wp_upload_dir()['basedir'] . '/erp-core/temp';
        if ( file_exists( $temp_dir ) && is_dir( $temp_dir ) ) {
            $files = glob( $temp_dir . '/*' );
            $now = time();
            
            foreach ( $files as $file ) {
                // Salta index.php e cartelle
                if ( basename( $file ) === 'index.php' || is_dir( $file ) ) {
                    continue;
                }
                
                // Elimina file più vecchi di X giorni
                if ( $now - filemtime( $file ) >= $log_retention_days * 86400 ) {
                    @unlink( $file );
                }
            }
        }
    }

    /**
     * Registra gli hook per la pulizia programmata
     */
    public function register_cleanup_cron() {
        // Registra l'evento se non è già programmato
        if ( ! wp_next_scheduled( 'erp_cleanup_old_data' ) ) {
            wp_schedule_event( time(), 'daily', 'erp_cleanup_old_data' );
        }
        
        // Aggiungi il callback per l'azione
        add_action( 'erp_cleanup_old_data', array( $this, 'cleanup_old_data' ) );
    }

    /**
     * Sanitizza un array ricorsivamente
     * 
     * @param array $array Array da sanitizzare
     * @return array
     */
    public function sanitize_array( $array ) {
        if ( ! is_array( $array ) ) {
            return sanitize_text_field( $array );
        }
        
        foreach ( $array as $key => $value ) {
            if ( is_array( $value ) ) {
                $array[ $key ] = $this->sanitize_array( $value );
            } else {
                $array[ $key ] = sanitize_text_field( $value );
            }
        }
        
        return $array;
    }
}
