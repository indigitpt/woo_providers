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

define('INDIGIT_PLG_DIR', WP_PLUGIN_DIR . '/' . plugin_basename(dirname(__FILE__)));
define('INDIGIT_PLG_DIR_LOGS', INDIGIT_PLG_DIR . '/logs');

define('INDIGIT_PROVIDERS_ENABLED', 'indigit_providers_enabled');


class Woocommerce_Providers
{

    /**
     * @var null|\WC_Order
     */
    protected $order = null;

    /**
     * The single instance of the class.
     *
     * @var \Woocommerce_Providers
     * @since 2.1.0
     */
    protected static $_instance = null;

    /**
     * Main Woocommerce_Providers Instance.
     *
     * Ensures only one instance of INDIGIT_Manager is loaded or can be loaded.
     *
     * @return static
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function registerPluginActivators()
    {
//        // activation
//        register_activation_hook(__FILE__, function () {
//            global $wpdb;
//
//            $wpdb->query(
//                "CREATE TABLE IF NOT EXISTS `indigit_providers`(
//                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
//                provider VARCHAR(100),
//                email_list TEXT,
//                changed TIMESTAMP default CURRENT_TIMESTAMP
//			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;"
//            );
//
////            $wpdb->query("INSERT INTO `attres`(config, description) VALUES('provider_id', 'Escolha uma Série de Documentos para melhor organização')");
//        });
//        // deactivation
//        register_deactivation_hook(__FILE__, function () {
//            global $wpdb;
//            $wpdb->query("DROP TABLE indigit_providers");
//            // @todo attrs
//        });

        return $this;
    }

    /**
     * Register administration menu
     */
    public function registerAdminMenu()
    {
        /**
         * Create the section beneath the products tab
         **/
        add_filter('woocommerce_get_sections_products', function ($sections) {
            $sections['providers'] = __( 'Product Providers', 'woocommerce' );
            return $sections;
        });

        /**
         * Add settings to the specific section we created before
         */
        add_filter('woocommerce_get_settings_products', function ($settings, $current_section) {
            if ($current_section == 'providers') {
                $settings_slider = [];
                // Add Title to the Settings
                $settings_slider[] = [
                    'name' => __('Product Providers', 'woocommerce'),
                    'type' => 'title',
                    'desc' => __('The following options are used to configure the product providers', 'woocommerce'),
                    'id' => 'providers'
                ];

                $settings_slider[] = [
                    'id' => INDIGIT_PROVIDERS_ENABLED,
                    'type' => 'checkbox',
                    'desc' => __('Activar', 'woocommerce'),
                    'default' => 'no',
                    'desc_tip' => false,
                    'show_if_checked' => 'yes',
                    'checkboxgroup' => 'end',
                ];

                $settings_slider[] = [
                    'type' => 'sectionend',
                    'id' => 'providers'
                ];


                $this->output_providers_screen();

                return $settings_slider;
            }

            return $settings;
        }, 10, 2 );

        return $this;
    }

    /**
     * Register order listeners
     *
     * @link https://docs.woocommerce.com/wp-content/uploads/2013/05/woocommerce-order-process-diagram.png
     * @return static
     */
    public function registerListeners()
    {
        add_action('woocommerce_order_status_completed', function ($order_id) {
            $this->order = new \WC_Order((int)$order_id);

            try {
                get_option(INDIGIT_PROVIDERS_ENABLED) === 'yes' && $this->processProviders();
            } catch (\Exception $e) {
                // Store some log info
                indigit_log('>> WC_PROVIDERS', $e);
            }
        });

        return $this;
    }

    /**
     * Show method for a zone
     */
    protected function output_providers_screen() {
        $zone = WC_Shipping_Zones::get_zone(1);

        if ( ! $zone ) {
            wp_die( esc_html__( 'Zone does not exist!', 'woocommerce' ) );
        }

        $allowed_countries   = WC()->countries->get_shipping_countries();
        $shipping_continents = WC()->countries->get_shipping_continents();

        // Prepare locations.
        $locations = array();
        $postcodes = array();

        foreach ( $zone->get_zone_locations() as $location ) {
            if ( 'postcode' === $location->type ) {
                $postcodes[] = $location->code;
            } else {
                $locations[] = $location->type . ':' . $location->code;
            }
        }

        wp_localize_script(
            'wc-shipping-zone-methods',
            'shippingZoneMethodsLocalizeScript',
            array(
                'methods'                 => $zone->get_shipping_methods( false, 'json' ),
                'zone_name'               => $zone->get_zone_name(),
                'zone_id'                 => $zone->get_id(),
                'wc_shipping_zones_nonce' => wp_create_nonce( 'wc_shipping_zones_nonce' ),
                'strings'                 => array(
                    'unload_confirmation_msg' => __( 'Your changed data will be lost if you leave this page without saving.', 'woocommerce' ),
                    'save_changes_prompt'     => __( 'Do you wish to save your changes first? Your changed data will be discarded if you choose to cancel.', 'woocommerce' ),
                    'save_failed'             => __( 'Your changes were not saved. Please retry.', 'woocommerce' ),
                    'add_method_failed'       => __( 'Shipping method could not be added. Please retry.', 'woocommerce' ),
                    'yes'                     => __( 'Yes', 'woocommerce' ),
                    'no'                      => __( 'No', 'woocommerce' ),
                    'default_zone_name'       => __( 'Zone', 'woocommerce' ),
                ),
            )
        );
        wp_enqueue_script( 'wc-shipping-zone-methods' );

        include_once dirname( __FILE__ ) . '/views/html-admin-page-products-providers.php';
    }

    /**
     * Dispatch emails to providers
     */
    public function processProviders()
    {

//        if (defined('EMAIL_SEND_INTERNAL')) {
//            $subject = sprintf('Envio de Documentos (%s Venda #%s)', get_option(INDIGIT_CTT_REFERENCE_PREFIX), $this->order->get_id());
//
//            $replace = [
//                '{{nome_empresa}}' => get_option(INDIGIT_CTT_REFERENCE_PREFIX),
//                '{{data_hoje}}' => date('Y-m-d'),
//                '{{nome_cliente}}' => $this->order->get_billing_first_name() . ' ' . $this->order->get_billing_last_name(),
//                '{{documento_numero}}' => $this->order->get_id(),
//                '{{link}}' => esc_url(admin_url(sprintf('post.php?post=%s&action=edit', $this->order->get_id()))),
//            ];
//            $message = str_replace(array_keys($replace), $replace, $this->getEmailTemplate());
//
//            indigit_log('MAIL', $this->order->get_id(), ['content' => $message, $cttPDF, $invoicePDF]); // log
//            wp_mail(EMAIL_SEND_INTERNAL, $subject, $message, ['Content-Type: text/html; charset=UTF-8'], array_filter([$cttPDF, $invoicePDF]));
//
//            $this->order->add_order_note(__('Documentos enviados para: ' . EMAIL_SEND_INTERNAL));
//        }
    }

    /**
     * Get email template
     *
     * @return string
     */
    protected function getEmailTemplate()
    {
        $template =<<<EOL
<table width="800" border="0" cellspacing="0" cellpadding="0" align="center" style="color:#333333;font-family:Arial, Helvetica, sans-serif, Tahoma, Verdana, Geneva;font-size:12px;line-height:150%;">
	<tbody>
		<tr>
			<td style="text-align:left;padding:10px 0px 10px 10px;border-bottom:1px solid #dddddd;">
				<table width="100%" cellpadding="0" cellspacing="0" border="0" style="color:#333333;font-family:Arial, Helvetica, sans-serif, Tahoma, Verdana, Geneva;font-size:12px;line-height:150%;">
					<tbody>
						<tr>
							<td style="width:380px;text-align:left;vertical-align:bottom;">
								{{nome_empresa}}
							</td>
							
							<td style="text-align:right;vertical-align:bottom;font-size:34px;font-family:Arial, Helvetica, sans-serif;color:#81A824;padding:0 10px 10px 0;">
								<span style="font-size:11px;font-style:italic;color:#999999;display:block;">{{data_hoje}}</span>
								<span style="font-size:22px;">Documentos em anexo</span>
							</td>
						</tr>
					</tbody>
				</table>
			</td>
		</tr>
		<tr>
			<td style="text-align:left;padding:10px 0px 10px 0px;">			
				<table width="100%" border="0" cellspacing="0" cellpadding="0" style="color:#333333;font-family:Arial, Helvetica, sans-serif, Tahoma, Verdana, Geneva;font-size:12px;line-height:150%;">
					<tbody>
						<tr>
							<td style="text-align:left;vertical-align:top;padding:10px 6px 6px 6px;font-family:Arial, Helvetica, sans-serif, Tahoma, Verdana, Geneva;font-size:12px;line-height:150%;">
								Segue em anexo os documentos para a venda #{{documento_numero}} para o cliente <b>{{nome_cliente}}</b>.<br>
								<a href="{{link}}">Link directo para a compra</a></br>
								<p>Com os melhores cumprimentos,<br>{{nome_empresa}}</p>
							</td>
						</tr>
						<tr>
							<td style="height:40px;">&nbsp;</td>
						</tr>
					</tbody>
				</table>
			</td>
		</tr>
	</tbody>
</table>
EOL;

        return $template;
    }

}


/**
 * Get INDIGIT Woocommerce Providers plugin
 *
 * @return \Woocommerce_Providers
 */
function woocommerce_providers()
{
    return \Woocommerce_Providers::instance();
}

// Bind
woocommerce_providers()
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
if(!function_exists('indigit_log')){
    function indigit_log($prefix = 'info', $message = '', $data = [])
    {

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
            file_put_contents(INDIGIT_PLG_DIR_LOGS . '/log-' . $dateTime->format('Y-m-d') . '.log', $logStr, FILE_APPEND);
        } catch (\Exception $e) {
        }
    }

}
