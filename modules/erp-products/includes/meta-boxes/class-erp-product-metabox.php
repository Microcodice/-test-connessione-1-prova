<?php
/**
 * Classe per la gestione dei metabox dei prodotti
 *
 * @package ERP_Core
 * @subpackage ERP_Prodotti
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe per la gestione dei metabox dei prodotti
 */
class ERP_Product_Metabox {

    /**
     * Inizializza la classe
     *
     * @return void
     */
    public function init() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_metabox'), 10, 2);
    }

    /**
     * Aggiunge i metabox
     *
     * @return void
     */
    public function add_meta_boxes() {
        add_meta_box(
            'erp_product_data',
            __('Dati Prodotto', 'erp-core'),
            array($this, 'render_product_data_metabox'),
            'erp_product',
            'normal',
            'high'
        );

        add_meta_box(
            'erp_product_inventory',
            __('Inventario', 'erp-core'),
            array($this, 'render_product_inventory_metabox'),
            'erp_product',
            'normal',
            'default'
        );

        add_meta_box(
            'erp_product_pricing',
            __('Prezzi', 'erp-core'),
            array($this, 'render_product_pricing_metabox'),
            'erp_product',
            'normal',
            'default'
        );
    }

    /**
     * Renderizza il metabox per i dati del prodotto
     *
     * @param WP_Post $post Oggetto post corrente.
     * @return void
     */
    public function render_product_data_metabox($post) {
        // Aggiungi nonce per sicurezza
        wp_nonce_field('erp_product_data_metabox', 'erp_product_data_nonce');

        // Recupera i valori salvati
        $sku = get_post_meta($post->ID, '_erp_sku', true);
        $barcode = get_post_meta($post->ID, '_erp_barcode', true);
        $weight = get_post_meta($post->ID, '_erp_weight', true);
        $dimensions = get_post_meta($post->ID, '_erp_dimensions', true);
        $supplier_id = get_post_meta($post->ID, '_erp_supplier_id', true);

        // Ottieni elenco fornitori
        $suppliers = get_posts(array(
            'post_type' => 'erp_supplier',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ));
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="erp_sku"><?php _e('SKU', 'erp-core'); ?></label>
                </th>
                <td>
                    <input type="text" id="erp_sku" name="erp_sku" value="<?php echo esc_attr($sku); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="erp_barcode"><?php _e('Codice a barre', 'erp-core'); ?></label>
                </th>
                <td>
                    <input type="text" id="erp_barcode" name="erp_barcode" value="<?php echo esc_attr($barcode); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="erp_weight"><?php _e('Peso (kg)', 'erp-core'); ?></label>
                </th>
                <td>
                    <input type="number" step="0.01" id="erp_weight" name="erp_weight" value="<?php echo esc_attr($weight); ?>" class="small-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="erp_dimensions"><?php _e('Dimensioni (LxAxP cm)', 'erp-core'); ?></label>
                </th>
                <td>
                    <input type="text" id="erp_dimensions" name="erp_dimensions" value="<?php echo esc_attr($dimensions); ?>" class="regular-text" placeholder="10x5x2">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="erp_supplier_id"><?php _e('Fornitore', 'erp-core'); ?></label>
                </th>
                <td>
                    <select id="erp_supplier_id" name="erp_supplier_id">
                        <option value=""><?php _e('Seleziona un fornitore', 'erp-core'); ?></option>
                        <?php foreach ($suppliers as $supplier) : ?>
                            <option value="<?php echo $supplier->ID; ?>" <?php selected($supplier_id, $supplier->ID); ?>>
                                <?php echo $supplier->post_title; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Renderizza il metabox per l'inventario
     *
     * @param WP_Post $post Oggetto post corrente.
     * @return void
     */
    public function render_product_inventory_metabox($post) {
        // Recupera i valori salvati
        $stock = get_post_meta($post->ID, '_erp_stock', true);
        $min_stock = get_post_meta($post->ID, '_erp_min_stock', true);
        $manage_stock = get_post_meta($post->ID, '_erp_manage_stock', true);
        $stock_status = get_post_meta($post->ID, '_erp_stock_status', true);
        $location = get_post_meta($post->ID, '_erp_location', true);
        
        // Valore predefinito
        if (!$stock_status) {
            $stock_status = 'instock';
        }
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="erp_manage_stock"><?php _e('Gestisci inventario', 'erp-core'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="erp_manage_stock" name="erp_manage_stock" value="yes" <?php checked($manage_stock, 'yes'); ?>>
                    <span class="description"><?php _e('Attiva la gestione del magazzino per questo prodotto', 'erp-core'); ?></span>
                </td>
            </tr>
            <tr class="stock-fields" <?php echo $manage_stock !== 'yes' ? 'style="display:none;"' : ''; ?>>
                <th scope="row">
                    <label for="erp_stock"><?php _e('Quantità in magazzino', 'erp-core'); ?></label>
                </th>
                <td>
                    <input type="number" step="1" id="erp_stock" name="erp_stock" value="<?php echo esc_attr($stock); ?>" class="small-text">
                </td>
            </tr>
            <tr class="stock-fields" <?php echo $manage_stock !== 'yes' ? 'style="display:none;"' : ''; ?>>
                <th scope="row">
                    <label for="erp_min_stock"><?php _e('Soglia minima', 'erp-core'); ?></label>
                </th>
                <td>
                    <input type="number" step="1" id="erp_min_stock" name="erp_min_stock" value="<?php echo esc_attr($min_stock); ?>" class="small-text">
                    <span class="description"><?php _e('Verrà segnalato quando lo stock scende sotto questo valore', 'erp-core'); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="erp_stock_status"><?php _e('Stato inventario', 'erp-core'); ?></label>
                </th>
                <td>
                    <select id="erp_stock_status" name="erp_stock_status">
                        <option value="instock" <?php selected($stock_status, 'instock'); ?>><?php _e('Disponibile', 'erp-core'); ?></option>
                        <option value="outofstock" <?php selected($stock_status, 'outofstock'); ?>><?php _e('Esaurito', 'erp-core'); ?></option>
                        <option value="onbackorder" <?php selected($stock_status, 'onbackorder'); ?>><?php _e('In arrivo', 'erp-core'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="erp_location"><?php _e('Posizione in magazzino', 'erp-core'); ?></label>
                </th>
                <td>
                    <input type="text" id="erp_location" name="erp_location" value="<?php echo esc_attr($location); ?>" class="regular-text" placeholder="A12-B3">
                    <p class="description"><?php _e('Posizione fisica del prodotto in magazzino (es. scaffale, corridoio)', 'erp-core'); ?></p>
                </td>
            </tr>
        </table>
        <script>
            jQuery(document).ready(function($) {
                $('#erp_manage_stock').change(function() {
                    if ($(this).is(':checked')) {
                        $('.stock-fields').show();
                    } else {
                        $('.stock-fields').hide();
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Renderizza il metabox per i prezzi
     *
     * @param WP_Post $post Oggetto post corrente.
     * @return void
     */
    public function render_product_pricing_metabox($post) {
        // Recupera i valori salvati
        $regular_price = get_post_meta($post->ID, '_erp_regular_price', true);
        $sale_price = get_post_meta($post->ID, '_erp_sale_price', true);
        $purchase_price = get_post_meta($post->ID, '_erp_purchase_price', true);
        $tax_class = get_post_meta($post->ID, '_erp_tax_class', true);
        $tax_rate = get_post_meta($post->ID, '_erp_tax_rate', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="erp_purchase_price"><?php _e('Prezzo di acquisto', 'erp-core'); ?></label>
                </th>
                <td>
                    <input type="number" step="0.01" id="erp_purchase_price" name="erp_purchase_price" value="<?php echo esc_attr($purchase_price); ?>" class="small-text">
                    <span class="description"><?php _e('Costo di acquisto dal fornitore (IVA esclusa)', 'erp-core'); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="erp_regular_price"><?php _e('Prezzo di vendita', 'erp-core'); ?></label>
                </th>
                <td>
                    <input type="number" step="0.01" id="erp_regular_price" name="erp_regular_price" value="<?php echo esc_attr($regular_price); ?>" class="small-text">
                    <span class="description"><?php _e('Prezzo di vendita normale (IVA esclusa)', 'erp-core'); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="erp_sale_price"><?php _e('Prezzo scontato', 'erp-core'); ?></label>
                </th>
                <td>
                    <input type="number" step="0.01" id="erp_sale_price" name="erp_sale_price" value="<?php echo esc_attr($sale_price); ?>" class="small-text">
                    <span class="description"><?php _e('Prezzo promozionale (IVA esclusa)', 'erp-core'); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="erp_tax_class"><?php _e('Classe IVA', 'erp-core'); ?></label>
                </th>
                <td>
                    <select id="erp_tax_class" name="erp_tax_class">
                        <option value="standard" <?php selected($tax_class, 'standard'); ?>><?php _e('Standard', 'erp-core'); ?></option>
                        <option value="reduced" <?php selected($tax_class, 'reduced'); ?>><?php _e('Ridotta', 'erp-core'); ?></option>
                        <option value="zero" <?php selected($tax_class, 'zero'); ?>><?php _e('Zero', 'erp-core'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="erp_tax_rate"><?php _e('Aliquota IVA (%)', 'erp-core'); ?></label>
                </th>
                <td>
                    <input type="number" step="0.1" id="erp_tax_rate" name="erp_tax_rate" value="<?php echo esc_attr($tax_rate); ?>" class="small-text">
                    <span class="description"><?php _e('Ad es. 22 per IVA al 22%', 'erp-core'); ?></span>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Salva i dati del metabox
     *
     * @param int     $post_id ID del post.
     * @param WP_Post $post    Oggetto post.
     * @return void
     */
    public function save_metabox($post_id, $post) {
        // Verifica se è un salvataggio automatico
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Verifica il nonce
        if (!isset($_POST['erp_product_data_nonce']) || !wp_verify_nonce($_POST['erp_product_data_nonce'], 'erp_product_data_metabox')) {
            return;
        }

        // Verifica i permessi
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Verifica che sia il tipo di post corretto
        if ('erp_product' !== $post->post_type) {
            return;
        }

        // Salva i dati del prodotto
        $fields = array(
            'erp_sku' => '_erp_sku',
            'erp_barcode' => '_erp_barcode',
            'erp_weight' => '_erp_weight',
            'erp_dimensions' => '_erp_dimensions',
            'erp_supplier_id' => '_erp_supplier_id',
            'erp_stock' => '_erp_stock',
            'erp_min_stock' => '_erp_min_stock',
            'erp_stock_status' => '_erp_stock_status',
            'erp_location' => '_erp_location',
            'erp_regular_price' => '_erp_regular_price',
            'erp_sale_price' => '_erp_sale_price',
            'erp_purchase_price' => '_erp_purchase_price',
            'erp_tax_class' => '_erp_tax_class',
            'erp_tax_rate' => '_erp_tax_rate',
        );

        foreach ($fields as $post_field => $meta_field) {
            if (isset($_POST[$post_field])) {
                update_post_meta($post_id, $meta_field, sanitize_text_field($_POST[$post_field]));
            }
        }

        // Gestione inventario (checkbox)
        $manage_stock = isset($_POST['erp_manage_stock']) ? 'yes' : 'no';
        update_post_meta($post_id, '_erp_manage_stock', $manage_stock);
    }
}
