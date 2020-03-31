<?php
/**
 * Products provider admin
 *
 * @package WooCommerce/Admin/Products/Provider
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<h2>
    <?php esc_html_e( 'Product Providers', 'woocommerce' ); ?>
    <?php echo wc_help_tip( __( 'Product providers will be used to dispatch an email every time an order is placed for such product', 'woocommerce' ) ); // @codingStandardsIgnoreLine ?>
</h2>

<table class="wc-shipping-classes wc-products-provider widefat">
    <thead>
    <tr>
        <?php foreach ( $products_provider_columns as $name => $heading ) : ?>
            <th class="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $heading ); ?></th>
        <?php endforeach; ?>
    </tr>
    </thead>
    <tfoot>
    <tr>
        <td colspan="<?php echo absint( count( $products_provider_columns ) ); ?>">
            <button type="submit" name="save" class="button button-primary wc-shipping-class-save wc-products-provider-save" value="<?php esc_attr_e( 'Save product providers', 'woocommerce' ); ?>" disabled><?php esc_html_e( 'Save product providers', 'woocommerce' ); ?></button>
            <a class="button button-secondary wc-shipping-class-add wc-products-provider-add" href="#"><?php esc_html_e( 'Add product provider', 'woocommerce' ); ?></a>
        </td>
    </tr>
    </tfoot>
    <tbody class="wc-shipping-class-rows wc-products-provider-rows"></tbody>
</table>

<script type="text/html" id="tmpl-wc-products-provider-row-blank">
    <tr>
        <td class="wc-shipping-classes-blank-state wc-products-provider-blank-state" colspan="<?php echo absint( count( $products_provider_columns ) ); ?>"><p><?php esc_html_e( 'No product providers have been created.', 'woocommerce' ); ?></p></td>
    </tr>
</script>

<script type="text/html" id="tmpl-wc-products-provider-row">
    <tr data-id="{{ data.term_id }}">
        <?php
        foreach ( $products_provider_columns as $name => $heading ) {
            echo '<td class="' . esc_attr( $name ) . '">';
            switch ( $name ) {
                case 'wc-products-provider-name':
                    ?>
                    <div class="view">
                        {{ data.name }}
                        <div class="row-actions">
                            <a class="wc-shipping-class-edit wc-products-provider-edit" href="#"><?php esc_html_e( 'Edit', 'woocommerce' ); ?></a> | <a href="#" class="wc-shipping-class-delete wc-products-provider-delete"><?php esc_html_e( 'Remove', 'woocommerce' ); ?></a>
                        </div>
                    </div>
                    <div class="edit">
                        <input type="text" name="name[{{ data.term_id }}]" data-attribute="name" value="{{ data.name }}" placeholder="<?php esc_attr_e( 'Product provider name', 'woocommerce' ); ?>" />
                        <div class="row-actions">
                            <a class="wc-shipping-class-cancel-edit wc-products-provider-cancel-edit" href="#"><?php esc_html_e( 'Cancel changes', 'woocommerce' ); ?></a>
                        </div>
                    </div>
                    <?php
                    break;
                case 'wc-products-provider-slug':
                    ?>
                    <div class="view">{{ data.slug }}</div>
                    <div class="edit"><input type="text" name="slug[{{ data.term_id }}]" data-attribute="slug" value="{{ data.slug }}" placeholder="<?php esc_attr_e( 'Slug', 'woocommerce' ); ?>" /></div>
                    <?php
                    break;
                case 'wc-products-provider-description':
                    ?>
                    <div class="view">{{ data.description }}</div>
                    <div class="edit">
                        <textarea name="description[{{ data.term_id }}]" data-attribute="description" placeholder="<?php esc_attr_e( 'Contact emails', 'woocommerce' ); ?>" rows="3" cols="50">{{ data.description }}</textarea>
                    </div>
                    <?php
                    break;
                default:
                    do_action( 'woocommerce_products_provider_column_' . $name );
                    break;
            }
            echo '</td>';
        }
        ?>
    </tr>
</script>
