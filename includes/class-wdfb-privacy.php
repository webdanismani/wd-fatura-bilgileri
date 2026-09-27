<?php
defined( 'ABSPATH' ) || exit;

class WDFB_Privacy {

	const META = array(
		'billing_wdfb_tip'     => 'Fatura türü',
		'billing_wdfb_tc'      => 'TC kimlik no',
		'billing_wdfb_vd'      => 'Vergi dairesi',
		'billing_wdfb_vd_kod'  => 'Vergi dairesi kodu',
		'billing_wdfb_vkn'     => 'Vergi / TC kimlik no (kurumsal)',
		'billing_wdfb_efatura' => 'e-Fatura mükellefi',
	);

	public static function init() {
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_eraser' ) );
		add_filter( 'woocommerce_privacy_export_order_personal_data_props', array( __CLASS__, 'order_props' ), 10, 2 );
		add_filter( 'woocommerce_privacy_export_order_personal_data_prop', array( __CLASS__, 'order_prop_value' ), 10, 3 );
		add_action( 'woocommerce_privacy_remove_order_personal_data', array( __CLASS__, 'remove_order' ) );
		add_action( 'admin_init', array( __CLASS__, 'policy_text' ) );
	}

	public static function register_exporter( $e ) {
		$e['wd-fatura-bilgileri'] = array( 'exporter_friendly_name' => 'WD Fatura Bilgileri', 'callback' => array( __CLASS__, 'export' ) );
		return $e;
	}

	public static function register_eraser( $e ) {
		$e['wd-fatura-bilgileri'] = array( 'eraser_friendly_name' => 'WD Fatura Bilgileri', 'callback' => array( __CLASS__, 'erase' ) );
		return $e;
	}

	public static function export( $email, $page = 1 ) {
		$user = get_user_by( 'email', $email );
		$data = array();
		if ( $user ) {
			foreach ( self::META as $k => $label ) {
				$v = (string) get_user_meta( $user->ID, $k, true );
				if ( '' !== $v ) {
					$data[] = array( 'name' => $label, 'value' => $v );
				}
			}
		}
		return array(
			'data' => $data ? array( array( 'group_id' => 'wdfb', 'group_label' => 'Fatura bilgileri', 'item_id' => 'wdfb-user', 'data' => $data ) ) : array(),
			'done' => true,
		);
	}

	public static function erase( $email, $page = 1 ) {
		$user    = get_user_by( 'email', $email );
		$removed = false;
		if ( $user ) {
			foreach ( array_keys( self::META ) as $k ) {
				if ( '' !== (string) get_user_meta( $user->ID, $k, true ) ) {
					delete_user_meta( $user->ID, $k );
					$removed = true;
				}
			}
		}
		return array( 'items_removed' => $removed, 'items_retained' => false, 'messages' => array(), 'done' => true );
	}

	public static function order_props( $props, $order ) {
		$props['wdfb_tc']  = 'TC kimlik no';
		$props['wdfb_vkn'] = 'Vergi / TC kimlik no (kurumsal)';
		$props['wdfb_vd']  = 'Vergi dairesi';
		return $props;
	}

	public static function order_prop_value( $value, $prop, $order ) {
		if ( 0 === strpos( $prop, 'wdfb_' ) ) {
			return (string) $order->get_meta( '_billing_' . $prop );
		}
		return $value;
	}

	public static function remove_order( $order ) {
		foreach ( array_keys( self::META ) as $k ) {
			$order->delete_meta_data( '_' . $k );
		}
		$s = WDFB_Settings::all();
		foreach ( array( 'meta_tip', 'meta_tc', 'meta_vkn', 'meta_vd' ) as $o ) {
			if ( '' !== $s[ $o ] ) {
				$order->delete_meta_data( $s[ $o ] );
			}
		}
	}

	public static function policy_text() {
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			wp_add_privacy_policy_content(
				'WD Fatura Bilgileri',
				'<p>Fatura düzenlenebilmesi amacıyla ödeme sırasında bireysel müşterilerden TC kimlik numarası, kurumsal müşterilerden firma ünvanı, vergi dairesi ve vergi kimlik numarası alınır. Bu bilgiler sipariş kayıtlarında ve kullanıcı hesabında saklanır; yasal saklama süreleri boyunca muhafaza edilir.</p>'
			);
		}
	}
}
