<?php
/**
 * Integrazione WooCommerce
 * 
 * Questa classe gestisce l'integrazione con WooCommerce, fornendo
 * funzionalità di sincronizzazione tra l'ERP e lo store online.
 * 
 * @package ERP_Core
 */

// Impedisce l'accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

class ERP_Core_WooCommerce {
    /**
     * Istanza singola della classe (singleton)
     * @var ERP_Core_WooCommerce
     */
    private static $instance = null;

    /**
     * Ottiene l'istanza singola della classe
     * @return ERP_Core_WooCommerce
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Costruttore della classe
     */
    private function __construct() {
        // Verifica che WooCommerce sia attivo
        if (!$this->is_woocommerce_active()) {
            return;
        }

        // Inizializza l'integrazione
        add_action('init', array($this, 'init'));
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
     * Inizializza l'integrazione con WooCommerce
     */
    public function init() {
        // Per ora, nessuna azione specifica
        // Qui verranno aggiunte tutte le funzioni di integrazione
    }

    /**
     * Sincronizza i prodotti dall'ERP a WooCommerce
     * 
     * @return bool
     */
    public function sync_products_to_woocommerce() {
        // Implementazione futura
        return true;
    }

    /**
     * Sincronizza gli ordini da WooCommerce all'ERP
     * 
     * @return bool
     */
    public function sync_orders_from_woocommerce() {
        // Implementazione futura
        return true;
    }

    /**
     * Sincronizza i clienti da WooCommerce all'ERP
     * 
     * @return bool
     */
    public function sync_customers_from_woocommerce() {
        // Implementazione futura
        return true;
    }
}