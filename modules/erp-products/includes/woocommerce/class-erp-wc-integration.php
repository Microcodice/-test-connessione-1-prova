<?php
/**
 * Classe per l'integrazione con WooCommerce
 *
 * @package ERP_Core
 * @subpackage ERP_Prodotti
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe per l'integrazione con WooCommerce
 */
class ERP_WC_Integration {

    /**
     * Inizializza la classe
     *
     * @return void
     */
    public function init() {
        // Verifica se WooCommerce è attivo
        if (!$this->is_woocommerce_active()) {
            return;
        }

        // Sincronizzazione prodotti
        add_action('save_post_erp_product', array($this, 'sync_to_woocommerce'), 10, 3);
        add_action('woocommerce_update_product', array($this, 'sync_from_woocommerce'), 10, 2);
        
        // Gestione giacenze
        add_action('woocommerce_reduce_order_stock', array($this, 'sync_stock_reduction'), 10);
    }

    /**
     * Verifica se WooCommerce è attivo
     *
     * @return bool
     */
    public function is_woocommerce_active() {
        return class_exists('WooCommerce');
    }

    /**
     * Sincronizza un prodotto ERP con WooCommerce
     *
     * @param int     $post_id ID del post.
     * @param WP_Post $post    Oggetto post.
     * @param bool    $update  Se è un aggiornamento.
     * @return void
     */
    public function sync_to_woocommerce($post_id, $post, $update) {
        // Verifica se la sincronizzazione è abilitata
        if ('yes' !== get_option('erp_prodotti_sync_woocommerce', 'yes')) {
            return;
        }

        // Evita loop di sincronizzazione
        if (defined('ERP_DOING_PRODUCT_SYNC') && ERP_DOING_PRODUCT_SYNC) {
            return;
        }

        // Se il post non è pubblicato, esci
        if ('publish' !== $post->post_status) {
            return;
        }

        // Definisci la costante per evitare loop
        define('ERP_DOING_PRODUCT_SYNC', true);

        // Recupera l'ID WooCommerce collegato
        $wc_product_id = get_post_meta($post_id, '_erp_wc_product_id', true);

        // Se esiste già un collegamento, aggiorna il prodotto WooCommerce
        if ($wc_product_id) {
            $wc_product = wc_get_product($wc_product_id);
            if ($wc_product) {
                $this->update_wc_product($post_id, $wc_product);
                return;
            }
        }

        // Altrimenti crea un nuovo prodotto WooCommerce
        $this->create_wc_product($post_id);
    }

    /**
     * Crea un nuovo prodotto WooCommerce
     *
     * @param int $erp_product_id ID del prodotto ERP.
     * @return int|bool           ID del nuovo prodotto WooCommerce o false in caso di errore.
     */
    private function create_wc_product($erp_product_id) {
        $erp_product = get_post($erp_product_id);
        if (!$erp_product) {
            return false;
        }

        // Recupera i metadati del prodotto ERP
        $sku = get_post_meta($erp_product_id, '_erp_sku', true);
        $regular_price = get_post_meta($erp_product_id, '_erp_regular_price', true);
        $sale_price = get_post_meta($erp_product_id, '_erp_sale_price', true);
        $stock = get_post_meta($erp_product_id, '_erp_stock', true);
        $manage_stock = get_post_meta($erp_product_id, '_erp_manage_stock', true);
        $stock_status = get_post_meta($erp_product_id, '_erp_stock_status', true);
        $weight = get_post_meta($erp_product_id, '_erp_weight', true);
        $dimensions = get_post_meta($erp_product_id, '_erp_dimensions', true);

        // Crea il nuovo prodotto WooCommerce
        $wc_product = new WC_Product();
        $wc_product->set_name($erp_product->post_title);
        $wc_product->set_description($erp_product->post_content);
        $wc_product->set_sku($sku);

        // Imposta i prezzi
        if ($regular_price) {
            $wc_product->set_regular_price($regular_price);
        }
        if ($sale_price) {
            $wc_product->set_sale_price($sale_price);
        }

        // Imposta lo stock
        if ('yes' === $manage_stock) {
            $wc_product->set_manage_stock(true);
            $wc_product->set_stock_quantity($stock);
        } else {
            $wc_product->set_manage_stock(false);
        }
        $wc_product->set_stock_status($stock_status);

        // Imposta peso e dimensioni
        if ($weight) {
            $wc_product->set_weight($weight);
        }
        if ($dimensions) {
            $dim_array = explode('x', $dimensions);
            if (count($dim_array) === 3) {
                $wc_product->set_length($dim_array[0]);
                $wc_product->set_width($dim_array[1]);
                $wc_product->set_height($dim_array[2]);
            }
        }

        // Salva il prodotto
        $wc_product_id = $wc_product->save();

        // Collega il prodotto WooCommerce al prodotto ERP
        if ($wc_product_id) {
            update_post_meta($erp_product_id, '_erp_wc_product_id', $wc_product_id);
            update_post_meta($wc_product_id, '_erp_product_id', $erp_product_id);
        }

        return $wc_product_id;
    }

    /**
     * Aggiorna un prodotto WooCommerce esistente
     *
     * @param int        $erp_product_id ID del prodotto ERP.
     * @param WC_Product $wc_product     Oggetto prodotto WooCommerce.
     * @return bool                      True se l'aggiornamento ha avuto successo.
     */
    private function update_wc_product($erp_product_id, $wc_product) {
        $erp_product = get_post($erp_product_id);
        if (!$erp_product) {
            return false;
        }

        // Recupera i metadati del prodotto ERP
        $sku = get_post_meta($erp_product_id, '_erp_sku', true);
        $regular_price = get_post_meta($erp_product_id, '_erp_regular_price', true);
        $sale_price = get_post_meta($erp_product_id, '_erp_sale_price', true);
        $stock = get_post_meta($erp_product_id, '_erp_stock', true);
        $manage_stock = get_post_meta($erp_product_id, '_erp_manage_stock', true);
        $stock_status = get_post_meta($erp_product_id, '_erp_stock_status', true);
        $weight = get_post_meta($erp_product_id, '_erp_weight', true);
        $dimensions = get_post_meta($erp_product_id, '_erp_dimensions', true);

        // Aggiorna i dati base
        $wc_product->set_name($erp_product->post_title);
        $wc_product->set_description($erp_product->post_content);
        $wc_product->set_sku($sku);

        // Aggiorna i prezzi
        if ($regular_price) {
            $wc_product->set_regular_price($regular_price);
        }
        if ($sale_price) {
            $wc_product->set_sale_price($sale_price);
        }

        // Aggiorna lo stock
        if ('yes' === $manage_stock) {
            $wc_product->set_manage_stock(true);
            $wc_product->set_stock_quantity($stock);
        } else {
            $wc_product->set_manage_stock(false);
        }
        $wc_product->set_stock_status($stock_status);

        // Aggiorna peso e dimensioni
        if ($weight) {
            $wc_product->set_weight($weight);
        }
        if ($dimensions) {
            $dim_array = explode('x', $dimensions);
            if (count($dim_array) === 3) {
                $wc_product->set_length($dim_array[0]);
                $wc_product->set_width($dim_array[1]);
                $wc_product->set_height($dim_array[2]);
            }
        }

        // Salva le modifiche
        $wc_product->save();

        return true;
    }

    /**
     * Sincronizza un prodotto WooCommerce con ERP
     *
     * @param int     $wc_product_id ID del prodotto WooCommerce.
     * @param WC_Post $wc_product    Oggetto prodotto WooCommerce.
     * @return void
     */
    public function sync_from_woocommerce($wc_product_id, $wc_product) {
        // Verifica se la sincronizzazione è abilitata
        if ('yes' !== get_option('erp_prodotti_sync_woocommerce', 'yes')) {
            return;
        }

        // Evita loop di sincronizzazione
        if (defined('ERP_DOING_PRODUCT_SYNC') && ERP_DOING_PRODUCT_SYNC) {
            return;
        }

        // Definisci la costante per evitare loop
        define('ERP_DOING_PRODUCT_SYNC', true);

        // Recupera l'ID ERP collegato
        $erp_product_id = get_post_meta($wc_product_id, '_erp_product_id', true);

        // Se esiste già un collegamento, aggiorna il prodotto ERP
        if ($erp_product_id) {
            $erp_product = get_post($erp_product_id);
            if ($erp_product) {
                $this->update_erp_product($erp_product_id, $wc_product);
                return;
            }
        }

        // Altrimenti crea un nuovo prodotto ERP
        $this->create_erp_product($wc_product_id);
    }

    /**
     * Crea un nuovo prodotto ERP
     *
     * @param int $wc_product_id ID del prodotto WooCommerce.
     * @return int|bool          ID del nuovo prodotto ERP o false in caso di errore.
     */
    private function create_erp_product($wc_product_id) {
        $wc_product = wc_get_product($wc_product_id);
        if (!$wc_product) {
            return false;
        }

        // Crea il nuovo prodotto ERP
        $erp_product_data = array(
            'post_title'   => $wc_product->get_name(),
            'post_content' => $wc_product->get_description(),
            'post_status'  => 'publish',
            'post_type'    => 'erp_product',
        );

        $erp_product_id = wp_insert_post($erp_product_data);

        if (!$erp_product_id || is_wp_error($erp_product_id)) {
            return false;
        }

        // Aggiorna i metadati del prodotto ERP
        update_post_meta($erp_product_id, '_erp_sku', $wc_product->get_sku());
        update_post_meta($erp_product_id, '_erp_regular_price', $wc_product->get_regular_price());
        update_post_meta($erp_product_id, '_erp_sale_price', $wc_product->get_sale_price());
        
        // Gestione stock
        update_post_meta($erp_product_id, '_erp_manage_stock', $wc_product->get_manage_stock() ? 'yes' : 'no');
        update_post_meta($erp_product_id, '_erp_stock', $wc_product->get_stock_quantity());
        update_post_meta($erp_product_id, '_erp_stock_status', $wc_product->get_stock_status());
        
        // Dimensioni e peso
        update_post_meta($erp_product_id, '_erp_weight', $wc_product->get_weight());
        $dimensions = $wc_product->get_length() . 'x' . $wc_product->get_width() . 'x' . $wc_product->get_height();
        update_post_meta($erp_product_id, '_erp_dimensions', $dimensions);

        // Collega il prodotto ERP al prodotto WooCommerce
        update_post_meta($erp_product_id, '_erp_wc_product_id', $wc_product_id);
        update_post_meta($wc_product_id, '_erp_product_id', $erp_product_id);

        return $erp_product_id;
    }

    /**
     * Aggiorna un prodotto ERP esistente
     *
     * @param int        $erp_product_id ID del prodotto ERP.
     * @param WC_Product $wc_product     Oggetto prodotto WooCommerce.
     * @return bool                      True se l'aggiornamento ha avuto successo.
     */
    private function update_erp_product($erp_product_id, $wc_product) {
        // Aggiorna i dati base del prodotto ERP
        $erp_product_data = array(
            'ID'           => $erp_product_id,
            'post_title'   => $wc_product->get_name(),
            'post_content' => $wc_product->get_description(),
        );

        $updated = wp_update_post($erp_product_data);

        if (!$updated || is_wp_error($updated)) {
            return false;
        }

        // Aggiorna i metadati del prodotto ERP
        update_post_meta($erp_product_id, '_erp_sku', $wc_product->get_sku());
        update_post_meta($erp_product_id, '_erp_regular_price', $wc_product->get_regular_price());
        update_post_meta($erp_product_id, '_erp_sale_price', $wc_product->get_sale_price());
        
        // Gestione stock
        update_post_meta($erp_product_id, '_erp_manage_stock', $wc_product->get_manage_stock() ? 'yes' : 'no');
        update_post_meta($erp_product_id, '_erp_stock', $wc_product->get_stock_quantity());
        update_post_meta($erp_product_id, '_erp_stock_status', $wc_product->get_stock_status());
        
        // Dimensioni e peso
        update_post_meta($erp_product_id, '_erp_weight', $wc_product->get_weight());
        $dimensions = $wc_product->get_length() . 'x' . $wc_product->get_width() . 'x' . $wc_product->get_height();
        update_post_meta($erp_product_id, '_erp_dimensions', $dimensions);

        return true;
    }

    /**
     * Sincronizza la riduzione dello stock
     *
     * @param WC_Order $order Ordine WooCommerce.
     * @return void
     */
    public function sync_stock_reduction($order) {
        if (!$order) {
            return;
        }

        // Verifica se la sincronizzazione è abilitata
        if ('yes' !== get_option('erp_prodotti_sync_woocommerce', 'yes')) {
            return;
        }

        // Ottieni gli elementi dell'ordine
        $items = $order->get_items();

        foreach ($items as $item) {
            $product_id = $item->get_product_id();
            $quantity = $item->get_quantity();

            // Recupera l'ID ERP collegato
            $erp_product_id = get_post_meta($product_id, '_erp_product_id', true);

            if ($erp_product_id) {
                // Aggiorna lo stock del prodotto ERP
                $current_stock = get_post_meta($erp_product_id, '_erp_stock', true);
                $new_stock = max(0, (int) $current_stock - $quantity);
                update_post_meta($erp_product_id, '_erp_stock', $new_stock);

                // Aggiorna lo stato dello stock se necessario
                if (0 === $new_stock) {
                    update_post_meta($erp_product_id, '_erp_stock_status', 'outofstock');
                }

                // Crea un movimento di magazzino
                $this->create_stock_movement($erp_product_id, $quantity, $order->get_id());
            }
        }
    }

    /**
     * Crea un movimento di magazzino
     *
     * @param int $product_id    ID del prodotto.
     * @param int $quantity      Quantità.
     * @param int $order_id      ID dell'ordine.
     * @return int|bool          ID del movimento o false in caso di errore.
     */
    private function create_stock_movement($product_id, $quantity, $order_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'erp_movements';

        // Ottieni l'utente corrente
        $user_id = get_current_user_id();

        // Inserisci il movimento
        $result = $wpdb->insert(
            $table_name,
            array(
                'date'       => current_time('mysql'),
                'type'       => 'order',
                'product_id' => $product_id,
                'quantity'   => -$quantity, // Negativo per uno scarico
                'note'       => sprintf(__('Scarico per ordine #%s', 'erp-core'), $order_id),
                'user_id'    => $user_id,
                'reference'  => $order_id,
            ),
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

        if ($result) {
            do_action('erp_after_create_movement', $wpdb->insert_id, array(
                'product_id' => $product_id,
                'quantity'   => -$quantity,
                'type'       => 'order',
                'reference'  => $order_id,
            ));
            return $wpdb->insert_id;
        }

        return false;
    }
}