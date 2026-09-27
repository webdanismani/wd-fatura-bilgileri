<?php
defined( 'ABSPATH' ) || exit;

class WDFB_Data {

	private static $data = null;

	public static function all() {
		if ( null === self::$data ) {
			$json       = file_get_contents( WDFB_DIR . 'data/vergi-daireleri.json' ); // phpcs:ignore
			self::$data = json_decode( $json, true );
		}
		return self::$data;
	}

	public static function count() {
		$n = 0;
		foreach ( self::all()['vd'] as $rows ) {
			$n += count( $rows );
		}
		return $n;
	}

	/** @return array{kod:string, ad:string, il:int}|null */
	public static function find( $kod ) {
		$kod = preg_replace( '/\D+/', '', (string) $kod );
		if ( '' === $kod ) {
			return null;
		}
		foreach ( self::all()['vd'] as $il => $rows ) {
			foreach ( $rows as $r ) {
				if ( $r[0] === $kod ) {
					return array( 'kod' => $r[0], 'ad' => $r[1], 'il' => (int) $il );
				}
			}
		}
		return null;
	}
}
