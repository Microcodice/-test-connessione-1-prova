<?php
/**
 * Controller API per il modulo Prodotti
 *
 * @package ERP_Core
 * @subpackage ERP_Prodotti
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controller API per il modulo Prodotti
 */
class ERP_Products_Controller {

    /**
     * Namespace per le API
     *
     * @var string
     */
    protected $namespace = 'erp/v1';

    /**
     * Route base per le API
     *
     * @var string
     */
    protected $rest_base = 'products';

    /**
     * Registra le route API
     *
     * @return void
     */
    public function register_routes() {
        // Lista products
        register_rest_route($this->namespace, '/' . $this->rest_base . '/list', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_items'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params(),
            ),
        ));

        // Singolo prodotto
        register_rest_route($this->namespace, '/' . $this->rest_base . '/get/(?P<id>[\d]+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_item'),
                'permission_callback' => array($this, 'get_item_permissions_check'),
                'args'                => array(
                    'id' => array(
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    ),
                ),
            ),
        ));

        // Creazione prodotto
        register_rest_route($this->namespace, '/' . $this->rest_base . '/create', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'create_item'),
                'permission_callback' => array($this, 'create_item_permissions_check'),
                'args'                => $this->get_endpoint_args_for_item_schema(true),
            ),
        ));

        // Aggiornamento prodotto
        register_rest_route($this->namespace, '/' . $this->rest_base . '/update/(?P<id>[\d]+)', array(
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_item'),
                'permission_callback' => array($this, 'update_item_permissions_check'),
                'args'                => $this->get_endpoint_args_for_item_schema(false),
            ),
        ));

        // Eliminazione prodotto
        register_rest_route($this->namespace, '/' . $this->rest_base . '/delete/(?P<id>[\d]+)', array(
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_item'),
                'permission_callback' => array($this, 'delete_item_permissions_check'),
                'args'                => array(
                    'id' => array(
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    ),
                ),
            ),
        ));

        // Conteggio products
        register_rest_route($this->namespace, '/' . $this->rest_base . '/count', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_count'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
            ),
        ));

        // Valore magazzino
        register_rest_route($this->namespace, '/' . $this->rest_base . '/valore', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_stock_value'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
            ),
        ));

        // Prodotti in esaurimento
        register_rest_route($this->namespace, '/' . $this->rest_base . '/esaurimento', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_low_stock_count'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
            ),
        ));

        // Prodotti esauriti
        register_rest_route($this->namespace, '/' . $this->rest_base . '/esauriti', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_out_of_stock_count'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
            ),
        ));
    }

    /**
     * Verifica i permessi per la lista products
     *
     * @param WP_REST_Request $request Richiesta completa.
     * @return bool
     */
    public function get_items_permissions_check($request) {
        return current_user_can('erp_products_view');
    }

    /**
     * Verifica i permessi per un singolo prodotto
     *
     * @param WP_REST_Request $request Richiesta completa.
     * @return bool
     */
    public function get_item_permissions_check($request) {
        return current_user_can('erp_products_view');
    }

    /**
     * Verifica i permessi per la creazione di un prodotto
     *
     * @param WP_REST_Request $request Richiesta completa.
     * @return bool
     */
    public function create_item_permissions_check($request) {
        return current_user_can('erp_products_create');
    }

    /**
     * Verifica i permessi per l'aggiornamento di un prodotto
     *
     * @param WP_REST_Request $request Richiesta completa.
     * @return bool
     */
    public function update_item_permissions_check($request) {
        return current_user_can('erp_products_edit');
    }

    /**
     * Verifica i permessi per l'eliminazione di un prodotto
     *
     * @param WP_REST_Request $request Richiesta completa.
     * @return bool
     */
    public function delete_item_permissions_check($request) {
        return current_user_can('erp_products_delete');
    }

    /**
     * Ottiene la lista dei products
     *
     * @param WP_REST_Request $request Richiesta completa.
     * @return WP_REST_Response
     */
    public function get_items($request) {
        $args = array(
            'post_type'      => 'erp_product',
            'posts_per_page' => $request->get_param('per_page') ? $request->get_param('per_page') : 10,
            'paged'          => $request->get_param('page') ? $request->get_param('page') : 1,
            'orderby'        => $request->get_param('orderby') ? $request->get_param('orderby') : 'date',
            'order'          => $request->get_param('order') ? $request->get_param('order') : 'DESC',
            'post_status'    => 'publish',
        );

        // Filtro per categoria
        if ($request->get_param('category')) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'erp_product_category',
                    'field'    => 'term_id',
                    'terms'    => (int) $request->get_param('category'),
                ),
            );
        }

        // Filtro per ricerca
        if ($request->get_param('search')) {
            $args['s'] = $request->get_param('search');
        }

        // Filtro per stock
        if ($request->get_param('stock_status')) {
            $args['meta_query'] = array(
                array(
                    'key'     => '_erp_stock_status',
                    'value'   => $request->get_param('stock_status'),
                    'compare' => '=',
                ),
            );
        }

        $query = new WP_Query($args);
        $products = array();

        foreach ($query->posts as $post) {
            $product = $this->prepare_item_for_response($post, $request);
            $products[] = $this->prepare_response_for_collection($product);
        }

        $page = $request->get_param('page') ? $request->get_param('page') : 1;
        $per_page = $request->get_param('per_page') ? $request->get_param('per_page') : 10;

        $response = rest_ensure_response($products);

        $response->header('X-WP-Total', $query->found_posts);
        $response->header('X-WP-TotalPages', ceil($query->found_posts / $per_page));

        return $response;
    }

    /**
     * Ottiene un singolo prodotto
     *
     * @param WP_REST_Request $request Richiesta completa.
     * @return WP_REST_Response|WP_Error
     */
    public function get_item($request) {
        $id = (int) $request->get_param('id');
        $post = get_post($id);

        if (empty($post) || $post->post_type !== 'erp_product') {
            return new WP_Error('rest_product_invalid_id', __('Prodotto non valido.', 'erp-core'), array('status' => 404));
        }

        $product = $this->prepare_item_for_response($post, $request);
        $response = rest_ensure_response($product);

        return $response;
    }

    /**
     * Crea un nuovo prodotto
     *
     * @param WP_REST_Request $request Richiesta completa.
     * @return WP_REST_Response|WP_Error
     */
    public function create_item($request) {
        $product = $this->prepare_item_for_database($request);

        if (is_wp_error($product)) {
            return $product;
        }

        $product_id = wp_insert_post($product, true);

        if (is_wp_error($product_id)) {
            return $product_id;
        }

        // Aggiorna i metadati
        $this->update_product_meta($product_id, $request);

        // Aggiorna le tassonomie
        $this->update_product_taxonomies($product_id, $request);

        $post = get_post($product_id);
        $response = $this->prepare_item_for_response($post, $request);
        $response = rest_ensure_response($response);
        $response->set_status(201);
        $response->header('Location', rest_url(sprintf('%s/%s/%d', $this->namespace, $this->rest_base, $product_id)));

        return $response;
    }

    /**
     * Aggiorna un prodotto esistente
     *
     * @param WP_REST_Request $request Richiesta completa.
     * @return WP_REST_Response|WP_Error
     */
    public function update_item($request) {
        $id = (int) $request->get_param('id');
        $post = get_post($id);

        if (empty($post) || $post->post_type !== 'erp_product') {
            return new WP_Error('rest_product_invalid_id', __('Prodotto non valido.', 'erp-core'), array('status' => 404));
        }

        $product = $this->prepare_item_for_database($request);

        if (is_wp_error($product)) {
            return $product;
        }

        // Imposta l'ID del prodotto
        $product['ID'] = $id;

        $product_id = wp_update_post($product, true);

        if (is_wp_error($product_id)) {
            return $product_id;
        }

        // Aggiorna i metadati
        $this->update_product_meta($product_id, $request);

        // Aggiorna le tassonomie
        $this->update_product_taxonomies($product_id, $request);

        $post = get_post($product_id);
        $response = $this->prepare_item_for_response($post, $request);
        $response = rest_ensure_response($response);

        return $response;
    }

    /**
     * Elimina un prodotto
     *
     * @param WP_REST_Request $request Richiesta completa.
     * @return WP_REST_Response|WP_Error
     */
    public function delete_item($request) {
        $id = (int) $request->get_param('id');
        $post = get_post($id);

        if (empty($post) || $post->post_type !== 'erp_product') {
            return new WP_Error('rest_product_invalid_id', __('Prodotto non valido.', 'erp-core'), array('status' => 404));
        }

        $result = wp_delete_post($id, true);

        if (!$result) {
            return new WP_Error('rest_cannot_delete', __('Il prodotto non può essere eliminato.', 'erp-core'), array('status' => 500));
        }

        $response = rest_ensure_response(array(
            'id'      => $id,
            'deleted' => true,
        ));

        return $response;
    }

    /**
     * Ottiene il conteggio dei products
     *
     * @param WP_REST_Request $request Richiesta completa.
     * @return WP_REST_Response
     */
    public function get_count($request) {
        $args = array(
            'post_type'      => 'erp_product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        );

        $query = new WP_Query($args);
        $count = $query->found_posts;

        $response = rest_ensure_response(array(
            'count' => $count,
        ));

        return $response;
    }

    /**
     * Ottiene il valore del magazzino
     *
     * @param WP_REST_Request $request Richiesta completa.
     * @return WP_REST_Response
     */
    public function get_stock_value($request) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT SUM(pm1.meta_value * pm2.meta_value) as total
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_erp_stock'
            JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_erp_purchase_price'
            WHERE p.post_type = %s AND p.post_status = %s",
            'erp_product', 'publish'
        );

        $total = $wpdb->get_var($query);

        if (null === $total) {
            $total = 0;
        }

        $response = rest_ensure_response(array(
            'value' => (float) $total,
        ));

        return $response;
    }

    /**
     * Ottiene il conteggio dei products in esaurimento
     *
     * @param WP_REST_Request $request Richiesta completa.
     * @return WP_REST_Response
     */
    public function get_low_stock_count($request) {
        $threshold = get_option('erp_products_low_stock_threshold', 5);

        $args = array(
            'post_type'      => 'erp_product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_erp_manage_stock',
                    'value'   => 'yes',
                    'compare' => '=',
                ),
                array(
                    'key'     => '_erp_stock',
                    'value'   => array(1, $threshold),
                    'compare' => 'BETWEEN',
                    'type'    => 'NUMERIC',
                ),
            ),
        );

        $query = new WP_Query($args);
        $count = $query->found_posts;

        $response = rest_ensure_response(array(
            'count' => $count,
        ));

        return $response;
    }

    /**
     * Ottiene il conteggio dei products esauriti
     *
     * @param WP_REST_Request $request Richiesta completa.
     * @return WP_REST_Response
     */
    public function get_out_of_stock_count($request) {
        $args = array(
            'post_type'      => 'erp_product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => '_erp_stock_status',
                    'value'   => 'outofstock',
                    'compare' => '=',
                ),
                array(
                    'key'     => '_erp_stock',
                    'value'   => '0',
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
            ),
        );

        $query = new WP_Query($args);
        $count = $query->found_posts;

        $response = rest_ensure_response(array(
            'count' => $count,
        ));

        return $response;
    }

    /**
     * Prepara un item per il database
     *
     * @param WP_REST_Request $request Richiesta completa.
     * @return array|WP_Error Dati del prodotto.
     */
    protected function prepare_item_for_database($request) {
        $product = array(
            'post_type'    => 'erp_product',
            'post_status'  => 'publish',
            'post_title'   => $request->get_param('title'),
            'post_content' => $request->get_param('description'),
        );

        return $product;
    }

    /**
     * Aggiorna i metadati di un prodotto
     *
     * @param int            $product_id ID del prodotto.
     * @param WP_REST_Request $request   Richiesta completa.
     * @return void
     */
    protected function update_product_meta($product_id, $request) {
        // Dati prodotto
        if ($request->get_param('sku')) {
            update_post_meta($product_id, '_erp_sku', sanitize_text_field($request->get_param('sku')));
        }

        if ($request->get_param('barcode')) {
            update_post_meta($product_id, '_erp_barcode', sanitize_text_field($request->get_param('barcode')));
        }

        if ($request->get_param('weight')) {
            update_post_meta($product_id, '_erp_weight', sanitize_text_field($request->get_param('weight')));
        }

        if ($request->get_param('dimensions')) {
            update_post_meta($product_id, '_erp_dimensions', sanitize_text_field($request->get_param('dimensions')));
        }

        if ($request->get_param('supplier_id')) {
            update_post_meta($product_id, '_erp_supplier_id', (int) $request->get_param('supplier_id'));
        }

        // Inventario
        if ($request->has_param('manage_stock')) {
            update_post_meta($product_id, '_erp_manage_stock', $request->get_param('manage_stock') ? 'yes' : 'no');
        }

        if ($request->get_param('stock')) {
            update_post_meta($product_id, '_erp_stock', (int) $request->get_param('stock'));
        }

        if ($request->get_param('min_stock')) {
            update_post_meta($product_id, '_erp_min_stock', (int) $request->get_param('min_stock'));
        }

        if ($request->get_param('stock_status')) {
            update_post_meta($product_id, '_erp_stock_status', sanitize_text_field($request->get_param('stock_status')));
        }

        if ($request->get_param('location')) {
            update_post_meta($product_id, '_erp_location', sanitize_text_field($request->get_param('location')));
        }

        // Prezzi
        if ($request->get_param('regular_price')) {
            update_post_meta($product_id, '_erp_regular_price', (float) $request->get_param('regular_price'));
        }

        if ($request->get_param('sale_price')) {
            update_post_meta($product_id, '_erp_sale_price', (float) $request->get_param('sale_price'));
        }

        if ($request->get_param('purchase_price')) {
            update_post_meta($product_id, '_erp_purchase_price', (float) $request->get_param('purchase_price'));
        }

        if ($request->get_param('tax_class')) {
            update_post_meta($product_id, '_erp_tax_class', sanitize_text_field($request->get_param('tax_class')));
        }

        if ($request->get_param('tax_rate')) {
            update_post_meta($product_id, '_erp_tax_rate', (float) $request->get_param('tax_rate'));
        }
    }

    /**
     * Aggiorna le tassonomie di un prodotto
     *
     * @param int            $product_id ID del prodotto.
     * @param WP_REST_Request $request   Richiesta completa.
     * @return void
     */
    protected function update_product_taxonomies($product_id, $request) {
        // Categorie
        if ($request->get_param('categories')) {
            $categories = $request->get_param('categories');
            if (is_array($categories)) {
                wp_set_object_terms($product_id, $categories, 'erp_product_category');
            }
        }

        // Attributi
        if ($request->get_param('attributes')) {
            $attributes = $request->get_param('attributes');
            if (is_array($attributes)) {
                wp_set_object_terms($product_id, $attributes, 'erp_product_attribute');
            }
        }
    }

    /**
     * Prepara un item per la risposta
     *
     * @param WP_Post         $post    Oggetto post.
     * @param WP_REST_Request $request Richiesta completa.
     * @return array Dati del prodotto.
     */
    protected function prepare_item_for_response($post, $request) {
        $data = array(
            'id'          => $post->ID,
            'title'       => $post->post_title,
            'description' => $post->post_content,
            'date'        => $post->post_date,
            'modified'    => $post->post_modified,
        );

        // Dati prodotto
        $data['sku'] = get_post_meta($post->ID, '_erp_sku', true);
        $data['barcode'] = get_post_meta($post->ID, '_erp_barcode', true);
        $data['weight'] = get_post_meta($post->ID, '_erp_weight', true);
        $data['dimensions'] = get_post_meta($post->ID, '_erp_dimensions', true);
        
        // Fornitore
        $supplier_id = get_post_meta($post->ID, '_erp_supplier_id', true);
        if ($supplier_id) {
            $supplier = get_post($supplier_id);
            if ($supplier) {
                $data['supplier'] = array(
                    'id'   => $supplier->ID,
                    'name' => $supplier->post_title,
                );
            }
        }

        // Inventario
        $data['manage_stock'] = get_post_meta($post->ID, '_erp_manage_stock', true) === 'yes';
        $data['stock'] = (int) get_post_meta($post->ID, '_erp_stock', true);
        $data['min_stock'] = (int) get_post_meta($post->ID, '_erp_min_stock', true);
        $data['stock_status'] = get_post_meta($post->ID, '_erp_stock_status', true);
        $data['location'] = get_post_meta($post->ID, '_erp_location', true);

        // Prezzi
        $data['regular_price'] = (float) get_post_meta($post->ID, '_erp_regular_price', true);
        $data['sale_price'] = (float) get_post_meta($post->ID, '_erp_sale_price', true);
        $data['purchase_price'] = (float) get_post_meta($post->ID, '_erp_purchase_price', true);
        $data['tax_class'] = get_post_meta($post->ID, '_erp_tax_class', true);
        $data['tax_rate'] = (float) get_post_meta($post->ID, '_erp_tax_rate', true);

        // Categorie
        $categories = wp_get_object_terms($post->ID, 'erp_product_category');
        $data['categories'] = array();
        foreach ($categories as $category) {
            $data['categories'][] = array(
                'id'   => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
            );
        }

        // Attributi
        $attributes = wp_get_object_terms($post->ID, 'erp_product_attribute');
        $data['attributes'] = array();
        foreach ($attributes as $attribute) {
            $data['attributes'][] = array(
                'id'   => $attribute->term_id,
                'name' => $attribute->name,
                'slug' => $attribute->slug,
            );
        }

        // Integrazione WooCommerce
        $wc_product_id = get_post_meta($post->ID, '_erp_wc_product_id', true);
        if ($wc_product_id) {
            $data['wc_product_id'] = (int) $wc_product_id;
        }

        return $data;
    }

    /**
     * Prepara una risposta per la collezione
     *
     * @param array $data Dati del prodotto.
     * @return array
     */
    protected function prepare_response_for_collection($data) {
        return $data;
    }

    /**
     * Ottiene i parametri per le collezioni
     *
     * @return array
     */
    protected function get_collection_params() {
        return array(
            'page' => array(
                'description'       => __('Numero di pagina corrente.', 'erp-core'),
                'type'              => 'integer',
                'default'           => 1,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
                'minimum'           => 1,
            ),
            'per_page' => array(
                'description'       => __('Numero di risultati per pagina.', 'erp-core'),
                'type'              => 'integer',
                'default'           => 10,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
                'minimum'           => 1,
                'maximum'           => 100,
            ),
            'search' => array(
                'description'       => __('Termine di ricerca.', 'erp-core'),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'orderby' => array(
                'description'       => __('Ordina per campo.', 'erp-core'),
                'type'              => 'string',
                'default'           => 'date',
                'enum'              => array('date', 'title', 'ID'),
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'order' => array(
                'description'       => __('Ordinamento.', 'erp-core'),
                'type'              => 'string',
                'default'           => 'DESC',
                'enum'              => array('ASC', 'DESC'),
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'category' => array(
                'description'       => __('ID categoria.', 'erp-core'),
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'stock_status' => array(
                'description'       => __('Stato stock.', 'erp-core'),
                'type'              => 'string',
                'enum'              => array('instock', 'outofstock', 'onbackorder'),
                'validate_callback' => 'rest_validate_request_arg',
            ),
        );
    }

    /**
     * Ottiene gli argomenti endpoint per lo schema
     *
     * @param bool $required Se gli argomenti sono richiesti.
     * @return array
     */
    protected function get_endpoint_args_for_item_schema($required = false) {
        $args = array(
            'title' => array(
                'description'       => __('Titolo del prodotto.', 'erp-core'),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
                'required'          => $required,
            ),
            'description' => array(
                'description'       => __('Descrizione del prodotto.', 'erp-core'),
                'type'              => 'string',
                'sanitize_callback' => 'wp_kses_post',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'sku' => array(
                'description'       => __('SKU del prodotto.', 'erp-core'),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'barcode' => array(
                'description'       => __('Codice a barre del prodotto.', 'erp-core'),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'weight' => array(
                'description'       => __('Peso del prodotto.', 'erp-core'),
                'type'              => 'number',
                'sanitize_callback' => 'floatval',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'dimensions' => array(
                'description'       => __('Dimensioni del prodotto.', 'erp-core'),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'supplier_id' => array(
                'description'       => __('ID fornitore.', 'erp-core'),
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'manage_stock' => array(
                'description'       => __('Gestisci stock.', 'erp-core'),
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'stock' => array(
                'description'       => __('Quantità in stock.', 'erp-core'),
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'min_stock' => array(
                'description'       => __('Quantità minima stock.', 'erp-core'),
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'stock_status' => array(
                'description'       => __('Stato stock.', 'erp-core'),
                'type'              => 'string',
                'enum'              => array('instock', 'outofstock', 'onbackorder'),
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'location' => array(
                'description'       => __('Posizione in magazzino.', 'erp-core'),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'regular_price' => array(
                'description'       => __('Prezzo regolare.', 'erp-core'),
                'type'              => 'number',
                'sanitize_callback' => 'floatval',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'sale_price' => array(
                'description'       => __('Prezzo scontato.', 'erp-core'),
                'type'              => 'number',
                'sanitize_callback' => 'floatval',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'purchase_price' => array(
                'description'       => __('Prezzo di acquisto.', 'erp-core'),
                'type'              => 'number',
                'sanitize_callback' => 'floatval',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'tax_class' => array(
                'description'       => __('Classe IVA.', 'erp-core'),
                'type'              => 'string',
                'enum'              => array('standard', 'reduced', 'zero'),
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'tax_rate' => array(
                'description'       => __('Aliquota IVA.', 'erp-core'),
                'type'              => 'number',
                'sanitize_callback' => 'floatval',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'categories' => array(
                'description'       => __('Categorie prodotto.', 'erp-core'),
                'type'              => 'array',
                'items'             => array(
                    'type' => 'integer',
                ),
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'attributes' => array(
                'description'       => __('Attributi prodotto.', 'erp-core'),
                'type'              => 'array',
                'items'             => array(
                    'type' => 'integer',
                ),
                'validate_callback' => 'rest_validate_request_arg',
            ),
        );

        return $args;
    }
}
