<?php
/*
 * Plugin Name:       Coupons by country for WooCommerce
 * Plugin URI:        https://wordpress.org/plugins/coupons-by-country-for-woocommerce/
 * Description:       Empower users with country-specific coupon selection for targeted redemption based on delivery addresses with this versatile plugin
 * Version:           1.1
 * Requires at least: 5.2
 * Requires PHP:      5.6
 * Author:            Dhrumil Kumbhani
 * Author URI:        https://in.linkedin.com/in/dhrumil-kumbhani-707b7b179?original_referer=https%3A%2F%2Fwww.google.com%2F
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       coupons-by-country-for-woocommerce
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit; 

// Add the "Country" tab to the coupon data tabs
function coupons_by_country_wc_tab($tabs) {
    $tabs['country'] = array(
        'label'     => __('Country', 'coupons-by-country-for-woocommerce'),
        'target'    => 'coupon_country_data',
        'class'     => 'show_if_coupon',
    );

    return $tabs;
}
add_filter('woocommerce_coupon_data_tabs', 'coupons_by_country_wc_tab');

// Save the country field when a coupon is saved
function coupons_by_country_wc_save_coupon_country($post_id) {
    if ( ! isset( $_POST['coupon_country_nonce'] ) || ! wp_verify_nonce( $_POST['coupon_country_nonce'], 'save_coupon_country' ) ) {
        return;
    }

    $coupon_country = isset($_POST['coupon_country']) ? sanitize_text_field($_POST['coupon_country']) : 'all';
    update_post_meta($post_id, 'coupon_country', $coupon_country);
}
add_action('woocommerce_coupon_options_save', 'coupons_by_country_wc_save_coupon_country');

// Display the content of the "Country" tab
function coupons_by_country_wc_tab_content() {
    $coupon_country = get_post_meta(get_the_ID(), 'coupon_country', true);
    ?>
    <div id="coupon_country_data" class="panel woocommerce_options_panel">
        <div class="options_group">
            <p class="form-field">
                <label for="coupon_country"><?php esc_html_e('Coupon Country', 'coupons-by-country-for-woocommerce'); ?></label>
                <select id="coupon_country" class="wc-enhanced-select" name="coupon_country">
                    <option value="all" <?php selected($coupon_country, 'all'); ?>><?php esc_html_e('All Countries', 'coupons-by-country-for-woocommerce'); ?></option>
                    <?php
                    $countries = WC()->countries->get_countries();
                    foreach ($countries as $code => $name) {
                        echo '<option value="' . esc_attr($code) . '" ' . selected($coupon_country, $code, false) . '>' . esc_html($name) . '</option>';
                    }
                    ?>
                </select>
                <?php wp_nonce_field( 'save_coupon_country', 'coupon_country_nonce' ); ?>
            </p>
        </div>
    </div>
    <?php
}
add_action('woocommerce_coupon_data_panels', 'coupons_by_country_wc_tab_content');


// Add the coupon field to the checkout page
function coupons_by_country_wc_checkout_coupon_form() {
    wc_get_template('checkout/coupon.php');
}
add_action('woocommerce_before_checkout_form', 'coupons_by_country_wc_checkout_coupon_form', 10);

// Remove the Apply Coupon field from the cart page
function coupons_by_country_wc_disable_coupon_field_on_cart( $enabled ) {
    if ( is_cart() ) {
        $enabled = false;
    }
    return $enabled;
}
add_filter( 'woocommerce_coupons_enabled', 'coupons_by_country_wc_disable_coupon_field_on_cart' );

// Check if the coupon is valid for the current user's country
function coupons_by_country_wc_check_coupon_country($valid, $coupon) {
    $user_country = WC()->customer->get_billing_country();
    $coupon_country = get_post_meta($coupon->get_id(), 'coupon_country', true);

    if ($coupon_country !== 'all' && $user_country !== $coupon_country) {
        $valid = false;
        wc_add_notice(__('This coupon is only valid for the selected country in the billing address.', 'coupons-by-country-for-woocommerce'), 'error');
    }

    return $valid;
}
add_filter('woocommerce_coupon_is_valid', 'coupons_by_country_wc_check_coupon_country', 10, 2);
