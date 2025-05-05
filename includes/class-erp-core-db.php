<?php
/**
 * Gestione del database
 * 
 * Questa classe gestisce le operazioni sul database, fornendo metodi
 * per interagire con le tabelle personalizzate dell'ERP.
 * 
 * @package ERP_Core
 */

// Impedisce l'accesso diretto
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ERP_Core_DB {
    /**
     * Istanza singola della classe (singleton)
     * @var ERP_Core_DB
     */
    private static $instance = null;

    /**
     * Ottiene l'istanza singola della classe
     * @return ERP_Core_DB
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
     * Ottiene la configurazione per una tabella
     * 
     * @param string $table Nome della tabella (senza prefisso)
     * @return array Configurazione della tabella
     */
    public function get_table_config( $table ) {
        global $wpdb;
        
        $tables = array(
            'erp_movements' => array(
                'primary_key' => 'id',
                'table_name' => $wpdb->prefix . 'erp_movements',
                'fields' => array(
                    'id' => '%d',
                    'product_id' => '%d',
                    'variant_id' => '%d',
                    'document_id' => '%d',
                    'movement_type' => '%s',
                    'quantity' => '%f',
                    'unit_price' => '%f',
                    'date_created' => '%s',
                    'note' => '%s',
                    'user_id' => '%d'
                )
            ),
            'erp_variants' => array(
                'primary_key' => 'id',
                'table_name' => $wpdb->prefix . 'erp_variants',
                'fields' => array(
                    'id' => '%d',
                    'product_id' => '%d',
                    'sku' => '%s',
                    'price' => '%f',
                    'stock_quantity' => '%f',
                    'attributes' => '%s',
                    'woocommerce_variation_id' => '%d',
                    'is_active' => '%d',
                    'date_created' => '%s',
                    'date_updated' => '%s'
                )
            ),
            'erp_documents_head' => array(
                'primary_key' => 'id',
                'table_name' => $wpdb->prefix . 'erp_documents_head',
                'fields' => array(
                    'id' => '%d',
                    'document_type' => '%s',
                    'document_number' => '%s',
                    'document_date' => '%s',
                    'client_id' => '%d',
                    'supplier_id' => '%d',
                    'total_amount' => '%f',
                    'tax_amount' => '%f',
                    'status' => '%s',
                    'notes' => '%s',
                    'date_created' => '%s',
                    'date_updated' => '%s',
                    'user_id' => '%d'
                )
            ),
            'erp_documents_rows' => array(
                'primary_key' => 'id',
                'table_name' => $wpdb->prefix . 'erp_documents_rows',
                'fields' => array(
                    'id' => '%d',
                    'document_id' => '%d',
                    'product_id' => '%d',
                    'variant_id' => '%d',
                    'description' => '%s',
                    'quantity' => '%f',
                    'unit_price' => '%f',
                    'discount_percent' => '%f',
                    'tax_percent' => '%f',
                    'row_total' => '%f',
                    'sort_order' => '%d'
                )
            ),
            'erp_documents_log' => array(
                'primary_key' => 'id',
                'table_name' => $wpdb->prefix . 'erp_documents_log',
                'fields' => array(
                    'id' => '%d',
                    'document_id' => '%d',
                    'action' => '%s',
                    'data' => '%s',
                    'date_created' => '%s',
                    'user_id' => '%d'
                )
            ),
            'erp_event_log' => array(
                'primary_key' => 'id',
                'table_name' => $wpdb->prefix . 'erp_event_log',
                'fields' => array(
                    'id' => '%d',
                    'event_type' => '%s',
                    'object_type' => '%s',
                    'object_id' => '%d',
                    'message' => '%s',
                    'date_created' => '%s',
                    'user_id' => '%d'
                )
            ),
            'erp_api_keys' => array(
                'primary_key' => 'id',
                'table_name' => $wpdb->prefix . 'erp_api_keys',
                'fields' => array(
                    'id' => '%d',
                    'api_key' => '%s',
                    'description' => '%s',
                    'permissions' => '%s',
                    'last_access' => '%s',
                    'is_active' => '%d',
                    'date_created' => '%s',
                    'user_id' => '%d'
                )
            ),
            'erp_webhook_log' => array(
                'primary_key' => 'id',
                'table_name' => $wpdb->prefix . 'erp_webhook_log',
                'fields' => array(
                    'id' => '%d',
                    'webhook_id' => '%s',
                    'direction' => '%s',
                    'url' => '%s',
                    'data' => '%s',
                    'response' => '%s',
                    'status_code' => '%d',
                    'date_created' => '%s'
                )
            )
        );
        
        // Applica filtri per estendere le tabelle da moduli esterni
        $tables = apply_filters( 'erp_db_tables', $tables );
        
        if ( isset( $tables[ $table ] ) ) {
            return $tables[ $table ];
        }
        
        return false;
    }
    
    /**
     * Inserisce un record in una tabella personalizzata
     * 
     * @param string $table Nome della tabella (senza prefisso)
     * @param array  $data  Dati da inserire
     * @return int|bool ID del record inserito o false in caso di errore
     */
    public function insert( $table, $data ) {
        global $wpdb;
        
        $table_config = $this->get_table_config( $table );
        if ( ! $table_config ) {
            return false;
        }
        
        $table_name = $table_config['table_name'];
        $fields = $table_config['fields'];
        
        // Prepara i dati da inserire, utilizzando solo i campi validi
        $insert_data = array();
        $format = array();
        
        foreach ( $fields as $field => $field_format ) {
            if ( isset( $data[ $field ] ) && $field !== 'id' ) {
                $insert_data[ $field ] = $data[ $field ];
                $format[] = $field_format;
            }
        }
        
        // Aggiunge timestamp se necessario
        if ( isset( $fields['date_created'] ) && ! isset( $insert_data['date_created'] ) ) {
            $insert_data['date_created'] = current_time( 'mysql' );
            $format[] = '%s';
        }
        
        // Esegue l'inserimento
        $result = $wpdb->insert( $table_name, $insert_data, $format );
        
        if ( $result ) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Aggiorna un record in una tabella personalizzata
     * 
     * @param string $table Nome della tabella (senza prefisso)
     * @param array  $data  Dati da aggiornare
     * @param array  $where Condizioni per la clausola WHERE
     * @return int|bool Numero di righe aggiornate o false in caso di errore
     */
    public function update( $table, $data, $where ) {
        global $wpdb;
        
        $table_config = $this->get_table_config( $table );
        if ( ! $table_config ) {
            return false;
        }
        
        $table_name = $table_config['table_name'];
        $fields = $table_config['fields'];
        
        // Prepara i dati da aggiornare, utilizzando solo i campi validi
        $update_data = array();
        $update_format = array();
        
        foreach ( $fields as $field => $field_format ) {
            if ( isset( $data[ $field ] ) ) {
                $update_data[ $field ] = $data[ $field ];
                $update_format[] = $field_format;
            }
        }
        
        // Aggiunge timestamp di aggiornamento se necessario
        if ( isset( $fields['date_updated'] ) && ! isset( $update_data['date_updated'] ) ) {
            $update_data['date_updated'] = current_time( 'mysql' );
            $update_format[] = '%s';
        }
        
        // Prepara le condizioni WHERE
        $where_data = array();
        $where_format = array();
        
        foreach ( $where as $field => $value ) {
            if ( isset( $fields[ $field ] ) ) {
                $where_data[ $field ] = $value;
                $where_format[] = $fields[ $field ];
            }
        }
        
        // Esegue l'aggiornamento
        return $wpdb->update( $table_name, $update_data, $where_data, $update_format, $where_format );
    }
    
    /**
     * Elimina un record da una tabella personalizzata
     * 
     * @param string $table Nome della tabella (senza prefisso)
     * @param array  $where Condizioni per la clausola WHERE
     * @return int|bool Numero di righe eliminate o false in caso di errore
     */
    public function delete( $table, $where ) {
        global $wpdb;
        
        $table_config = $this->get_table_config( $table );
        if ( ! $table_config ) {
            return false;
        }
        
        $table_name = $table_config['table_name'];
        $fields = $table_config['fields'];
        
        // Prepara le condizioni WHERE
        $where_data = array();
        $where_format = array();
        
        foreach ( $where as $field => $value ) {
            if ( isset( $fields[ $field ] ) ) {
                $where_data[ $field ] = $value;
                $where_format[] = $fields[ $field ];
            }
        }
        
        // Esegue l'eliminazione
        return $wpdb->delete( $table_name, $where_data, $where_format );
    }
    
    /**
     * Ottiene un singolo record da una tabella personalizzata
     * 
     * @param string $table Nome della tabella (senza prefisso)
     * @param int|array $id_or_where ID del record o array di condizioni
     * @return object|null Record trovato o null se non esiste
     */
    public function get( $table, $id_or_where ) {
        global $wpdb;
        
        $table_config = $this->get_table_config( $table );
        if ( ! $table_config ) {
            return null;
        }
        
        $table_name = $table_config['table_name'];
        $primary_key = $table_config['primary_key'];
        
        // Costruisce la query
        $where = '';
        $where_values = array();
        
        if ( is_array( $id_or_where ) ) {
            $conditions = array();
            
            foreach ( $id_or_where as $field => $value ) {
                $conditions[] = "`$field` = %s";
                $where_values[] = $value;
            }
            
            $where = implode( ' AND ', $conditions );
        } else {
            $where = "`$primary_key` = %d";
            $where_values[] = $id_or_where;
        }
        
        // Prepara la query
        $query = $wpdb->prepare( "SELECT * FROM $table_name WHERE $where LIMIT 1", $where_values );
        
        // Esegue la query
        return $wpdb->get_row( $query );
    }
    
    /**
     * Ottiene più record da una tabella personalizzata
     * 
     * @param string $table  Nome della tabella (senza prefisso)
     * @param array  $args   Argomenti per la query
     * @return array Array di record trovati
     */
    public function get_items( $table, $args = array() ) {
        global $wpdb;
        
        $table_config = $this->get_table_config( $table );
        if ( ! $table_config ) {
            return array();
        }
        
        $table_name = $table_config['table_name'];
        
        // Imposta i valori di default
        $defaults = array(
            'where'     => array(),
            'orderby'   => $table_config['primary_key'],
            'order'     => 'DESC',
            'limit'     => 20,
            'offset'    => 0,
            'count'     => false,
            'fields'    => '*'
        );
        
        $args = wp_parse_args( $args, $defaults );
        
        // Costruisce la query
        $where = '';
        $where_values = array();
        
        if ( ! empty( $args['where'] ) ) {
            $conditions = array();
            
            foreach ( $args['where'] as $field => $value ) {
                if ( is_array( $value ) ) {
                    // Supporto per operatori personalizzati: $args['where']['field'] = ['op' => '>', 'value' => 10]
                    $operator = isset( $value['op'] ) ? $value['op'] : '=';
                    $field_value = isset( $value['value'] ) ? $value['value'] : '';
                    
                    $conditions[] = "`$field` $operator %s";
                    $where_values[] = $field_value;
                } else {
                    $conditions[] = "`$field` = %s";
                    $where_values[] = $value;
                }
            }
            
            $where = 'WHERE ' . implode( ' AND ', $conditions );
        }
        
        // Campi da selezionare
        $select = $args['fields'];
        
        // Ordinamento
        $orderby = sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );
        $orderby = ! empty( $orderby ) ? "ORDER BY $orderby" : '';
        
        // Limite e offset
        $limit = '';
        if ( $args['limit'] > 0 ) {
            $limit = $wpdb->prepare( "LIMIT %d OFFSET %d", $args['limit'], $args['offset'] );
        }
        
        // Conteggio totale o risultati completi
        if ( $args['count'] ) {
            $query = "SELECT COUNT(*) FROM $table_name $where";
            $query = $where_values ? $wpdb->prepare( $query, $where_values ) : $query;
            return $wpdb->get_var( $query );
        } else {
            $query = "SELECT $select FROM $table_name $where $orderby $limit";
            $query = $where_values ? $wpdb->prepare( $query, $where_values ) : $query;
            return $wpdb->get_results( $query );
        }
    }
    
    /**
     * Esegue una query personalizzata
     * 
     * @param string $query  Query SQL da eseguire
     * @param string $output Tipo di output (OBJECT, ARRAY_A, ARRAY_N)
     * @return mixed Risultato della query
     */
    public function query( $query, $output = OBJECT ) {
        global $wpdb;
        
        // Verifica che la query sia sicura
        if ( preg_match( '/^\s*(ALTER|CREATE|DROP|RENAME|TRUNCATE|OPTIMIZE|REPAIR)/i', $query ) ) {
            return false;
        }
        
        // Esegue la query
        $result = $wpdb->get_results( $query, $output );
        
        return $result;
    }

    /**
     * Inizia una transazione
     * 
     * @return void
     */
    public function start_transaction() {
        global $wpdb;
        $wpdb->query( 'START TRANSACTION' );
    }
    
    /**
     * Esegue il commit di una transazione
     * 
     * @return void
     */
    public function commit() {
        global $wpdb;
        $wpdb->query( 'COMMIT' );
    }
    
    /**
     * Esegue il rollback di una transazione
     * 
     * @return void
     */
    public function rollback() {
        global $wpdb;
        $wpdb->query( 'ROLLBACK' );
    }
}
