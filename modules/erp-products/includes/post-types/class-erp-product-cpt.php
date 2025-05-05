<?php
/**
 * Classe per la gestione del Custom Post Type prodotti
 *
 * @package ERP_Core
 * @subpackage ERP_Prodotti
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe per la gestione del Custom Post Type prodotti
 */
class ERP_Product_CPT {

    /**
     * Inizializza la classe
     *
     * @return void
     */
    public function init() {
        add_action('init', array($this, 'register_post_type'), 10);
    }

    /**
     * Registra il Custom Post Type per i prodotti
     *
     * @return void
     */
    public function register_post_type() {
        $labels = array(
            'name'               => _x('Prodotti', 'post type general name', 'erp-core'),
            'singular_name'      => _x('Prodotto', 'post type singular name', 'erp-core'),
            'menu_name'          => _x('Prodotti ERP', 'admin menu', 'erp-core'),
            'name_admin_bar'     => _x('Prodotto', 'add new on admin bar', 'erp-core'),
            'add_new'            => _x('Aggiungi Nuovo', 'product', 'erp-core'),
            'add_new_item'       => __('Aggiungi Nuovo Prodotto', 'erp-core'),
            'new_item'           => __('Nuovo Prodotto', 'erp-core'),
            'edit_item'          => __('Modifica Prodotto', 'erp-core'),
            'view_item'          => __('Visualizza Prodotto', 'erp-core'),
            'all_items'          => __('Tutti i Prodotti', 'erp-core'),
            'search_items'       => __('Cerca Prodotti', 'erp-core'),
            'parent_item_colon'  => __('Prodotto Genitore:', 'erp-core'),
            'not_found'          => __('Nessun prodotto trovato.', 'erp-core'),
            'not_found_in_trash' => __('Nessun prodotto trovato nel cestino.', 'erp-core')
        );

        $args = array(
            'labels'             => $labels,
            'description'        => __('Prodotti per ERP Core', 'erp-core'),
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => false,  // Non mostra nel menu di WordPress
            'query_var'          => true,
            'rewrite'            => array('slug' => 'erp-product'),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'editor', 'thumbnail', 'custom-fields'),
            'menu_icon'          => 'dashicons-products',
            'show_in_rest'       => true,
        );

        register_post_type('erp_product', $args);
    }
}
