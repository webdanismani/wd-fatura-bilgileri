<?php
/**
 * Plugin Name:       WD Fatura Bilgileri — Bireysel / Kurumsal
 * Plugin URI:        https://oblifex.com/?utm_source=wp-plugin&utm_medium=header&utm_campaign=wd-fatura-bilgileri
 * Description:       WooCommerce ödeme sayfasında bireysel (TC kimlik no) ve kurumsal (firma ünvanı, vergi dairesi, VKN) fatura bilgisi seçimi. Algoritmik TCKN/VKN doğrulama, 1.047 vergi dairesi listesi, sipariş ve e-posta entegrasyonu.
 * Version:           1.0.1
 * Author:            Web Danışmanı
 * Author URI:        https://webdanismani.com
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       wd-fatura-bilgileri
 * Update URI:        https://github.com/webdanismani/wd-fatura-bilgileri
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * WC requires at least: 7.0
 * WC tested up to:   11.1
 */

defined( 'ABSPATH' ) || exit;

define( 'WDFB_VERSION', '1.0.1' );
define( 'WDFB_FILE', __FILE__ );
define( 'WDFB_DIR', plugin_dir_path( __FILE__ ) );
define( 'WDFB_URL', plugin_dir_url( __FILE__ ) );

// Eksik yükleme koruması: dosyalar eksikse (ör. GitHub web yüklemesinde klasörler atlanmışsa)
// site çökmez; eklenti çalışmaz ve yöneticiye yeniden kurulum uyarısı gösterilir.
$wdfb_required = array(
	'includes/class-wdfb-validator.php',
	'includes/class-wdfb-settings.php',
	'includes/class-wdfb-data.php',
	'includes/class-wdfb-checkout.php',
	'includes/class-wdfb-order.php',
	'includes/class-wdfb-privacy.php',
	'includes/class-wdfb-admin.php',
	'includes/class-wdfb-oblifex.php',
	'assets/css/admin.css',
	'assets/css/wdfb.css',
	'assets/js/admin.js',
	'assets/js/wdfb.js',
	'data/vergi-daireleri.json',
);
$wdfb_missing  = array();
foreach ( $wdfb_required as $wdfb_file ) {
	$wdfb_path = WDFB_DIR . $wdfb_file;
	$wdfb_ok   = '/' === substr( $wdfb_file, -1 ) ? ( is_dir( $wdfb_path ) && (bool) glob( $wdfb_path . '*' ) ) : is_readable( $wdfb_path );
	if ( ! $wdfb_ok ) {
		$wdfb_missing[] = $wdfb_file;
	}
}
if ( $wdfb_missing ) {
	add_action(
		'admin_notices',
		function () use ( $wdfb_missing ) {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			$list = implode( ', ', array_slice( $wdfb_missing, 0, 5 ) ) . ( count( $wdfb_missing ) > 5 ? ' …' : '' );
			printf(
				'<div class="notice notice-error"><p><strong>WD Fatura Bilgileri eksik yüklenmiş, bu yüzden çalıştırılmadı.</strong> Bulunamayan dosyalar: <code>%s</code></p><p>Eklentiyi silip <a href="%s" target="_blank" rel="noopener">GitHub sayfasından</a> (Code → Download ZIP) ya da <a href="https://oblifex.com" target="_blank" rel="noopener">oblifex.com</a> üzerindeki paketle yeniden kurun.</p></div>',
				esc_html( $list ),
				esc_url( 'https://github.com/webdanismani/wd-fatura-bilgileri' )
			);
		}
	);
	return;
}
foreach ( $wdfb_required as $wdfb_file ) {
	if ( '.php' === substr( $wdfb_file, -4 ) ) {
		require_once WDFB_DIR . $wdfb_file;
	}
}
unset( $wdfb_required, $wdfb_missing, $wdfb_file, $wdfb_path, $wdfb_ok );

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
