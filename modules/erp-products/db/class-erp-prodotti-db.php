<?php
/**
 * Classe per le operazioni sul database
 *
 * @package ERP_Core
 * @subpackage ERP_Prodotti
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe per le operazioni sul database
 */
class ERP_Prodotti_DB {

    /**
     * Nome tabella movimenti
     *
     * @var string
     */
    private $movements_table;

    /**
     * Nome tabella varianti
     *
     * @var string
     */
    private $variants_table;

    /**
     * Costruttore
     */
    public function __construct() {
        global $wpdb;
        $this->movements_table = $wpdb->prefix . 'erp_movements';
        $this->variants_table = $wpdb->prefix . 'erp_variants';
    }

    /**
     * Ottiene i movimenti di un prodotto
     *
     * @param int   $product_id ID del prodotto.
     * @param array $args       Argomenti per la query.
     * @return array
     */
    public function get_product_movements($product_id, $args = array()) {
        global $wpdb;

        $defaults = array(
            'per_page' => 10,
            'page'     => 1,
            'orderby'  => 'date',
            'order'    => 'DESC',
            'type'     => '',
        );

        $args = wp_parse_args($args, $defaults);

        $limit = $args['per_page'];
        $offset = ($args['page'] - 1) * $args['per_page'];

        $where = $wpdb->prepare('product_id = %d', $product_id);

        if (!empty($args['type'])) {
            $where .= $wpdb->prepare(' AND type = %s', $args['type']);
        }

        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        if (!$orderby) {
            $orderby = 'date DESC';
        }

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->movements_table} WHERE {$where} ORDER BY {$orderby} LIMIT %d, %d",
            $offset, $limit
        );

        $items = $wpdb->get_results($query);

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->movements_table} WHERE {$where}");

        return array(
            'items'       => $items,
            'total'       => (int) $total,
            'total_pages' => ceil($total / $limit),
            'page'        => $args['page'],
        );
    }

    /**
     * Crea un movimento
     *
     * @param array $data Dati del movimento.
     * @return int|bool   ID del movimento o false in caso di errore.
     */
    public function create_movement($data) {
        global $wpdb;

        $defaults = array(
            'date'       => current_time('mysql'),
            'type'       => '',
            'product_id' => 0,
            'quantity'   => 0,
            'note'       => '',
            'user_id'    => get_current_user_id(),
            'reference'  => '',
        );

        $data = wp_parse_args($data, $defaults);

        $inserted = $wpdb->insert(
            $this->movements_table,
            $data,
            array(
                '%s', // date
                '%s', // type
                '%d', // product_id
                '%f', // quantity
                '%s', // note
                '%d', // user_id
                '%s', // reference
            )
        );

        if ($inserted) {
            // Aggiorna la quantità in stock
            $stock = get_post_meta($data['product_id'], '_erp_stock', true);
            $new_stock = (float) $stock + (float) $data['quantity'];
            update_post_meta($data['product_id'], '_erp_stock', $new_stock);

            // Aggiorna lo stato dello stock se necessario
            if ($new_stock <= 0) {
                update_post_meta($data['product_id'], '_erp_stock_status', 'outofstock');
            } else {
                $min_stock = get_post_meta($data['product_id'], '_erp_min_stock', true);
                if ($new_stock <= $min_stock) {
                    update_post_meta($data['product_id'], '_erp_stock_status', 'lowstock');
                } else {
                    update_post_meta($data['product_id'], '_erp_stock_status', 'instock');
                }
            }

            do_action('erp_after_create_movement', $wpdb->insert_id, $data);
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Ottiene le varianti di un prodotto
     *
     * @param int   $product_id ID del prodotto.
     * @param array $args       Argomenti per la query.
     * @return array
     */
    public function get_product_variants($product_id, $args = array()) {
        global $wpdb;

        $defaults = array(
            'per_page' => 10,
            'page'     => 1,
            'orderby'  => 'id',
            'order'    => 'ASC',
        );

        $args = wp_parse_args($args, $defaults);

        $limit = $args['per_page'];
        $offset = ($args['page'] - 1) * $args['per_page'];

        $where = $wpdb->prepare('product_id = %d', $product_id);

        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        if (!$orderby) {
            $orderby = 'id ASC';
        }

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->variants_table} WHERE {$where} ORDER BY {$orderby} LIMIT %d, %d",
            $offset, $limit
        );

        $items = $wpdb->get_results($query);

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->variants_table} WHERE {$where}");

        return array(
            'items'       => $items,
            'total'       => (int) $total,
            'total_pages' => ceil($total / $limit),
            'page'        => $args['page'],
        );
    }

    /**
     * Crea una variante
     *
     * @param array $data Dati della variante.
     * @return int|bool   ID della variante o false in caso di errore.
     */
    public function create_variant($data) {
        global $wpdb;

        $defaults = array(
            'product_id' => 0,
            'sku'        => '',
            'price'      => 0,
            'stock'      => 0,
            'attributes' => '',
        );

        $data = wp_parse_args($data, $defaults);

        // Converte gli attributi in JSON se necessario
        if (is_array($data['attributes'])) {
            $data['attributes'] = wp_json_encode($data['attributes']);
        }

        $inserted = $wpdb->insert(
            $this->variants_table,
            $data,
            array(
                '%d', // product_id
                '%s', // sku
                '%f', // price
                '%d', // stock
                '%s', // attributes
            )
        );

        if ($inserted) {
            do_action('erp_after_create_variant', $wpdb->insert_id, $data);
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Aggiorna una variante
     *
     * @param int   $variant_id ID della variante.
     * @param array $data       Dati da aggiornare.
     * @return bool             True se l'aggiornamento ha avuto successo.
     */
    public function update_variant($variant_id, $data) {
        global $wpdb;

        // Converte gli attributi in JSON se necessario
        if (isset($data['attributes']) && is_array($data['attributes'])) {
            $data['attributes'] = wp_json_encode($data['attributes']);
        }

        // Prepara i formati dei campi
        $formats = array();
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'product_id':
                case 'stock':
                    $formats[] = '%d';
                    break;
                case 'price':
                    $formats[] = '%f';
                    break;
                default:
                    $formats[] = '%s';
                    break;
            }
        }

        $updated = $wpdb->update(
            $this->variants_table,
            $data,
            array('id' => $variant_id),
            $formats,
            array('%d')
        );

        if ($updated) {
            do_action('erp_after_update_variant', $variant_id, $data);
            return true;
        }

        return false;
    }

    /**
     * Elimina una variante
     *
     * @param int $variant_id ID della variante.
     * @return bool           True se l'eliminazione ha avuto successo.
     */
    public function delete_variant($variant_id) {
        global $wpdb;

        $deleted = $wpdb->delete(
            $this->variants_table,
            array('id' => $variant_id),
            array('%d')
        );

        if ($deleted) {
            do_action('erp_after_delete_variant', $variant_id);
            return true;
        }

        return false;
    }

    /**
     * Ottiene il valore totale del magazzino
     *
     * @return float
     */
    public function get_total_stock_value() {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT SUM(pm1.meta_value * pm2.meta_value) as total
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = %s
            JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = %s
            WHERE p.post_type = %s AND p.post_status = %s",
            '_erp_stock', '_erp_purchase_price', 'erp_product', 'publish'
        );

        $total = $wpdb->get_var($query);

        if (null === $total) {
            $total = 0;
        }

        return (float) $total;
    }

    /**
     * Ottiene il conteggio dei prodotti in esaurimento
     *
     * @return int
     */
    public function get_low_stock_count() {
        global $wpdb;
        
        $threshold = get_option('erp_prodotti_low_stock_threshold', 5);

        $query = $wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = %s AND pm1.meta_value = %s
            JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = %s
            WHERE p.post_type = %s 
            AND p.post_status = %s
            AND pm2.meta_value > 0 
            AND pm2.meta_value <= %d",
            '_erp_manage_stock', 'yes', '_erp_stock', 'erp_product', 'publish', $threshold
        );

        $count = $wpdb->get_var($query);

        return (int) $count;
    }

    /**
     * Ottiene il conteggio dei prodotti esauriti
     *
     * @return int
     */
    public function get_out_of_stock_count() {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = %s AND pm1.meta_value = %s
            JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = %s AND pm2.meta_value = %s
            WHERE p.post_type = %s 
            AND p.post_status = %s",
            '_erp_manage_stock', 'yes', '_erp_stock', '0', 'erp_product', 'publish'
        );

        $count = $wpdb->get_var($query);

        return (int) $count;
    }

    /**
     * Aggiorna lo stock di un prodotto
     *
     * @param int   $product_id ID del prodotto.
     * @param float $quantity   Quantità da aggiungere (o sottrarre se negativa).
     * @param array $args       Argomenti aggiuntivi.
     * @return bool             True se l'aggiornamento ha avuto successo.
     */
    public function update_product_stock($product_id, $quantity, $args = array()) {
        $defaults = array(
            'type'      => 'adjustment',
            'note'      => '',
            'reference' => '',
        );

        $args = wp_parse_args($args, $defaults);

        // Crea un movimento
        $movement_data = array(
            'product_id' => $product_id,
            'quantity'   => $quantity,
            'type'       => $args['type'],
            'note'       => $args['note'],
            'reference'  => $args['reference'],
        );

        return $this->create_movement($movement_data);
    }
}