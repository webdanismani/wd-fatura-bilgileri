<?php
defined( 'ABSPATH' ) || exit;

class WDFB_Settings {

	const OPTION = 'wdfb_settings';

	private static $cache = null;

	public static function defaults() {
		return array(
			'enabled'        => 1,
			'default_tip'    => 'bireysel',
			'tc_mode'        => 'optional',
			'tc_threshold'   => 5000,
			'allow_foreign'  => 1,
			'unvan_required' => 1,
			'vd_required'    => 1,
			'vkn_required'   => 1,
			'show_efatura'   => 1,
			'show_summary'   => 1,
			'address_line'   => 1,
			'mask_customer'  => 1,
			'email_block'    => 1,
			'list_column'    => 1,
			'theme'          => 'auto',
			'accent'         => '#1f6f5c',
			'radius'         => 12,
			'meta_tip'       => '',
			'meta_tc'        => '',
			'meta_vkn'       => '',
			'meta_vd'        => '',
		);
	}

	public static function all() {
		if ( null === self::$cache ) {
			$saved       = get_option( self::OPTION, array() );
			self::$cache = wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
		}
		return self::$cache;
	}

	public static function get( $key ) {
		$all = self::all();
		return $all[ $key ] ?? null;
	}

	public static function sanitize( $in ) {
		$d   = self::defaults();
		$in  = is_array( $in ) ? $in : array();
		$out = array();

		foreach ( array( 'enabled', 'allow_foreign', 'unvan_required', 'vd_required', 'vkn_required', 'show_efatura', 'show_summary', 'address_line', 'mask_customer', 'email_block', 'list_column' ) as $k ) {
			$out[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
		}

		$out['default_tip']  = in_array( $in['default_tip'] ?? '', array( 'bireysel', 'kurumsal' ), true ) ? $in['default_tip'] : $d['default_tip'];
		$out['tc_mode']      = in_array( $in['tc_mode'] ?? '', array( 'hidden', 'optional', 'required', 'threshold' ), true ) ? $in['tc_mode'] : $d['tc_mode'];
		$out['tc_threshold'] = max( 0, (float) str_replace( array( '.', ',' ), array( '', '.' ), (string) ( $in['tc_threshold'] ?? $d['tc_threshold'] ) ) );
		$out['theme']        = in_array( $in['theme'] ?? '', array( 'auto', 'light', 'dark' ), true ) ? $in['theme'] : $d['theme'];
		$accent              = sanitize_hex_color( $in['accent'] ?? '' );
		$out['accent']       = $accent ? $accent : $d['accent'];
		$out['radius']       = max( 0, min( 24, (int) ( $in['radius'] ?? $d['radius'] ) ) );

		foreach ( array( 'meta_tip', 'meta_tc', 'meta_vkn', 'meta_vd' ) as $k ) {
			$v         = trim( (string) ( $in[ $k ] ?? '' ) );
			$out[ $k ] = preg_match( '/^[A-Za-z0-9_\-]{1,64}$/', $v ) && 0 !== strpos( $v, '_billing_wdfb_' ) ? $v : '';
		}

		self::$cache = null;
		return $out;
	}
}
