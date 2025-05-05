<?php
/**
 * Classe per la gestione delle tassonomie dei prodotti
 *
 * @package ERP_Core
 * @subpackage ERP_Prodotti
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe per la gestione delle tassonomie dei prodotti
 */
class ERP_Product_Tax {

    /**
     * Inizializza la classe
     *
     * @return void
     */
    public function init() {
        add_action('init', array($this, 'register_taxonomies'), 10);
    }

    /**
     * Registra le tassonomie
     *
     * @return void
     */
    public function register_taxonomies() {
        // Tassonomia Categoria Prodotti
        $labels = array(
            'name'              => _x('Categorie Prodotti', 'taxonomy general name', 'erp-core'),
            'singular_name'     => _x('Categoria Prodotto', 'taxonomy singular name', 'erp-core'),
            'search_items'      => __('Cerca Categorie', 'erp-core'),
            'all_items'         => __('Tutte le Categorie', 'erp-core'),
            'parent_item'       => __('Categoria Genitore', 'erp-core'),
            'parent_item_colon' => __('Categoria Genitore:', 'erp-core'),
            'edit_item'         => __('Modifica Categoria', 'erp-core'),
            'update_item'       => __('Aggiorna Categoria', 'erp-core'),
            'add_new_item'      => __('Aggiungi Nuova Categoria', 'erp-core'),
            'new_item_name'     => __('Nuova Categoria', 'erp-core'),
            'menu_name'         => __('Categorie', 'erp-core'),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'erp-product-category'),
            'show_in_rest'      => true,
        );

        register_taxonomy('erp_product_category', array('erp_product'), $args);

        // Tassonomia Attributi Prodotti
        $labels = array(
            'name'              => _x('Attributi Prodotti', 'taxonomy general name', 'erp-core'),
            'singular_name'     => _x('Attributo Prodotto', 'taxonomy singular name', 'erp-core'),
            'search_items'      => __('Cerca Attributi', 'erp-core'),
            'all_items'         => __('Tutti gli Attributi', 'erp-core'),
            'parent_item'       => __('Attributo Genitore', 'erp-core'),
            'parent_item_colon' => __('Attributo Genitore:', 'erp-core'),
            'edit_item'         => __('Modifica Attributo', 'erp-core'),
            'update_item'       => __('Aggiorna Attributo', 'erp-core'),
            'add_new_item'      => __('Aggiungi Nuovo Attributo', 'erp-core'),
            'new_item_name'     => __('Nuovo Attributo', 'erp-core'),
            'menu_name'         => __('Attributi', 'erp-core'),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'erp-product-attribute'),
            'show_in_rest'      => true,
        );

        register_taxonomy('erp_product_attribute', array('erp_product'), $args);
    }
}
