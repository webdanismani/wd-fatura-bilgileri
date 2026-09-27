<?php
defined( 'ABSPATH' ) || exit;

class WDFB_Validator {

	const FOREIGN_TC = '11111111111';

	public static function digits( $v ) {
		return preg_replace( '/\D+/', '', (string) $v );
	}

	public static function tckn( $v ) {
		$v = self::digits( $v );
		if ( ! preg_match( '/^[1-9]\d{10}$/', $v ) ) {
			return false;
		}
		$d    = array_map( 'intval', str_split( $v ) );
		$odd  = $d[0] + $d[2] + $d[4] + $d[6] + $d[8];
		$even = $d[1] + $d[3] + $d[5] + $d[7];
		$d10  = ( ( $odd * 7 - $even ) % 10 + 10 ) % 10;
		if ( $d10 !== $d[9] ) {
			return false;
		}
		return ( array_sum( array_slice( $d, 0, 10 ) ) % 10 ) === $d[10];
	}

	public static function vkn( $v ) {
		$v = self::digits( $v );
		if ( ! preg_match( '/^\d{10}$/', $v ) ) {
			return false;
		}
		$d   = array_map( 'intval', str_split( $v ) );
		$sum = 0;
		for ( $i = 0; $i < 9; $i++ ) {
			$tmp = ( $d[ $i ] + ( 9 - $i ) ) % 10;
			$val = ( $tmp * ( 2 ** ( 9 - $i ) ) ) % 9;
			if ( 0 !== $tmp && 0 === $val ) {
				$val = 9;
			}
			$sum += $val;
		}
		return ( ( 10 - ( $sum % 10 ) ) % 10 ) === $d[9];
	}

	/** Kurumsal kimlik: 10 haneli VKN veya şahıs şirketleri için 11 haneli TCKN. */
	public static function tax_id( $v ) {
		$v = self::digits( $v );
		if ( 10 === strlen( $v ) ) {
			return self::vkn( $v ) ? 'vkn' : false;
		}
		if ( 11 === strlen( $v ) ) {
			return self::tckn( $v ) ? 'tckn' : false;
		}
		return false;
	}

	public static function mask( $v ) {
		$v = self::digits( $v );
		if ( strlen( $v ) < 6 ) {
			return $v;
		}
		return substr( $v, 0, 3 ) . str_repeat( '•', strlen( $v ) - 5 ) . substr( $v, -2 );
	}
}
