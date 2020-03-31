<?php

class Woocommerce_ProductsProvider
{

    /**
     * Stores the product providers
     *
     * @var array
     */
    public $products_providers = [];

    /**
     * The single instance of the class.
     *
     * @var \Woocommerce_ProductsProvider
     * @since 2.1.0
     */
    protected static $_instance = null;

    /**
     * Main Woocommerce_ProductProvider Instance.
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

    /**
     * Get an array of product providers
     *
     * @return array
     */
    public function get_products_providers()
    {
        if (empty($this->products_providers)) {
            $providers = get_terms(
                'products_provider',
                [
                    'hide_empty' => '0',
                    'orderby' => 'name',
                ]
            );
            $this->products_providers = !is_wp_error($providers) ? $providers : [];
        }
        return apply_filters('woocommerce_get_products_providers', $this->products_providers);
    }

    /**
     * Get an array of product providers with key
     *
     * @return \WP_Term[]
     */
    public function get_products_providers_with_key()
    {
        $list = [];
        $providers = $this->get_products_providers();
        foreach ($providers as $provider) {
            /** @var \WP_Term $provider */
            $list[$provider->slug] = $provider;
        }
        return $list;
    }

}
