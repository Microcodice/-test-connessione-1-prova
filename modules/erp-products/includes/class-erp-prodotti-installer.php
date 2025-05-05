<?php
/**
 * Classe per l'installazione del modulo Prodotti
 *
 * @package ERP_Core
 * @subpackage ERP_Prodotti
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe per l'installazione del modulo Prodotti
 */
class ERP_Prodotti_Installer {

    /**
     * Esegue l'installazione del modulo
     *
     * @return void
     */
    public static function install() {
        self::create_tables();
        self::create_options();
        self::create_roles();
    }

    /**
     * Crea le tabelle del database necessarie
     *
     * @return void
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Tabella per gestire le varianti dei prodotti
        $table_variants = $wpdb->prefix . 'erp_variants';
        $sql_variants = "CREATE TABLE IF NOT EXISTS $table_variants (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint(20) unsigned NOT NULL,
            sku varchar(100) NOT NULL,
            price decimal(10,2) NOT NULL DEFAULT 0,
            stock int(11) NOT NULL DEFAULT 0,
            attributes longtext,
            date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            date_modified datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            UNIQUE KEY sku (sku)
        ) $charset_collate;";

        // Esegui le query
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_variants);

        // Registra la versione del database
        update_option('erp_prodotti_db_version', '1.0.0');
    }

    /**
     * Crea le opzioni predefinite
     *
     * @return void
     */
    private static function create_options() {
        // Opzioni per la gestione dei prodotti
        $options = array(
            'erp_prodotti_per_page' => 20,
            'erp_prodotti_low_stock_threshold' => 5,
            'erp_prodotti_out_of_stock_threshold' => 0,
            'erp_prodotti_sync_woocommerce' => 'yes',
        );

        foreach ($options as $option => $value) {
            if (false === get_option($option)) {
                add_option($option, $value);
            }
        }
    }

    /**
     * Crea i ruoli e i permessi
     *
     * @return void
     */
    private static function create_roles() {
        // Aggiungi i permessi per i ruoli esistenti
        $roles = array('administrator', 'erp_admin', 'erp_magazzino');

        foreach ($roles as $role_name) {
            $role = get_role($role_name);

            if ($role) {
                // Permessi base per i prodotti
                $role->add_cap('erp_prodotti_view');
                
                // I ruoli admin hanno tutti i permessi
                if (in_array($role_name, array('administrator', 'erp_admin'))) {
                    $role->add_cap('erp_prodotti_create');
                    $role->add_cap('erp_prodotti_edit');
                    $role->add_cap('erp_prodotti_delete');
                    $role->add_cap('erp_prodotti_report');
                }
                
                // Il ruolo magazzino ha permessi limitati
                if ($role_name === 'erp_magazzino') {
                    $role->add_cap('erp_prodotti_edit');
                    $role->add_cap('erp_prodotti_report');
                }
            }
        }
    }
}
