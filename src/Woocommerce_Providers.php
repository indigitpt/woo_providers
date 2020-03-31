<?php

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
        add_action('admin_enqueue_scripts', function () {
            $version = \Automattic\Jetpack\Constants::get_constant('WC_VERSION');

            $url = untrailingslashit(plugins_url('/', WC_PRODUCTS_PROVIDER_PLUGIN_FILE));
            wp_register_script('wc-products-provider', $url . '/assets/js/admin/wc-products-provider.js', array('jquery', 'wp-util', 'underscore', 'backbone'), $version);
        });

        /**
         * Create the section beneath the products tab
         **/
        add_filter('woocommerce_get_sections_products', function ($sections) {
            $sections['providers'] = __('Products Providers', 'woocommerce');
            return $sections;
        });

        /**
         * Add settings to the specific section we created before
         */
        add_filter('woocommerce_get_settings_products', function ($settings, $current_section) {
            if ($current_section == 'providers') {
                $settings_slider = [];
//                // Add Title to the Settings
//                $settings_slider[] = [
//                    'name' => __('Product Providers', 'woocommerce'),
//                    'type' => 'title',
//                    'desc' => __('The following options are used to configure the product providers', 'woocommerce'),
//                    'id' => 'providers'
//                ];
//
//                // ...
//
//                $settings_slider[] = [
//                    'type' => 'sectionend',
//                    'id' => 'providers'
//                ];

                $this->output_providers_screen();

                return $settings_slider;
            }

            return $settings;
        }, 10, 2);

        register_taxonomy(
            'products_provider',
            apply_filters('woocommerce_taxonomy_objects_products_provider', array('product', 'product_variation')),
            apply_filters(
                'woocommerce_taxonomy_args_products_provider',
                array(
                    'hierarchical' => false,
                    'update_count_callback' => '_update_post_term_count',
                    'label' => __('Products Providers', 'woocommerce'),
                    'labels' => array(
                        'name' => __('Products Providers', 'woocommerce'),
                        'singular_name' => __('Product Provider', 'woocommerce'),
                        'menu_name' => _x('Products Providers', 'Admin menu name', 'woocommerce'),
                        'search_items' => __('Search products providers', 'woocommerce'),
                        'all_items' => __('All products providers', 'woocommerce'),
                        'parent_item' => __('Parent product provider', 'woocommerce'),
                        'parent_item_colon' => __('Parent product provider:', 'woocommerce'),
                        'edit_item' => __('Edit product provider', 'woocommerce'),
                        'update_item' => __('Update product provider', 'woocommerce'),
                        'add_new_item' => __('Add new product provider', 'woocommerce'),
                        'new_item_name' => __('New product provider Name', 'woocommerce'),
                    ),
                    'show_ui' => false,
                    'show_in_quick_edit' => false,
                    'show_in_nav_menus' => false,
                    'query_var' => is_admin(),
                    'capabilities' => array(
                        'manage_terms' => 'manage_product_terms',
                        'edit_terms' => 'edit_product_terms',
                        'delete_terms' => 'delete_product_terms',
                        'assign_terms' => 'assign_product_terms',
                    ),
                    'rewrite' => false,
                )
            )
        );

        add_filter('yith_wcbep_get_custom_taxonomies', function ($custom_taxonomies) {
            $custom_taxonomies[] = 'products_provider';
            return $custom_taxonomies;
        });

        add_filter('manage_taxonomies_for_product_columns', function ($taxonomies) {
            $taxonomies[] = 'products_provider';
            return $taxonomies;
        });

        add_action('wp_ajax_woocommerce_products_provider_save_changes', function () {
            if (!isset($_POST['wc_products_provider_nonce'], $_POST['changes'])) {
                wp_send_json_error('missing_fields');
                wp_die();
            }

            if (!wp_verify_nonce(wp_unslash($_POST['wc_products_provider_nonce']), 'wc_products_provider_nonce')) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                wp_send_json_error('bad_nonce');
                wp_die();
            }

            if (!current_user_can('manage_woocommerce')) {
                wp_send_json_error('missing_capabilities');
                wp_die();
            }

            $changes = wp_unslash($_POST['changes']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

            foreach ($changes as $term_id => $data) {
                $term_id = absint($term_id);

                if (isset($data['deleted'])) {
                    if (isset($data['newRow'])) {
                        // So the user added and deleted a new row.
                        // That's fine, it's not in the database anyways. NEXT!
                        continue;
                    }
                    wp_delete_term($term_id, 'products_provider');
                    continue;
                }

                $update_args = array();

                if (isset($data['name'])) {
                    $update_args['name'] = wc_clean($data['name']);
                }

                if (isset($data['slug'])) {
                    $update_args['slug'] = wc_clean($data['slug']);
                }

                if (isset($data['description'])) {
                    $update_args['description'] = wc_clean($data['description']);
                }

                if (isset($data['newRow'])) {
                    $update_args = array_filter($update_args);
                    if (empty($update_args['name'])) {
                        continue;
                    }
                    $inserted_term = wp_insert_term($update_args['name'], 'products_provider', $update_args);
                    $term_id = is_wp_error($inserted_term) ? 0 : $inserted_term['term_id'];
                } else {
                    wp_update_term($term_id, 'products_provider', $update_args);
                }

                do_action('woocommerce_products_providers_save_class', $term_id, $data);
            }

            $wc_products_provider = Woocommerce_ProductsProvider::instance();
            wp_send_json_success(
                array(
                    'products_providers' => $wc_products_provider->get_products_providers(),
                )
            );
        });

        // Add order extra information
        add_action('add_meta_boxes', function () {
            add_meta_box('wc_products_providers_add_meta_box', 'Product Providers', [$this, 'showWCPPView'], 'shop_order', 'side', 'core');
        });

        // Product edit page related

        // woocommerce_before_' . $this->object_type . '_object_save
        add_action('woocommerce_product_object_updated_props', [$this, 'updateProductAction']);
        add_action('woocommerce_product_options_general_product_data', function () {
            global $product_object;
            /** @var \WC_Product_Simple $product_object */

            $terms = get_the_terms($product_object->get_id(), 'products_provider');
            $args = array(
                'taxonomy'         => 'products_provider',
                'hide_empty'       => 0,
                'show_option_none' => __( 'No Product Provider', 'woocommerce' ),
                'name'             => 'products_provider',
                'id'               => 'products_provider',
                'selected'         => !empty($terms) ? current($terms)->term_id : null,
                'class'            => 'select short',
                'orderby'          => 'name',
            );
            ?>
            <p class="form-field shipping_class_field">
                <label for="product_provider"><?php esc_html_e( 'Product Provider', 'woocommerce' ); ?></label>
                <?php wp_dropdown_categories( $args ); ?>
                <?php echo wc_help_tip( __( 'The product provider will be used to be notified when a product is sold.', 'woocommerce' ) ); ?>
            </p>
            <?php
        });

        add_action('admin_menu', function () {
            // Add WCPP management page menu item
            add_submenu_page(null, 'WCPP', 'WCPP', 'manage_options', 'woocommerce_products_providers_notify', function () {
                $order_id = $_GET['order_id'];

                // Fetch order from database
                $this->order = new \WC_Order((int)$order_id);

                try {
                    null !== $this->order && $this->processProviders();
                    wp_redirect(admin_url(sprintf('post.php?post=%s&action=edit', $order_id)));
                    exit;
                } catch (\Exception $e) {
                    // Store some log info
                    indigit_log('>> WCPP', $e);

                    echo $e->getMessage();
                }
            });
        });

        return $this;
    }

    /**
     * @param \WC_Product_Simple $product
     */
    public function updateProductAction($product)
    {
        $terms = get_the_terms($product->get_id(), 'products_provider');
        $term = !empty($terms) ? current($terms)->term_id : null;
        $new = array_key_exists('products_provider', $_POST) ? intval(wc_clean($_POST['products_provider'])) : null;

        if ($new !== $term) {
            wp_set_post_terms( $product->get_id(), array( $new ), 'products_provider', false );
        }
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
                $this->processProviders();
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
    protected function output_providers_screen()
    {
        global $hide_save_button;
        $hide_save_button = true;

        $wc_products_provider = Woocommerce_ProductsProvider::instance();
        wp_localize_script(
            'wc-products-provider',
            'productsProviderLocalizeScript',
            array(
                'classes' => $wc_products_provider->get_products_providers(),
                'default_products_provider' => array(
                    'term_id' => 0,
                    'name' => '',
                    'description' => '',
                ),
                'wc_products_provider_nonce' => wp_create_nonce('wc_products_provider_nonce'),
                'strings' => array(
                    'unload_confirmation_msg' => __('Your changed data will be lost if you leave this page without saving.', 'woocommerce'),
                    'save_failed' => __('Your changes were not saved. Please retry.', 'woocommerce'),
                ),
            )
        );
        wp_enqueue_script('wc-products-provider');

        // Extendable columns to show on the shipping classes screen.
        $products_provider_columns = apply_filters(
            'woocommerce_products_provider_columns',
            array(
                'wc-products-provider-name' => __('Name', 'woocommerce'),
                'wc-products-provider-slug' => __('Slug', 'woocommerce'),
                'wc-products-provider-description' => __('Emails (comma separated)', 'woocommerce'),
            )
        );

        include_once WC_PRODUCTS_PROVIDER_PLG_DIR . '/views/html-admin-page-products-provider.php';
    }

    /**
     * Show order extra info related to Products Providers
     *
     * @param \WP_Post $post
     */
    public function showWCPPView($post)
    {
        if (in_array($post->post_status, ['wc-completed'])):
            // If we already have references for
            $wcPPList = get_post_meta($post->ID, '_wc_product_providers', true);
            $wcPPList = $wcPPList !== '' ? json_decode($wcPPList, true) : [];

            $wc_product_provider = Woocommerce_ProductsProvider::instance();
            $providers = $wc_product_provider->get_products_providers_with_key();

            echo '<ul>';
            foreach ($wcPPList as $providerSlug => $count):
                echo sprintf('<li><strong>%s</strong>: %s product(s)</li>', $providers[$providerSlug]->name, $count);
            endforeach;
            echo '</ul>';

            if (empty($wcPPList)): ?>
                <a type="button" class="button button-primary"
                   href="<?php echo admin_url('admin.php?page=woocommerce_products_providers_notify&order_id=' . $post->ID); ?>"
                   style="margin-top: 10px; float:right;">Notify Providers</a>
            <?php endif; ?>

            <div style="clear:both"></div>

        <?php endif;
    }

    /**
     * Get list of products by providers
     *
     * @return array
     */
    protected function getProductsWithProviders()
    {
        $list = [];
        foreach ($this->order->get_items() as $item) {
            /** @var \WC_Product $product */
            $product = $item->get_product();
            $terms = get_the_terms($product->get_id(), 'products_provider');

            if ($terms && !is_wp_error($terms)) {
                $productProvider = current($terms);
                $list[$productProvider->slug][] = [$item, $product];
            }
        }
        return $list;
    }

    /**
     * Dispatch emails to providers
     */
    public function processProviders()
    {
        $wc_product_provider = Woocommerce_ProductsProvider::instance();
        $providers = $wc_product_provider->get_products_providers_with_key();

        $subject = sprintf('Envio de Documentos (%s Venda #%s)', get_option(''), $this->order->get_id());
        $replace = [
            '{{order_id}}' => $this->order->get_id(),
            '{{company_name}}' => get_option('blogname'),
            '{{date}}' => date('Y-m-d'),
        ];

        $success = get_post_meta($this->order->get_id(), '_wc_product_providers', true);
        $success = $success !== '' ? json_decode($success, true) : [];

        foreach ($this->getProductsWithProviders() as $provider_slug => $data) {
            $provider = $providers[$provider_slug];
            if (array_key_exists($provider_slug, $success)) {
                continue; // already notified
            }

            $message = str_replace(array_keys($replace), $replace, $this->getEmailTemplate($data));

            indigit_log('MAIL', $this->order->get_id(), ['content' => $message]); // log
            wp_mail($provider->description, $subject, $message, ['Content-Type: text/html; charset=UTF-8']);

            $this->order->add_order_note(__('Product provider notified: ' . $provider->name));
            $success[$provider_slug] = count($data);
        }

        update_post_meta($this->order->get_id(), '_wc_product_providers', json_encode($success));
    }

    /**
     * Get email template
     *
     * @return string
     */
    protected function getEmailTemplate($data)
    {
        $productsHTML = '';
        foreach ($data as $each) {
            [$item, $product] = $each;
            /** @var \WC_Order_Item $item */
            /** @var \WC_Product $product */
            $productsHTML .= sprintf('<tr><td max-width="300px">%s</td><td>x %s</td></tr>', $product->get_formatted_name(), $item->get_quantity());
        }

        $template = <<<EOL
<table width="800" border="0" cellspacing="0" cellpadding="0" align="center" style="color:#333333;font-family:Arial, Helvetica, sans-serif, Tahoma, Verdana, Geneva;font-size:12px;line-height:150%;">
	<tbody>
		<tr>
			<td style="text-align:left;padding:10px 0px 10px 10px;border-bottom:1px solid #dddddd;">
				<table width="100%" cellpadding="0" cellspacing="0" border="0" style="color:#333333;font-family:Arial, Helvetica, sans-serif, Tahoma, Verdana, Geneva;font-size:12px;line-height:150%;">
					<tbody>
						<tr>
							<td style="width:380px;text-align:left;vertical-align:bottom;">
								{{company_name}}
							</td>
							
							<td style="text-align:right;vertical-align:bottom;font-size:34px;font-family:Arial, Helvetica, sans-serif;color:#81A824;padding:0 10px 10px 0;">
								<span style="font-size:11px;font-style:italic;color:#999999;display:block;">{{date}}</span>
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
								Segue em anexo os productos para a encomenda #{{order_id}}.<br>
								<p>Com os melhores cumprimentos,<br>{{company_name}}</p>
							</td>
						</tr>
						$productsHTML
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
