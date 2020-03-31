<?php
/**
 * @package INDIGIT
 * @version 1.0.0
 */
/*
Plugin Name: WooCommerce Providers by INDIGIT
Plugin URI: https://indigit.pt
Description: This connects WooCommerce to product providers and dispatches an email
Author: INDIGIT®
Version: 1.0.0
Author URI: https://indigit.pt
*/

define('WC_PRODUCTS_PROVIDER_PLUGIN_FILE', __FILE__);
define('WC_PRODUCTS_PROVIDER_PLG_DIR', WP_PLUGIN_DIR . '/' . plugin_basename(dirname(__FILE__)));
define('WC_PRODUCTS_PROVIDER_DIR_LOGS', WC_PRODUCTS_PROVIDER_PLG_DIR . '/logs');

require_once __DIR__ . '/src/Woocommerce_Providers.php';
require_once __DIR__ . '/src/Woocommerce_ProductsProvider.php';

// Bind
\Woocommerce_Providers::instance()
    ->registerPluginActivators()
    ->registerAdminMenu()
    ->registerListeners();


// Debug & Helpers
/**
 * Log debug message
 *
 * @param string prefix
 * @param string|object $message
 * @param array $data
 */
if (!function_exists('indigit_log')) {
    function indigit_log($prefix = 'info', $message = '', $data = [])
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        if ($message instanceof \Exception) {
            $data = array_merge($data, [
                '_file' => $message->getFile(),
                '_line' => $message->getLine(),
                '_trace' => $message->getTraceAsString()
            ]);
            $message = $message->getMessage();
        }
        $dateTime = new DateTime();
        $logStr = strtoupper($prefix) . ': ';
        $logStr .= $message . ' ';
        $logStr .= json_encode($data);
        $logStr .= "\n\n";

        try {
            file_put_contents(WC_PRODUCTS_PROVIDER_DIR_LOGS . '/log-' . $dateTime->format('Y-m-d') . '.log', $logStr, FILE_APPEND);
        } catch (\Exception $e) {
        }
    }

}
