<?php
defined( 'ABSPATH' ) || exit;

class WDFB_Checkout {

	const SUBKEYS = array( 'tip', 'tc', 'yabanci', 'vd', 'vd_kod', 'vkn', 'efatura' );

	private static $errors = array();

	public static function init() {
		add_filter( 'woocommerce_billing_fields', array( __CLASS__, 'fields' ), 30 );
		add_filter( 'woocommerce_form_field_wdfb_panel', array( __CLASS__, 'render' ), 10, 4 );
		add_filter( 'woocommerce_form_field_wdfb_part', '__return_empty_string' );

		add_filter( 'woocommerce_checkout_posted_data', array( __CLASS__, 'posted_data' ), 25 );
		add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'validate_checkout' ), 25, 2 );
		add_action( 'woocommerce_after_save_address_validation', array( __CLASS__, 'validate_account' ), 10, 4 );
		add_filter( 'woocommerce_update_order_review_fragments', array( __CLASS__, 'fragments' ) );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	/* ------------------------------------------------------------------ */

	public static function fields( $fields ) {
		$base = isset( $fields['billing_last_name']['priority'] ) ? (int) $fields['billing_last_name']['priority'] : 20;

		foreach ( self::SUBKEYS as $i => $sub ) {
			$fields[ 'billing_wdfb_' . $sub ] = array(
				'type'     => 'tip' === $sub ? 'wdfb_panel' : 'wdfb_part',
				'label'    => '',
				'required' => false,
				'priority' => $base + 2 + $i / 100,
			);
		}

		$fields['billing_company'] = array(
			'type'     => 'wdfb_part',
			'label'    => 'Firma ünvanı',
			'required' => false,
			'priority' => $base + 2.5,
		);

		return $fields;
	}

	private static function value( $key ) {
		if ( isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return wc_clean( wp_unslash( $_POST[ $key ] ) ); // phpcs:ignore
		}
		if ( function_exists( 'is_checkout' ) && is_checkout() && WC()->checkout() ) {
			$v = WC()->checkout()->get_value( $key );
			return is_scalar( $v ) ? (string) $v : '';
		}
		if ( is_user_logged_in() ) {
			return (string) get_user_meta( get_current_user_id(), $key, true );
		}
		return '';
	}

	public static function cart_total() {
		return ( function_exists( 'WC' ) && WC()->cart ) ? (float) WC()->cart->get_total( 'edit' ) : 0.0;
	}

	public static function tc_required( $total = null ) {
		$mode = WDFB_Settings::get( 'tc_mode' );
		if ( 'required' === $mode ) {
			return true;
		}
		if ( 'threshold' === $mode ) {
			$total = null === $total ? self::cart_total() : $total;
			return $total >= (float) WDFB_Settings::get( 'tc_threshold' );
		}
		return false;
	}

	/* ------------------------------------------------------------------ */

	public static function render( $html, $key, $args, $value ) {
		$s = WDFB_Settings::all();
		$v = array();
		foreach ( self::SUBKEYS as $sub ) {
			$v[ $sub ] = self::value( 'billing_wdfb_' . $sub );
		}
		$v['company'] = self::value( 'billing_company' );

		$tip = in_array( $v['tip'], array( 'bireysel', 'kurumsal' ), true ) ? $v['tip'] : ( '' !== $v['vkn'] || '' !== $v['company'] ? 'kurumsal' : $s['default_tip'] );

		$is_checkout = function_exists( 'is_checkout' ) && is_checkout();
		$tc_req      = $is_checkout ? self::tc_required() : 'required' === $s['tc_mode'];
		$req         = '<abbr class="wdfb-req" title="zorunlu">*</abbr>';
		$opt         = '<span class="wdfb-opt">isteğe bağlı</span>';
		$ok_icon     = '<svg class="wdfb-st__ok" viewBox="0 0 20 20" aria-hidden="true"><path d="m5 10.5 3.2 3L15 6.8" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>';
		$bad_icon    = '<svg class="wdfb-st__bad" viewBox="0 0 20 20" aria-hidden="true"><path d="M10 5.5v5.5M10 14v.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
		$foreign     = WDFB_Validator::FOREIGN_TC === $v['tc'] || '1' === $v['yabanci'];

		ob_start();
		?>
		<div class="form-row form-row-wide wdfb-row" id="<?php echo esc_attr( $key ); ?>_field" data-priority="<?php echo esc_attr( $args['priority'] ?? 22 ); ?>">
			<div class="wdfb" lang="tr" data-wdfb data-tip="<?php echo esc_attr( $tip ); ?>" data-theme-pref="<?php echo esc_attr( $s['theme'] ); ?>">
				<span class="wdfb-total-holder" data-total="<?php echo esc_attr( $is_checkout ? self::cart_total() : 0 ); ?>" hidden></span>

				<div class="wdfb__head">
					<svg class="wdfb__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3.5h7.5L19 8v12.5H7z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M14.5 3.5V8H19M10 12.5h6M10 16h4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M4.5 6.5v14a1 1 0 0 0 1 1H16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" opacity=".45"/></svg>
					<div>
						<strong>Fatura bilgileri</strong>
						<span>Faturanız bu bilgilerle düzenlenir</span>
					</div>
				</div>

				<div class="wdfb-seg" role="radiogroup" aria-label="Fatura türü">
					<span class="wdfb-seg__thumb" aria-hidden="true"></span>
					<label class="wdfb-seg__opt">
						<input type="radio" name="billing_wdfb_tip" value="bireysel" <?php checked( $tip, 'bireysel' ); ?>>
						<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8.5" r="3.6" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M5 20c.8-3.6 3.6-5.6 7-5.6s6.2 2 7 5.6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
						<span><b>Bireysel</b><small>Kişi adına fatura</small></span>
					</label>
					<label class="wdfb-seg__opt">
						<input type="radio" name="billing_wdfb_tip" value="kurumsal" <?php checked( $tip, 'kurumsal' ); ?>>
						<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4.5 20.5V6.5l7-3v17M11.5 9.5h8v11" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M7.5 9h1M7.5 12.5h1M7.5 16h1M14.5 13h2M14.5 16.5h2M3 20.5h18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
						<span><b>Kurumsal</b><small>Şirket veya şahıs şirketi</small></span>
					</label>
				</div>

				<div class="wdfb__pane" data-pane="bireysel" <?php echo 'hidden' === $s['tc_mode'] ? 'data-empty="1"' : ''; ?>>
					<div class="wdfb__pane-in">
						<?php if ( 'hidden' !== $s['tc_mode'] ) : ?>
						<div class="wdfb-f" data-field="tc">
							<label class="wdfb-f__label" for="billing_wdfb_tc">TC Kimlik No <span data-role="tc-mark"><?php echo $tc_req ? $req : $opt; // phpcs:ignore ?></span></label>
							<div class="wdfb-in wdfb-in--id">
								<input type="text" id="billing_wdfb_tc" name="billing_wdfb_tc" value="<?php echo esc_attr( $foreign ? '' : $v['tc'] ); ?>" inputmode="numeric" maxlength="11" autocomplete="off" placeholder="11 haneli numara" data-role="tc" <?php disabled( $foreign ); ?>>
								<span class="wdfb-st" aria-hidden="true"><span class="wdfb-st__count" data-role="tc-count">0/11</span><?php echo $ok_icon . $bad_icon; // phpcs:ignore ?></span>
							</div>
							<p class="wdfb-f__hint" data-role="tc-hint" aria-live="polite">
								<?php echo 'threshold' === $s['tc_mode'] ? esc_html( sprintf( '%s ve üzeri siparişlerde zorunludur.', wp_strip_all_tags( wc_price( $s['tc_threshold'], array( 'decimals' => 0 ) ) ) ) ) : ''; ?>
							</p>
						</div>
						<?php if ( $s['allow_foreign'] ) : ?>
						<label class="wdfb-check">
							<input type="checkbox" name="billing_wdfb_yabanci" value="1" data-role="foreign" <?php checked( $foreign ); ?>>
							<i aria-hidden="true"></i>
							<span>TC kimlik numaram yok <small>(yabancı uyruklu)</small></span>
						</label>
						<?php endif; ?>
						<?php endif; ?>
					</div>
				</div>

				<div class="wdfb__pane" data-pane="kurumsal">
					<div class="wdfb__pane-in">
						<div class="wdfb__grid">
							<div class="wdfb-f wdfb-f--wide" data-field="unvan">
								<label class="wdfb-f__label" for="billing_company">Firma ünvanı <?php echo $s['unvan_required'] ? $req : $opt; // phpcs:ignore ?></label>
								<div class="wdfb-in">
									<input type="text" id="billing_company" name="billing_company" value="<?php echo esc_attr( $v['company'] ); ?>" maxlength="200" autocomplete="organization" placeholder="Örn. Web Danışmanı Yazılım Ltd. Şti." data-role="unvan">
								</div>
								<p class="wdfb-f__hint"></p>
							</div>

							<div class="wdfb-f" data-field="vd">
								<label class="wdfb-f__label" for="billing_wdfb_vd_q">Vergi dairesi <?php echo $s['vd_required'] ? $req : $opt; // phpcs:ignore ?></label>
								<div class="wdfb-cb" data-role="vd-box">
									<input type="text" id="billing_wdfb_vd_q" class="wdfb-cb__input" value="<?php echo esc_attr( $v['vd'] ); ?>" placeholder="Vergi dairesi arayın" autocomplete="off" spellcheck="false" role="combobox" aria-expanded="false" aria-autocomplete="list">
									<svg class="wdfb-cb__chev" viewBox="0 0 20 20" aria-hidden="true"><path d="M5.5 7.5 10 12l4.5-4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
									<input type="hidden" name="billing_wdfb_vd" value="<?php echo esc_attr( $v['vd'] ); ?>" data-role="vd">
									<input type="hidden" name="billing_wdfb_vd_kod" value="<?php echo esc_attr( $v['vd_kod'] ); ?>" data-role="vd_kod">
								</div>
								<p class="wdfb-f__hint"></p>
							</div>

							<div class="wdfb-f" data-field="vkn">
								<label class="wdfb-f__label" for="billing_wdfb_vkn">Vergi / TC kimlik no <?php echo $s['vkn_required'] ? $req : $opt; // phpcs:ignore ?></label>
								<div class="wdfb-in wdfb-in--id">
									<span class="wdfb-in__badge" data-role="vkn-badge">VKN</span>
									<input type="text" id="billing_wdfb_vkn" name="billing_wdfb_vkn" value="<?php echo esc_attr( $v['vkn'] ); ?>" inputmode="numeric" maxlength="11" autocomplete="off" placeholder="10 veya 11 hane" data-role="vkn">
									<span class="wdfb-st" aria-hidden="true"><span class="wdfb-st__count" data-role="vkn-count">0/10</span><?php echo $ok_icon . $bad_icon; // phpcs:ignore ?></span>
								</div>
								<p class="wdfb-f__hint">Şahıs şirketlerinde TC kimlik numarası yazılır.</p>
							</div>

							<?php if ( $s['show_efatura'] ) : ?>
							<label class="wdfb-check wdfb-f--wide">
								<input type="checkbox" name="billing_wdfb_efatura" value="1" <?php checked( '1', $v['efatura'] ); ?> data-role="efatura">
								<i aria-hidden="true"></i>
								<span>e-Fatura mükellefiyim <small>(faturam GİB e-Fatura sistemi üzerinden kesilsin)</small></span>
							</label>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<?php if ( $s['show_summary'] ) : ?>
				<div class="wdfb-sum" data-role="summary" data-state="empty">
					<span class="wdfb-sum__cap">Fatura</span>
					<span class="wdfb-sum__txt" data-role="summary-text">Bilgiler tamamlandığında burada görünür</span>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ------------------------------------------------------------------ */

	/** Gönderilen verileri tipine göre temizler. */
	public static function normalize( array $data ) {
		$s   = WDFB_Settings::all();
		$tip = in_array( $data['billing_wdfb_tip'] ?? '', array( 'bireysel', 'kurumsal' ), true ) ? $data['billing_wdfb_tip'] : $s['default_tip'];

		$data['billing_wdfb_tip'] = $tip;

		if ( 'bireysel' === $tip ) {
			$foreign                      = $s['allow_foreign'] && ! empty( $data['billing_wdfb_yabanci'] );
			$data['billing_wdfb_tc']      = 'hidden' === $s['tc_mode'] ? '' : ( $foreign ? WDFB_Validator::FOREIGN_TC : WDFB_Validator::digits( $data['billing_wdfb_tc'] ?? '' ) );
			$data['billing_wdfb_yabanci'] = $foreign ? '1' : '';
			$data['billing_company']      = '';
			$data['billing_wdfb_vkn']     = '';
			$data['billing_wdfb_vd']      = '';
			$data['billing_wdfb_vd_kod']  = '';
			$data['billing_wdfb_efatura'] = '';
		} else {
			$data['billing_wdfb_tc']      = '';
			$data['billing_wdfb_yabanci'] = '';
			$data['billing_company']      = trim( preg_replace( '/\s+/u', ' ', (string) ( $data['billing_company'] ?? '' ) ) );
			$data['billing_wdfb_vkn']     = WDFB_Validator::digits( $data['billing_wdfb_vkn'] ?? '' );
			$vd                           = WDFB_Data::find( $data['billing_wdfb_vd_kod'] ?? '' );
			$data['billing_wdfb_vd_kod']  = $vd ? $vd['kod'] : '';
			$data['billing_wdfb_vd']      = $vd ? $vd['ad'] : mb_substr( trim( (string) ( $data['billing_wdfb_vd'] ?? '' ) ), 0, 120 );
			$data['billing_wdfb_efatura'] = ! empty( $data['billing_wdfb_efatura'] ) && $s['show_efatura'] ? '1' : '';
		}
		return $data;
	}

	/** @return array<int, array{0:string,1:string}> [alan id, mesaj] */
	public static function errors( array $data, $total ) {
		$s   = WDFB_Settings::all();
		$err = array();

		if ( 'bireysel' === $data['billing_wdfb_tip'] ) {
			if ( 'hidden' === $s['tc_mode'] ) {
				return $err;
			}
			$tc = $data['billing_wdfb_tc'];
			if ( '' === $tc ) {
				if ( self::tc_required( $total ) ) {
					$err[] = array( 'billing_wdfb_tc', 'Fatura için <strong>TC kimlik numarası</strong> gerekli.' );
				}
			} elseif ( WDFB_Validator::FOREIGN_TC !== $tc && ! WDFB_Validator::tckn( $tc ) ) {
				$err[] = array( 'billing_wdfb_tc', 'Girilen <strong>TC kimlik numarası</strong> geçerli değil.' );
			}
			return $err;
		}

		if ( $s['unvan_required'] && mb_strlen( $data['billing_company'] ) < 2 ) {
			$err[] = array( 'billing_company', 'Kurumsal fatura için <strong>firma ünvanı</strong> gerekli.' );
		}
		if ( $s['vd_required'] && '' === $data['billing_wdfb_vd'] ) {
			$err[] = array( 'billing_wdfb_vd_q', 'Kurumsal fatura için <strong>vergi dairesi</strong> seçin.' );
		}
		$vkn = $data['billing_wdfb_vkn'];
		if ( '' === $vkn ) {
			if ( $s['vkn_required'] ) {
				$err[] = array( 'billing_wdfb_vkn', 'Kurumsal fatura için <strong>vergi kimlik numarası</strong> gerekli.' );
			}
		} elseif ( ! WDFB_Validator::tax_id( $vkn ) ) {
			$err[] = array( 'billing_wdfb_vkn', 'Girilen <strong>vergi / TC kimlik numarası</strong> geçerli değil.' );
		}
		return $err;
	}

	private static function applies( $country ) {
		return '' === (string) $country || 'TR' === $country;
	}

	public static function posted_data( $data ) {
		self::$errors = array();
		if ( ! self::applies( $data['billing_country'] ?? '' ) ) {
			foreach ( self::SUBKEYS as $sub ) {
				unset( $data[ 'billing_wdfb_' . $sub ] );
			}
			return $data;
		}
		$data         = self::normalize( $data );
		self::$errors = self::errors( $data, self::cart_total() );
		return $data;
	}

	public static function validate_checkout( $data, $errors ) {
		foreach ( self::$errors as $e ) {
			$errors->add( 'validation', $e[1], array( 'id' => $e[0] ) );
		}
	}

	public static function validate_account( $user_id, $load_address, $address, $customer ) {
		if ( 'billing' !== $load_address || ! self::applies( $customer->get_billing_country() ) ) {
			return;
		}
		$posted = array();
		foreach ( array_merge( array_map( function ( $s ) { return 'billing_wdfb_' . $s; }, self::SUBKEYS ), array( 'billing_company' ) ) as $k ) {
			$posted[ $k ] = isset( $_POST[ $k ] ) ? wc_clean( wp_unslash( $_POST[ $k ] ) ) : ''; // phpcs:ignore
		}
		$data = self::normalize( $posted );

		foreach ( self::errors( $data, 0 ) as $e ) {
			if ( false !== strpos( $e[1], 'TC kimlik numarası</strong> gerekli' ) && 'threshold' === WDFB_Settings::get( 'tc_mode' ) ) {
				continue;
			}
			wc_add_notice( $e[1], 'error', array( 'id' => $e[0] ) );
		}

		$customer->set_billing_company( $data['billing_company'] );
		foreach ( self::SUBKEYS as $sub ) {
			$customer->update_meta_data( 'billing_wdfb_' . $sub, $data[ 'billing_wdfb_' . $sub ] );
		}
	}

	public static function fragments( $fragments ) {
		$fragments['.wdfb-total-holder'] = '<span class="wdfb-total-holder" data-total="' . esc_attr( self::cart_total() ) . '" hidden></span>';
		return $fragments;
	}

	/* ------------------------------------------------------------------ */

	public static function assets() {
		$checkout = function_exists( 'is_checkout' ) && is_checkout() && ! is_wc_endpoint_url( 'order-received' ) && ! is_wc_endpoint_url( 'order-pay' );
		$account  = function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'edit-address' );
		if ( ! $checkout && ! $account ) {
			return;
		}
		$s = WDFB_Settings::all();

		wp_enqueue_style( 'wdfb', WDFB_URL . 'assets/css/wdfb.css', array(), WDFB_VERSION );
		wp_add_inline_style( 'wdfb', sprintf( '.wdfb{--wdfb-accent:%s;--wdfb-radius:%dpx}', esc_attr( $s['accent'] ), (int) $s['radius'] ) );

		wp_enqueue_script( 'wdfb', WDFB_URL . 'assets/js/wdfb.js', array( 'jquery' ), WDFB_VERSION, true );
		$d = WDFB_Data::all();
		wp_localize_script( 'wdfb', 'WDFB_CFG', array(
			'vd'        => $d['vd'],
			'iller'     => $d['iller'],
			'tcMode'    => $s['tc_mode'],
			'threshold' => (float) $s['tc_threshold'],
			'unvanReq'  => (int) $s['unvan_required'],
			'vdReq'     => (int) $s['vd_required'],
			'vknReq'    => (int) $s['vkn_required'],
			'isAccount' => $account ? 1 : 0,
		) );
	}
}
