<?php
/* Plugin Name: WooCommerce German Law
 * Description: Adds bits and pieces to comply with German law.
 * Version: 1.0
 * Author: Frederik Gossen 
 * Text Domain: woo-ger-law
 * Domain Path: /languages
 */

defined('ABSPATH') or die('No script kiddies please!');

/* load translations */
add_action('init', 'woo_ger_law_load_translation');
function woo_ger_law_load_translation() {
	load_plugin_textdomain('woo-ger-law', false, 'woo-commerce-german-law/languages/');
}

/* add settings to backend */
add_filter('woocommerce_settings_tabs_array', 'woo_ger_law_register_settings_tab', 50);
function woo_ger_law_register_settings_tab($settings_tabs) {
	$settings_tabs['ger_law'] = __('German Law', 'woo-ger-law');
	return $settings_tabs;
}
add_action('woocommerce_settings_tabs_ger_law', 'woo_ger_law_render_settings_tab');
function woo_ger_law_render_settings_tab() {
    woocommerce_admin_fields(woo_ger_law_settings());
}
add_action('woocommerce_update_options_ger_law', 'woo_ger_law_save');
function woo_ger_law_save() {
    woocommerce_update_options(woo_ger_law_settings());
}
function woo_ger_law_settings() {
    $settings = array(
	array(
		'name' => __('Required Pages', 'woo-ger-law'), 
		'type' => 'title'), 
	array(
		'title'    => __('Shipping cost', 'woo-ger-law'),
		'desc'     => __('If defined this page should state shipping costs and will be linked after each price statement.', 'woo-ger-law'),
		'id'       => 'woo_ger_law_shipping_page_id',
		'default'  => '', 
		'class'    => 'wc-enhanced-select-nostd', 
		'css'      => 'min-width:300px;', 
		'type'     => 'single_select_page', 
		'args'     => array(
			'exclude' => wc_get_page_id('checkout')), 
		'desc_tip' => true, 
		'autoload' => false), 
	/* this field is more or less copied from woocommerce '/includes/admin/settings/class-wc-settings-checkout.php' for convenience. */
	array(
		'title'    => __('Terms and conditions', 'woo-ger-law'),
		'desc'     => __('If you define a terms page the customer will be asked if they accept them when checking out.', 'woo-ger-law'),
		'id'       => 'woocommerce_terms_page_id',
		'default'  => '', 
		'class'    => 'wc-enhanced-select-nostd', 
		'css'      => 'min-width:300px;', 
		'type'     => 'single_select_page', 
		'args'     => array(
			'exclude' => wc_get_page_id('checkout')), 
		'desc_tip' => true, 
		'autoload' => false), 
	array(
		'title'    => __('Cancellation policy', 'woo-ger-law'),
		'desc'     => __('If defined this page should state the cancellation policy and the customer will be asked if they accept it together with the terms when checking out. This choice has no effect if no terms page was selected.', 'woo-ger-law'),
		'id'       => 'woo_ger_law_cancellation_policy_page_id',
		'default'  => '', 
		'class'    => 'wc-enhanced-select-nostd', 
		'css'      => 'min-width:300px;', 
		'type'     => 'single_select_page', 
		'args'     => array(
			'exclude' => wc_get_page_id('checkout')), 
		'desc_tip' => true, 
		'autoload' => false), 
        array(
             'type' => 'sectionend'));
    return apply_filters('woo_ger_law_settings', $settings);
}

/* this hack is the only way to hook into the checkout site without writing a child theme. */
add_filter('gettext', 'woo_ger_law_gettext_filter', 20, 3);
function woo_ger_law_gettext_filter($translated_text, $text, $domain) {
	if ($domain != 'woo-ger-law') {
		switch ($text) {
			case 'Proceed to PayPal':
			$translated_text = __('Pay now with PayPal', 'woo-ger-law');
			break;
			case 'Place order':
			$translated_text = __('Pay now', 'woo-ger-law');
			break;
			case 'I&rsquo;ve read and accept the <a href="%s" target="_blank" class="woocommerce-terms-and-conditions-link">terms &amp; conditions</a>':
			$terms_page_id = wc_get_page_id('terms');
			$cancellation_policy_page_id = get_option('woo_ger_law_cancellation_policy_page_id');
			if (empty($cancellation_policy_page_id)) {
				$terms_link = esc_url(wc_get_page_permalink('terms'));
				$translated_text = sprintf(__('I&rsquo;ve read and accept the <a href="%s" target="_blank">terms &amp; conditions</a>', 'woo-ger-law'), $terms_link);
			}
			else {
				$terms_link = esc_url(wc_get_page_permalink('terms'));
				$cancellation_policy_link = esc_url(get_page_link($cancellation_policy_page_id));
				$translated_text = sprintf(__('I&rsquo;ve read and accept the <a href="%s" target="_blank">terms &amp; conditions</a> and also the <a href="%s" target="_blank">cancellation policy</a>', 'woo-ger-law'), $terms_link, $cancellation_policy_link);
			}
			break;
		}
	}
	return $translated_text;
}

/* add shipping cost link to product prices */
/* product details link must close before price tag to ensure that we do not create a link within a link */
add_action('after_setup_theme', 'woo_ger_law_remove_shop_item_link_close', 0);
function woo_ger_law_remove_shop_item_link_close() {
	$priority_as_defined_by_storefront = 5;
	remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', $priority_as_defined_by_storefront);
}
add_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_product_link_close', 0); 
add_filter('woocommerce_get_price_html', 'woo_ger_law_get_price_html_filter', 15, 2);
function woo_ger_law_get_price_html_filter($price_html, $product) {
	$shipping_page_id = get_option('woo_ger_law_shipping_page_id');
	if (!empty($shipping_page_id)) {
		$shipping_page_link = esc_url(get_page_link($shipping_page_id));
		$price_html .= ' <small>' . sprintf(__('plus <a href="%s">shipping</a>', 'woo-ger-law'), $shipping_page_link) . '</small>';
	}
	return $price_html;
}
?>
