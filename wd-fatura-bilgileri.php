<?php
/**
 * Plugin Name:       WD Fatura Bilgileri — Bireysel / Kurumsal
 * Plugin URI:        https://oblifex.com/?utm_source=wp-plugin&utm_medium=header&utm_campaign=wd-fatura-bilgileri
 * Description:       WooCommerce ödeme sayfasında bireysel (TC kimlik no) ve kurumsal (firma ünvanı, vergi dairesi, VKN) fatura bilgisi seçimi. Algoritmik TCKN/VKN doğrulama, 1.047 vergi dairesi listesi, sipariş ve e-posta entegrasyonu.
 * Version:           1.0.0
 * Author:            Oblifex
 * Author URI:        https://oblifex.com
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       wd-fatura-bilgileri
 * Update URI:        https://github.com/oblifex/wd-fatura-bilgileri
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * WC requires at least: 7.0
 * WC tested up to:   11.1
 */

defined( 'ABSPATH' ) || exit;

define( 'WDFB_VERSION', '1.0.0' );
define( 'WDFB_FILE', __FILE__ );
define( 'WDFB_DIR', plugin_dir_path( __FILE__ ) );
define( 'WDFB_URL', plugin_dir_url( __FILE__ ) );

require_once WDFB_DIR . 'includes/class-wdfb-validator.php';
require_once WDFB_DIR . 'includes/class-wdfb-settings.php';
require_once WDFB_DIR . 'includes/class-wdfb-data.php';
require_once WDFB_DIR . 'includes/class-wdfb-checkout.php';
require_once WDFB_DIR . 'includes/class-wdfb-order.php';
require_once WDFB_DIR . 'includes/class-wdfb-privacy.php';
require_once WDFB_DIR . 'includes/class-wdfb-admin.php';
require_once WDFB_DIR . 'includes/class-wdfb-oblifex.php';

add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WDFB_FILE, true );
	}
} );

add_action( 'plugins_loaded', function () {
	WDFB_Oblifex::init();

	if ( is_admin() ) {
		WDFB_Admin::init();
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			if ( current_user_can( 'activate_plugins' ) ) {
				echo '<div class="notice notice-error"><p><strong>WD Fatura Bilgileri</strong> çalışmak için WooCommerce eklentisine ihtiyaç duyar.</p></div>';
			}
		} );
		return;
	}

	WDFB_Order::init();
	WDFB_Privacy::init();

	if ( WDFB_Settings::get( 'enabled' ) ) {
		WDFB_Checkout::init();
	}
} );

register_activation_hook( __FILE__, function () {
	WDFB_Oblifex::etkinlestirildi();
	if ( false === get_option( WDFB_Settings::OPTION ) ) {
		add_option( WDFB_Settings::OPTION, WDFB_Settings::defaults() );
	}
} );

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=wd-fatura-bilgileri' ) ) . '">Ayarlar</a>' );
	return $links;
} );
