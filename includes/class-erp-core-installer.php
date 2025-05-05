<?php
/**
 * Classe Installer per la gestione dell'installazione
 * 
 * Gestisce l'installazione e l'aggiornamento del plugin, inclusa la creazione
 * delle tabelle personalizzate nel database.
 * 
 * @package ERP_Core
 */

// Impedisce l'accesso diretto
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ERP_Core_Installer {

    /**
     * Esegue l'installazione
     */
    public function install() {
        // Crea le tabelle personalizzate
        $this->create_tables();
        
        // Crea ruoli e permessi di default
        $this->create_roles();
        
        // Crea le cartelle necessarie
        $this->create_directories();
        
        // Aggiorna la versione nel database
        update_option( 'erp_core_version', ERP_CORE_VERSION );
        
        // Assicura che le regole di rewrite siano aggiornate
        flush_rewrite_rules();
    }

    /**
     * Crea le tabelle personalizzate nel database
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $tables = array(
            // Tabella movimenti magazzino
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}erp_movements (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                product_id BIGINT(20) UNSIGNED NOT NULL,
                variant_id BIGINT(20) UNSIGNED DEFAULT NULL,
                document_id BIGINT(20) UNSIGNED DEFAULT NULL,
                movement_type VARCHAR(50) NOT NULL,
                quantity DECIMAL(12,3) NOT NULL,
                unit_price DECIMAL(12,2) DEFAULT NULL,
                date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                note TEXT DEFAULT NULL,
                user_id BIGINT(20) UNSIGNED NOT NULL,
                PRIMARY KEY (id),
                KEY product_id (product_id),
                KEY document_id (document_id),
                KEY movement_type (movement_type)
            ) $charset_collate;",
            
            // Tabella varianti prodotto
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}erp_variants (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                product_id BIGINT(20) UNSIGNED NOT NULL,
                sku VARCHAR(100) DEFAULT NULL,
                price DECIMAL(12,2) DEFAULT NULL,
                stock_quantity DECIMAL(12,3) DEFAULT 0,
                attributes LONGTEXT DEFAULT NULL,
                woocommerce_variation_id BIGINT(20) UNSIGNED DEFAULT NULL,
                is_active TINYINT(1) DEFAULT 1,
                date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                date_updated DATETIME DEFAULT NULL,
                PRIMARY KEY (id),
                KEY product_id (product_id),
                KEY sku (sku),
                KEY woocommerce_variation_id (woocommerce_variation_id)
            ) $charset_collate;",
            
            // Tabella testata documenti
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}erp_documents_head (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                document_type VARCHAR(50) NOT NULL,
                document_number VARCHAR(100) NOT NULL,
                document_date DATE NOT NULL,
                client_id BIGINT(20) UNSIGNED DEFAULT NULL,
                supplier_id BIGINT(20) UNSIGNED DEFAULT NULL,
                total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
                tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
                status VARCHAR(50) NOT NULL DEFAULT 'draft',
                notes TEXT DEFAULT NULL,
                date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                date_updated DATETIME DEFAULT NULL,
                user_id BIGINT(20) UNSIGNED NOT NULL,
                PRIMARY KEY (id),
                KEY document_type (document_type),
                KEY document_number (document_number),
                KEY client_id (client_id),
                KEY supplier_id (supplier_id),
                KEY status (status)
            ) $charset_collate;",
            
            // Tabella righe documenti
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}erp_documents_rows (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                document_id BIGINT(20) UNSIGNED NOT NULL,
                product_id BIGINT(20) UNSIGNED DEFAULT NULL,
                variant_id BIGINT(20) UNSIGNED DEFAULT NULL,
                description TEXT NOT NULL,
                quantity DECIMAL(12,3) NOT NULL DEFAULT 0,
                unit_price DECIMAL(12,4) NOT NULL DEFAULT 0,
                discount_percent DECIMAL(5,2) DEFAULT 0,
                tax_percent DECIMAL(5,2) DEFAULT 0,
                row_total DECIMAL(12,2) NOT NULL DEFAULT 0,
                sort_order INT NOT NULL DEFAULT 0,
                PRIMARY KEY (id),
                KEY document_id (document_id)
            ) $charset_collate;",
            
            // Tabella log documenti
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}erp_documents_log (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                document_id BIGINT(20) UNSIGNED NOT NULL,
                action VARCHAR(50) NOT NULL,
                data LONGTEXT DEFAULT NULL,
                date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                user_id BIGINT(20) UNSIGNED NOT NULL,
                PRIMARY KEY (id),
                KEY document_id (document_id)
            ) $charset_collate;",
            
            // Tabella log eventi
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}erp_event_log (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                event_type VARCHAR(50) NOT NULL,
                object_type VARCHAR(50) DEFAULT NULL,
                object_id BIGINT(20) UNSIGNED DEFAULT NULL,
                message TEXT NOT NULL,
                date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                user_id BIGINT(20) UNSIGNED NOT NULL,
                PRIMARY KEY (id),
                KEY event_type (event_type),
                KEY object_type (object_type),
                KEY object_id (object_id)
            ) $charset_collate;",
            
            // Tabella chiavi API
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}erp_api_keys (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                api_key VARCHAR(64) NOT NULL,
                description VARCHAR(200) DEFAULT NULL,
                permissions VARCHAR(100) DEFAULT 'read',
                last_access DATETIME DEFAULT NULL,
                is_active TINYINT(1) DEFAULT 1,
                date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                user_id BIGINT(20) UNSIGNED NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY api_key (api_key)
            ) $charset_collate;",
            
            // Tabella log webhook
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}erp_webhook_log (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                webhook_id VARCHAR(100) NOT NULL,
                direction ENUM('in', 'out') NOT NULL,
                url TEXT DEFAULT NULL,
                data LONGTEXT DEFAULT NULL,
                response LONGTEXT DEFAULT NULL,
                status_code INT DEFAULT NULL,
                date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY webhook_id (webhook_id),
                KEY direction (direction)
            ) $charset_collate;"
        );
        
        // Esecuzione delle query di creazione tabelle
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        foreach ( $tables as $table_sql ) {
            dbDelta( $table_sql );
        }
    }

    /**
     * Crea i ruoli e i permessi di default
     */
    private function create_roles() {
        // Ruolo ERP Admin
        add_role(
            'erp_admin',
            __( 'ERP Admin', 'erp-core' ),
            array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'publish_posts' => false,
                'upload_files' => true,
            )
        );
        
        // Ruolo ERP Magazzino
        add_role(
            'erp_magazzino',
            __( 'ERP Magazzino', 'erp-core' ),
            array(
                'read' => true,
                'upload_files' => true,
            )
        );
        
        // Ruolo ERP Vendite
        add_role(
            'erp_vendite',
            __( 'ERP Vendite', 'erp-core' ),
            array(
                'read' => true,
                'upload_files' => true,
            )
        );
        
        // Ruolo ERP Contabilità
        add_role(
            'erp_contabilita',
            __( 'ERP Contabilità', 'erp-core' ),
            array(
                'read' => true,
                'upload_files' => true,
            )
        );
        
        // Assegna le capacità agli amministratori esistenti
        $admin_role = get_role( 'administrator' );
        if ( $admin_role ) {
            $admin_role->add_cap( 'manage_erp' );
            $admin_role->add_cap( 'view_erp_dashboard' );
            $admin_role->add_cap( 'manage_erp_settings' );
        }
    }

    /**
     * Crea le directory necessarie
     */
    private function create_directories() {
        $upload_dir = wp_upload_dir();
        $erp_dir = $upload_dir['basedir'] . '/erp-core';
        
        // Directory principale ERP
        if ( ! file_exists( $erp_dir ) ) {
            wp_mkdir_p( $erp_dir );
        }
        
        // Directory per i file temporanei
        if ( ! file_exists( $erp_dir . '/temp' ) ) {
            wp_mkdir_p( $erp_dir . '/temp' );
        }
        
        // Directory per i documenti PDF generati
        if ( ! file_exists( $erp_dir . '/documents' ) ) {
            wp_mkdir_p( $erp_dir . '/documents' );
        }
        
        // Directory per i report
        if ( ! file_exists( $erp_dir . '/reports' ) ) {
            wp_mkdir_p( $erp_dir . '/reports' );
        }
        
        // Directory per i backup
        if ( ! file_exists( $erp_dir . '/backups' ) ) {
            wp_mkdir_p( $erp_dir . '/backups' );
        }
        
        // Aggiungiamo file index.php in ogni directory per prevenire directory listing
        $this->create_index_files( $erp_dir );
    }
    
    /**
     * Crea file index.php vuoti nelle directory
     * 
     * @param string $base_dir Directory di base
     */
    private function create_index_files( $base_dir ) {
        $index_content = "<?php\n// Silence is golden.";
        
        // File index nella directory principale
        if ( ! file_exists( $base_dir . '/index.php' ) ) {
            file_put_contents( $base_dir . '/index.php', $index_content );
        }
        
        // Subdirectory
        $subdirectories = array( 'temp', 'documents', 'reports', 'backups' );
        
        foreach ( $subdirectories as $subdir ) {
            if ( ! file_exists( $base_dir . '/' . $subdir . '/index.php' ) ) {
                file_put_contents( $base_dir . '/' . $subdir . '/index.php', $index_content );
            }
        }
    }
}
