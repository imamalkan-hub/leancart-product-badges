<?php
/**
 * Plugin Name: LeanCart Product Badges
 * Plugin URI:  https://landscapingcostguide.com/
 * Description: Automatically show badges such as "New", "Best Seller", and "Last 3 items" by category or tag. Makes product cards stand out.
 * Version:     1.0.0
 * Author:      LeanCart
 * Author URI:  https://leancart.dev
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: product-badges
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'LEANCART_PRODUCT_BADGES_DEMO_SITE' ) ) {
	define( 'LEANCART_PRODUCT_BADGES_DEMO_SITE', 'https://landscapingcostguide.com' );
}

require_once __DIR__ . '/includes/class-leancart-framework.php';

final class LeanCart_Product_Badges {

	const SLUG = 'product-badges';

	public static function init() {
		LeanCart_Framework::boot( array(
			'slug'    => self::SLUG,
			'name'    => 'LeanCart Product Badges',
			'version' => '1.0.0',
			'file'    => __FILE__,
			'site'    => LEANCART_PRODUCT_BADGES_DEMO_SITE,
		) );

		// WooCommerce may load after these plugins; defer the check to runtime.
		add_action( 'admin_notices', array( __CLASS__, 'wc_notice' ) );

		load_plugin_textdomain( 'product-badges', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		add_action( 'leancart_' . self::SLUG . '_settings_fields', array( __CLASS__, 'fields' ) );

		// WooCommerce may load after this plugin; hooks are registered once WC is ready.
		if ( LeanCart_Framework::is_wc() ) {
			self::hooks();
		} else {
			add_action( 'woocommerce_loaded', array( __CLASS__, 'hooks' ) );
		}
	}

	public static function wc_notice() {
		if ( LeanCart_Framework::is_wc() ) { return; }
		echo '<div class="notice notice-warning"><p><strong>' . esc_html( 'LeanCart Product Badges' ) . '</strong>: ';
		esc_html_e( 'This plugin requires WooCommerce to be active.', 'product-badges' );
		echo '</p></div>';
	}

	public static function fields() {
		LeanCart_Framework::field_text( 'rules', __('Badge rules', 'product-badges'), __('One per line: category-slug|Badge text', 'product-badges'), 'textarea' );
		LeanCart_Framework::field_text( 'color', __('Badge color', 'product-badges'), __('E.g.: #b32d2e', 'product-badges'), 'text' );
	}


	public static function hooks() {
		add_action( 'woocommerce_before_shop_loop_item_title', array( __CLASS__, 'badge' ), 5 );
		add_action( 'woocommerce_before_single_product_summary', array( __CLASS__, 'badge' ), 5 );
	}

	public static function badge() {
		global $product;
		if ( ! $product ) { return; }
		$label = '';
		foreach ( explode( "\n", LeanCart_Framework::setting( 'rules' ) ) as $line ) {
			$line = trim( $line );
			if ( false === strpos( $line, '|' ) ) { continue; }
			list( $slug, $text ) = array_map( 'trim', explode( '|', $line, 2 ) );
			if ( has_term( $slug, 'product_cat', $product->get_id() ) || has_term( $slug, 'product_tag', $product->get_id() ) ) {
				$label = sanitize_text_field( $text );
				break;
			}
		}
		if ( ! $label && $product->is_in_stock() && null !== $product->get_stock_quantity() && $product->get_stock_quantity() <= 3 ) {
			$label = __( 'Last items', 'product-badges' );
		}
		if ( ! $label ) { return; }
		$color = LeanCart_Framework::setting( 'color', '#b32d2e' );
		if ( ! preg_match( '/^#[0-9a-fA-F]{6}$/', $color ) ) { $color = '#b32d2e'; }
		echo '<span class="lc-badge" style="background:' . esc_attr( $color ) . ';color:#fff;padding:3px 8px;border-radius:3px;font-size:11px;font-weight:700">' . esc_html( $label ) . '</span>';
	}


}

LeanCart_Product_Badges::init();

