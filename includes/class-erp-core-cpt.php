<?php
/**
 * Gestione dei Custom Post Types
 * 
 * Questa classe gestisce la registrazione e la configurazione di tutti i 
 * Custom Post Types necessari al funzionamento dell'ERP Core.
 * 
 * @package ERP_Core
 */

// Impedisce l'accesso diretto
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ERP_Core_CPT {
    /**
     * Istanza singola della classe (singleton)
     * @var ERP_Core_CPT
     */
    private static $instance = null;

    /**
     * Ottiene l'istanza singola della classe
     * @return ERP_Core_CPT
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
     * Registra tutti i Custom Post Types
     */
    public function register_post_types() {
        // Registro i CPT principali
        $this->register_product_cpt();
        $this->register_client_cpt();
        $this->register_supplier_cpt();
        $this->register_document_cpt();
        $this->register_movement_cpt();
        
        // Registro le tassonomie
        $this->register_taxonomies();
        
        // Applica filtri per eventuali personalizzazioni
        do_action( 'erp_core_after_register_post_types' );
    }

    /**
     * Registra il CPT per i prodotti
     */
    private function register_product_cpt() {
        $labels = array(
            'name'                  => _x( 'Prodotti', 'Post type general name', 'erp-core' ),
            'singular_name'         => _x( 'Prodotto', 'Post type singular name', 'erp-core' ),
            'menu_name'             => _x( 'Prodotti', 'Admin Menu text', 'erp-core' ),
            'name_admin_bar'        => _x( 'Prodotto', 'Add New on Toolbar', 'erp-core' ),
            'add_new'               => __( 'Aggiungi Nuovo', 'erp-core' ),
            'add_new_item'          => __( 'Aggiungi Nuovo Prodotto', 'erp-core' ),
            'new_item'              => __( 'Nuovo Prodotto', 'erp-core' ),
            'edit_item'             => __( 'Modifica Prodotto', 'erp-core' ),
            'view_item'             => __( 'Visualizza Prodotto', 'erp-core' ),
            'all_items'             => __( 'Tutti i Prodotti', 'erp-core' ),
            'search_items'          => __( 'Cerca Prodotti', 'erp-core' ),
            'not_found'             => __( 'Nessun prodotto trovato.', 'erp-core' ),
            'not_found_in_trash'    => __( 'Nessun prodotto trovato nel cestino.', 'erp-core' ),
            'featured_image'        => __( 'Immagine Prodotto', 'erp-core' ),
            'set_featured_image'    => __( 'Imposta immagine prodotto', 'erp-core' ),
            'remove_featured_image' => __( 'Rimuovi immagine prodotto', 'erp-core' ),
            'use_featured_image'    => __( 'Usa come immagine prodotto', 'erp-core' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => false,
            'show_in_menu'       => false,
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
            'show_in_rest'       => true, // Necessario per il nuovo editor Gutenberg
            'map_meta_cap'       => true,
        );

        register_post_type( 'erp_product', $args );
    }

    /**
     * Registra il CPT per i clienti
     */
    private function register_client_cpt() {
        $labels = array(
            'name'                  => _x( 'Clienti', 'Post type general name', 'erp-core' ),
            'singular_name'         => _x( 'Cliente', 'Post type singular name', 'erp-core' ),
            'menu_name'             => _x( 'Clienti', 'Admin Menu text', 'erp-core' ),
            'name_admin_bar'        => _x( 'Cliente', 'Add New on Toolbar', 'erp-core' ),
            'add_new'               => __( 'Aggiungi Nuovo', 'erp-core' ),
            'add_new_item'          => __( 'Aggiungi Nuovo Cliente', 'erp-core' ),
            'new_item'              => __( 'Nuovo Cliente', 'erp-core' ),
            'edit_item'             => __( 'Modifica Cliente', 'erp-core' ),
            'view_item'             => __( 'Visualizza Cliente', 'erp-core' ),
            'all_items'             => __( 'Tutti i Clienti', 'erp-core' ),
            'search_items'          => __( 'Cerca Clienti', 'erp-core' ),
            'not_found'             => __( 'Nessun cliente trovato.', 'erp-core' ),
            'not_found_in_trash'    => __( 'Nessun cliente trovato nel cestino.', 'erp-core' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => false,
            'show_in_menu'       => false,
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title', 'editor', 'custom-fields' ),
            'show_in_rest'       => true,
            'map_meta_cap'       => true,
        );

        register_post_type( 'erp_client', $args );
    }

    /**
     * Registra il CPT per i fornitori
     */
    private function register_supplier_cpt() {
        $labels = array(
            'name'                  => _x( 'Fornitori', 'Post type general name', 'erp-core' ),
            'singular_name'         => _x( 'Fornitore', 'Post type singular name', 'erp-core' ),
            'menu_name'             => _x( 'Fornitori', 'Admin Menu text', 'erp-core' ),
            'name_admin_bar'        => _x( 'Fornitore', 'Add New on Toolbar', 'erp-core' ),
            'add_new'               => __( 'Aggiungi Nuovo', 'erp-core' ),
            'add_new_item'          => __( 'Aggiungi Nuovo Fornitore', 'erp-core' ),
            'new_item'              => __( 'Nuovo Fornitore', 'erp-core' ),
            'edit_item'             => __( 'Modifica Fornitore', 'erp-core' ),
            'view_item'             => __( 'Visualizza Fornitore', 'erp-core' ),
            'all_items'             => __( 'Tutti i Fornitori', 'erp-core' ),
            'search_items'          => __( 'Cerca Fornitori', 'erp-core' ),
            'not_found'             => __( 'Nessun fornitore trovato.', 'erp-core' ),
            'not_found_in_trash'    => __( 'Nessun fornitore trovato nel cestino.', 'erp-core' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => false,
            'show_in_menu'       => false,
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title', 'editor', 'custom-fields' ),
            'show_in_rest'       => true,
            'map_meta_cap'       => true,
        );

        register_post_type( 'erp_supplier', $args );
    }

    /**
     * Registra il CPT per i documenti
     */
    private function register_document_cpt() {
        $labels = array(
            'name'                  => _x( 'Documenti', 'Post type general name', 'erp-core' ),
            'singular_name'         => _x( 'Documento', 'Post type singular name', 'erp-core' ),
            'menu_name'             => _x( 'Documenti', 'Admin Menu text', 'erp-core' ),
            'name_admin_bar'        => _x( 'Documento', 'Add New on Toolbar', 'erp-core' ),
            'add_new'               => __( 'Aggiungi Nuovo', 'erp-core' ),
            'add_new_item'          => __( 'Aggiungi Nuovo Documento', 'erp-core' ),
            'new_item'              => __( 'Nuovo Documento', 'erp-core' ),
            'edit_item'             => __( 'Modifica Documento', 'erp-core' ),
            'view_item'             => __( 'Visualizza Documento', 'erp-core' ),
            'all_items'             => __( 'Tutti i Documenti', 'erp-core' ),
            'search_items'          => __( 'Cerca Documenti', 'erp-core' ),
            'not_found'             => __( 'Nessun documento trovato.', 'erp-core' ),
            'not_found_in_trash'    => __( 'Nessun documento trovato nel cestino.', 'erp-core' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => false,
            'show_in_menu'       => false,
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title', 'custom-fields' ),
            'show_in_rest'       => true,
            'map_meta_cap'       => true,
        );

        register_post_type( 'erp_document', $args );
    }

    /**
     * Registra il CPT per i movimenti di magazzino
     */
    private function register_movement_cpt() {
        $labels = array(
            'name'                  => _x( 'Movimenti', 'Post type general name', 'erp-core' ),
            'singular_name'         => _x( 'Movimento', 'Post type singular name', 'erp-core' ),
            'menu_name'             => _x( 'Movimenti', 'Admin Menu text', 'erp-core' ),
            'name_admin_bar'        => _x( 'Movimento', 'Add New on Toolbar', 'erp-core' ),
            'add_new'               => __( 'Aggiungi Nuovo', 'erp-core' ),
            'add_new_item'          => __( 'Aggiungi Nuovo Movimento', 'erp-core' ),
            'new_item'              => __( 'Nuovo Movimento', 'erp-core' ),
            'edit_item'             => __( 'Modifica Movimento', 'erp-core' ),
            'view_item'             => __( 'Visualizza Movimento', 'erp-core' ),
            'all_items'             => __( 'Tutti i Movimenti', 'erp-core' ),
            'search_items'          => __( 'Cerca Movimenti', 'erp-core' ),
            'not_found'             => __( 'Nessun movimento trovato.', 'erp-core' ),
            'not_found_in_trash'    => __( 'Nessun movimento trovato nel cestino.', 'erp-core' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => false,
            'show_in_menu'       => false,
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title', 'custom-fields' ),
            'show_in_rest'       => true,
            'map_meta_cap'       => true,
        );

        register_post_type( 'erp_movement', $args );
    }

    /**
     * Registra le tassonomie necessarie
     */
    private function register_taxonomies() {
        // Categoria Prodotti
        $this->register_product_category_taxonomy();
        
        // Categoria Clienti
        $this->register_client_category_taxonomy();
        
        // Categoria Fornitori
        $this->register_supplier_category_taxonomy();
        
        // Categoria Documenti
        $this->register_document_type_taxonomy();
    }

    /**
     * Registra la tassonomia per le categorie prodotti
     */
    private function register_product_category_taxonomy() {
        $labels = array(
            'name'              => _x( 'Categorie Prodotti', 'taxonomy general name', 'erp-core' ),
            'singular_name'     => _x( 'Categoria Prodotto', 'taxonomy singular name', 'erp-core' ),
            'search_items'      => __( 'Cerca Categorie', 'erp-core' ),
            'all_items'         => __( 'Tutte le Categorie', 'erp-core' ),
            'parent_item'       => __( 'Categoria Genitore', 'erp-core' ),
            'parent_item_colon' => __( 'Categoria Genitore:', 'erp-core' ),
            'edit_item'         => __( 'Modifica Categoria', 'erp-core' ),
            'update_item'       => __( 'Aggiorna Categoria', 'erp-core' ),
            'add_new_item'      => __( 'Aggiungi Nuova Categoria', 'erp-core' ),
            'new_item_name'     => __( 'Nuova Categoria', 'erp-core' ),
            'menu_name'         => __( 'Categorie', 'erp-core' ),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => false,
            'show_admin_column' => true,
            'query_var'         => false,
            'rewrite'           => false,
            'show_in_rest'      => true,
        );

        register_taxonomy( 'erp_product_cat', array( 'erp_product' ), $args );
    }

    /**
     * Registra la tassonomia per le categorie clienti
     */
    private function register_client_category_taxonomy() {
        $labels = array(
            'name'              => _x( 'Categorie Clienti', 'taxonomy general name', 'erp-core' ),
            'singular_name'     => _x( 'Categoria Cliente', 'taxonomy singular name', 'erp-core' ),
            'search_items'      => __( 'Cerca Categorie', 'erp-core' ),
            'all_items'         => __( 'Tutte le Categorie', 'erp-core' ),
            'parent_item'       => __( 'Categoria Genitore', 'erp-core' ),
            'parent_item_colon' => __( 'Categoria Genitore:', 'erp-core' ),
            'edit_item'         => __( 'Modifica Categoria', 'erp-core' ),
            'update_item'       => __( 'Aggiorna Categoria', 'erp-core' ),
            'add_new_item'      => __( 'Aggiungi Nuova Categoria', 'erp-core' ),
            'new_item_name'     => __( 'Nuova Categoria', 'erp-core' ),
            'menu_name'         => __( 'Categorie', 'erp-core' ),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => false,
            'show_admin_column' => true,
            'query_var'         => false,
            'rewrite'           => false,
            'show_in_rest'      => true,
        );

        register_taxonomy( 'erp_client_cat', array( 'erp_client' ), $args );
    }

    /**
     * Registra la tassonomia per le categorie fornitori
     */
    private function register_supplier_category_taxonomy() {
        $labels = array(
            'name'              => _x( 'Categorie Fornitori', 'taxonomy general name', 'erp-core' ),
            'singular_name'     => _x( 'Categoria Fornitore', 'taxonomy singular name', 'erp-core' ),
            'search_items'      => __( 'Cerca Categorie', 'erp-core' ),
            'all_items'         => __( 'Tutte le Categorie', 'erp-core' ),
            'parent_item'       => __( 'Categoria Genitore', 'erp-core' ),
            'parent_item_colon' => __( 'Categoria Genitore:', 'erp-core' ),
            'edit_item'         => __( 'Modifica Categoria', 'erp-core' ),
            'update_item'       => __( 'Aggiorna Categoria', 'erp-core' ),
            'add_new_item'      => __( 'Aggiungi Nuova Categoria', 'erp-core' ),
            'new_item_name'     => __( 'Nuova Categoria', 'erp-core' ),
            'menu_name'         => __( 'Categorie', 'erp-core' ),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => false,
            'show_admin_column' => true,
            'query_var'         => false,
            'rewrite'           => false,
            'show_in_rest'      => true,
        );

        register_taxonomy( 'erp_supplier_cat', array( 'erp_supplier' ), $args );
    }

    /**
     * Registra la tassonomia per i tipi di documento
     */
    private function register_document_type_taxonomy() {
        $labels = array(
            'name'              => _x( 'Tipi Documento', 'taxonomy general name', 'erp-core' ),
            'singular_name'     => _x( 'Tipo Documento', 'taxonomy singular name', 'erp-core' ),
            'search_items'      => __( 'Cerca Tipi', 'erp-core' ),
            'all_items'         => __( 'Tutti i Tipi', 'erp-core' ),
            'parent_item'       => null,
            'parent_item_colon' => null,
            'edit_item'         => __( 'Modifica Tipo', 'erp-core' ),
            'update_item'       => __( 'Aggiorna Tipo', 'erp-core' ),
            'add_new_item'      => __( 'Aggiungi Nuovo Tipo', 'erp-core' ),
            'new_item_name'     => __( 'Nuovo Tipo', 'erp-core' ),
            'menu_name'         => __( 'Tipi Documento', 'erp-core' ),
        );

        $args = array(
            'hierarchical'      => false,
            'labels'            => $labels,
            'show_ui'           => false,
            'show_admin_column' => true,
            'query_var'         => false,
            'rewrite'           => false,
            'show_in_rest'      => true,
        );

        register_taxonomy( 'erp_document_type', array( 'erp_document' ), $args );
    }
}
