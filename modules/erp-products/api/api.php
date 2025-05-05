<?php
/**
 * API REST per il modulo Prodotti
 *
 * @package ERP_Core
 * @subpackage ERP_Prodotti
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Include i file API
 */
include_once dirname(__FILE__) . '/class-erp-prodotti-controller.php';

/**
 * Registra gli endpoint API
 */
function erp_prodotti_register_api_endpoints() {
    $controller = new ERP_Prodotti_Controller();
    $controller->register_routes();
}
add_action('rest_api_init', 'erp_prodotti_register_api_endpoints');
