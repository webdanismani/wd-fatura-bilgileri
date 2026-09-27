<?php
defined( 'ABSPATH' ) || exit;

class WDFB_Order {

	public static function init() {
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'compat_meta' ), 30 );
		add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'admin_saved' ), 100 );

		add_filter( 'woocommerce_admin_billing_fields', array( __CLASS__, 'admin_fields' ) );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'admin_box' ) );

		add_filter( 'woocommerce_localisation_address_formats', array( __CLASS__, 'address_formats' ), 50 );
		add_filter( 'woocommerce_formatted_address_replacements', array( __CLASS__, 'address_replacements' ), 10, 2 );
		add_filter( 'woocommerce_order_formatted_billing_address', array( __CLASS__, 'order_address' ), 10, 2 );
		add_filter( 'woocommerce_my_account_my_address_formatted_address', array( __CLASS__, 'account_address' ), 10, 3 );

		add_action( 'woocommerce_email_customer_details', array( __CLASS__, 'email_block' ), 30, 4 );
		add_action( 'woocommerce_order_details_after_customer_details', array( __CLASS__, 'thankyou_block' ) );

		add_filter( 'manage_edit-shop_order_columns', array( __CLASS__, 'column' ), 30 );
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( __CLASS__, 'column' ), 30 );
		add_action( 'manage_shop_order_posts_custom_column', array( __CLASS__, 'column_legacy' ), 10, 2 );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( __CLASS__, 'column_hpos' ), 10, 2 );
		add_action( 'admin_head', array( __CLASS__, 'admin_css' ) );
	}

	/* ------------------------------------------------------------------ */

	public static function info( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return null;
		}
		$tip = (string) $order->get_meta( '_billing_wdfb_tip' );
		if ( '' === $tip ) {
			return null;
		}
		return array(
			'tip'     => $tip,
			'tc'      => (string) $order->get_meta( '_billing_wdfb_tc' ),
			'unvan'   => (string) $order->get_billing_company(),
			'vd'      => (string) $order->get_meta( '_billing_wdfb_vd' ),
			'vd_kod'  => (string) $order->get_meta( '_billing_wdfb_vd_kod' ),
			'vkn'     => (string) $order->get_meta( '_billing_wdfb_vkn' ),
			'efatura' => '1' === (string) $order->get_meta( '_billing_wdfb_efatura' ),
		);
	}

	public static function compat_meta( $order ) {
		$s = WDFB_Settings::all();
		$i = self::info( $order );
		if ( ! $i ) {
			return;
		}
		$map = array(
			'meta_tip' => $i['tip'],
			'meta_tc'  => $i['tc'],
			'meta_vkn' => $i['vkn'],
			'meta_vd'  => $i['vd'],
		);
		foreach ( $map as $opt => $val ) {
			if ( '' !== $s[ $opt ] ) {
				$order->update_meta_data( $s[ $opt ], $val );
			}
		}
	}

	public static function admin_saved( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$tip = (string) $order->get_meta( '_billing_wdfb_tip' );
		if ( '' === $tip && '' !== (string) $order->get_meta( '_billing_wdfb_vkn' ) ) {
			$order->update_meta_data( '_billing_wdfb_tip', 'kurumsal' );
		}
		foreach ( array( '_billing_wdfb_tc', '_billing_wdfb_vkn' ) as $k ) {
			$order->update_meta_data( $k, WDFB_Validator::digits( $order->get_meta( $k ) ) );
		}
		self::compat_meta( $order );
		$order->save();
	}

	/* ----- yönetici sipariş ekranı ----- */

	public static function admin_fields( $fields ) {
		$new = array();
		foreach ( $fields as $k => $f ) {
			$new[ $k ] = $f;
			if ( 'company' === $k ) {
				$new['wdfb_tip']     = array(
					'label'   => 'Fatura türü',
					'show'    => false,
					'type'    => 'select',
					'options' => array( '' => '—', 'bireysel' => 'Bireysel', 'kurumsal' => 'Kurumsal' ),
				);
				$new['wdfb_tc']      = array( 'label' => 'TC kimlik no', 'show' => false );
				$new['wdfb_vd']      = array( 'label' => 'Vergi dairesi', 'show' => false );
				$new['wdfb_vkn']     = array( 'label' => 'VKN / TCKN', 'show' => false );
				$new['wdfb_efatura'] = array(
					'label'   => 'e-Fatura mükellefi',
					'show'    => false,
					'type'    => 'select',
					'options' => array( '' => 'Hayır', '1' => 'Evet' ),
				);
			}
		}
		return $new;
	}

	private static function id_state( $tip, $value ) {
		if ( '' === $value ) {
			return '';
		}
		if ( WDFB_Validator::FOREIGN_TC === $value ) {
			return '<span class="wdfb-a-tag">yabancı uyruklu</span>';
		}
		$ok = 'bireysel' === $tip ? WDFB_Validator::tckn( $value ) : (bool) WDFB_Validator::tax_id( $value );
		return $ok ? '<span class="wdfb-a-tag is-ok">doğrulandı</span>' : '<span class="wdfb-a-tag is-bad">geçersiz</span>';
	}

	public static function admin_box( $order ) {
		$i = self::info( $order );
		if ( ! $i ) {
			return;
		}
		$rows = array();
		if ( 'bireysel' === $i['tip'] ) {
			if ( '' !== $i['tc'] ) {
				$rows['TC kimlik no'] = esc_html( $i['tc'] ) . ' ' . self::id_state( 'bireysel', $i['tc'] );
			}
		} else {
			$type                  = 11 === strlen( $i['vkn'] ) ? 'TCKN' : 'VKN';
			$rows['Ünvan']         = esc_html( $i['unvan'] );
			$rows['Vergi dairesi'] = esc_html( $i['vd'] ) . ( $i['vd_kod'] ? ' <span class="wdfb-a-muted">' . esc_html( $i['vd_kod'] ) . '</span>' : '' );
			$rows[ $type ]         = esc_html( $i['vkn'] ) . ' ' . self::id_state( 'kurumsal', $i['vkn'] );
			$rows['e-Fatura']      = $i['efatura'] ? 'Mükellef' : 'Değil (e-Arşiv)';
		}

		echo '<div class="wdfb-a-box"><p class="wdfb-a-head"><span>Fatura bilgileri</span><b class="wdfb-a-pill is-' . esc_attr( $i['tip'] ) . '">' . ( 'kurumsal' === $i['tip'] ? 'Kurumsal' : 'Bireysel' ) . '</b></p>';
		if ( $rows ) {
			echo '<table>';
			foreach ( $rows as $k => $v ) {
				echo '<tr><th>' . esc_html( $k ) . '</th><td>' . wp_kses_post( $v ) . '</td></tr>';
			}
			echo '</table>';
		}
		echo '</div>';
	}

	public static function admin_css() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! in_array( $screen->id, array( 'shop_order', 'edit-shop_order', 'woocommerce_page_wc-orders' ), true ) ) {
			return;
		}
		?>
		<style>
			.wdfb-a-box{margin-top:12px;padding:10px 12px;border:1px solid #dcdcde;border-left:3px solid #1f6f5c;border-radius:6px;background:#fbfbfa}
			.wdfb-a-head{display:flex;justify-content:space-between;align-items:center;margin:0 0 6px!important;font-weight:600}
			.wdfb-a-box table{width:100%;border-collapse:collapse;font-size:12px}
			.wdfb-a-box th{padding:3px 8px 3px 0;color:#646970;font-weight:400;text-align:left;white-space:nowrap;vertical-align:top}
			.wdfb-a-box td{padding:3px 0}
			.wdfb-a-pill{display:inline-block;padding:1px 8px;border-radius:99px;font-size:11px;font-weight:600;background:#eef3f1;color:#1f6f5c}
			.wdfb-a-pill.is-kurumsal{background:#f3eee4;color:#8a6420}
			.wdfb-a-tag{display:inline-block;margin-left:4px;padding:0 6px;border-radius:4px;font-size:10.5px;background:#f0f0f1;color:#50575e}
			.wdfb-a-tag.is-ok{background:#e7f5ee;color:#1a7f52}
			.wdfb-a-tag.is-bad{background:#fcecea;color:#b32d2e}
			.wdfb-a-muted{color:#8c8f94;font-size:11px}
			.column-wdfb{width:9ch}
		</style>
		<?php
	}

	/* ----- sipariş listesi ----- */

	public static function column( $cols ) {
		if ( ! WDFB_Settings::get( 'list_column' ) ) {
			return $cols;
		}
		$new = array();
		foreach ( $cols as $k => $v ) {
			$new[ $k ] = $v;
			if ( 'order_status' === $k ) {
				$new['wdfb'] = 'Fatura';
			}
		}
		if ( ! isset( $new['wdfb'] ) ) {
			$new['wdfb'] = 'Fatura';
		}
		return $new;
	}

	private static function column_html( $order ) {
		$i = self::info( $order );
		if ( ! $i ) {
			echo '<span class="wdfb-a-muted">—</span>';
			return;
		}
		printf(
			'<b class="wdfb-a-pill is-%1$s">%2$s</b>%3$s',
			esc_attr( $i['tip'] ),
			'kurumsal' === $i['tip'] ? 'Kurumsal' : 'Bireysel',
			$i['efatura'] ? ' <span class="wdfb-a-tag">e-Fatura</span>' : ''
		);
	}

	public static function column_legacy( $column, $post_id ) {
		if ( 'wdfb' === $column ) {
			self::column_html( wc_get_order( $post_id ) );
		}
	}

	public static function column_hpos( $column, $order ) {
		if ( 'wdfb' === $column ) {
			self::column_html( $order );
		}
	}

	/* ----- biçimlenmiş adres ----- */

	public static function address_formats( $formats ) {
		if ( ! WDFB_Settings::get( 'address_line' ) ) {
			return $formats;
		}
		foreach ( array( 'default', 'TR' ) as $c ) {
			if ( isset( $formats[ $c ] ) && false === strpos( $formats[ $c ], '{wdfb}' ) ) {
				$formats[ $c ] .= "\n{wdfb}";
			}
		}
		return $formats;
	}

	public static function address_replacements( $rep, $args ) {
		$rep['{wdfb}'] = isset( $args['wdfb'] ) ? $args['wdfb'] : '';
		return $rep;
	}

	private static function line( $tip, $vkn, $vd ) {
		if ( 'kurumsal' !== $tip || '' === $vkn ) {
			return '';
		}
		$label = 11 === strlen( $vkn ) ? 'TCKN' : 'VKN';
		return trim( $label . ': ' . $vkn . ( $vd ? ' · ' . $vd : '' ) );
	}

	public static function order_address( $address, $order ) {
		$i = self::info( $order );
		if ( $i && WDFB_Settings::get( 'address_line' ) ) {
			$address['wdfb'] = self::line( $i['tip'], $i['vkn'], $i['vd'] );
		}
		return $address;
	}

	public static function account_address( $address, $customer_id, $type ) {
		if ( 'billing' === $type && WDFB_Settings::get( 'address_line' ) ) {
			$address['wdfb'] = self::line(
				(string) get_user_meta( $customer_id, 'billing_wdfb_tip', true ),
				(string) get_user_meta( $customer_id, 'billing_wdfb_vkn', true ),
				(string) get_user_meta( $customer_id, 'billing_wdfb_vd', true )
			);
		}
		return $address;
	}

	/* ----- e-posta ve teşekkür sayfası ----- */

	private static function rows_for_customer( $i, $mask ) {
		$rows = array( 'Fatura türü' => 'kurumsal' === $i['tip'] ? 'Kurumsal' : 'Bireysel' );
		if ( 'bireysel' === $i['tip'] ) {
			if ( '' !== $i['tc'] ) {
				$rows['TC kimlik no'] = WDFB_Validator::FOREIGN_TC === $i['tc'] ? 'Yabancı uyruklu' : ( $mask ? WDFB_Validator::mask( $i['tc'] ) : $i['tc'] );
			}
		} else {
			$rows['Ünvan']         = $i['unvan'];
			$rows['Vergi dairesi'] = $i['vd'];
			$rows[ 11 === strlen( $i['vkn'] ) ? 'TC kimlik no' : 'Vergi no' ] = ( $mask && 11 === strlen( $i['vkn'] ) ) ? WDFB_Validator::mask( $i['vkn'] ) : $i['vkn'];
			if ( $i['efatura'] ) {
				$rows['e-Fatura'] = 'Mükellef';
			}
		}
		return array_filter( $rows, 'strlen' );
	}

	public static function email_block( $order, $sent_to_admin, $plain_text, $email ) {
		if ( ! WDFB_Settings::get( 'email_block' ) ) {
			return;
		}
		$i = self::info( $order );
		if ( ! $i ) {
			return;
		}
		$rows = self::rows_for_customer( $i, ! $sent_to_admin && WDFB_Settings::get( 'mask_customer' ) );

		if ( $plain_text ) {
			echo "\n" . esc_html( mb_strtoupper( 'Fatura bilgileri', 'UTF-8' ) ) . "\n";
			foreach ( $rows as $k => $v ) {
				echo esc_html( $k . ': ' . $v ) . "\n";
			}
			return;
		}
		echo '<div style="margin:0 0 24px"><h2>Fatura bilgileri</h2><table cellspacing="0" cellpadding="6" style="width:100%;border:1px solid #e5e5e5;border-collapse:collapse" border="1">';
		foreach ( $rows as $k => $v ) {
			echo '<tr><th style="text-align:left;width:40%;border:1px solid #e5e5e5">' . esc_html( $k ) . '</th><td style="text-align:left;border:1px solid #e5e5e5">' . esc_html( $v ) . '</td></tr>';
		}
		echo '</table></div>';
	}

	public static function thankyou_block( $order ) {
		$i = self::info( $order );
		if ( ! $i ) {
			return;
		}
		$rows = self::rows_for_customer( $i, (bool) WDFB_Settings::get( 'mask_customer' ) );
		echo '<section class="woocommerce-customer-details wdfb-details"><h2 class="woocommerce-column__title">Fatura bilgileri</h2><table class="woocommerce-table shop_table"><tbody>';
		foreach ( $rows as $k => $v ) {
			echo '<tr><th>' . esc_html( $k ) . '</th><td>' . esc_html( $v ) . '</td></tr>';
		}
		echo '</tbody></table></section>';
	}
}
