<?php
defined( 'ABSPATH' ) || exit;

class WDFB_Admin {

	const SLUG = 'wd-fatura-bilgileri';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 61 );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_wdfb_action', array( __CLASS__, 'handle_action' ) );
	}

	public static function menu() {
		$parent = class_exists( 'WooCommerce' ) ? 'woocommerce' : 'options-general.php';
		add_submenu_page( $parent, 'WD Fatura Bilgileri', 'Fatura Bilgileri', 'manage_woocommerce', self::SLUG, array( __CLASS__, 'page' ) );
	}

	public static function register() {
		register_setting( 'wdfb_group', WDFB_Settings::OPTION, array(
			'type'              => 'array',
			'sanitize_callback' => array( 'WDFB_Settings', 'sanitize' ),
			'default'           => WDFB_Settings::defaults(),
		) );
	}

	public static function assets( $hook ) {
		if ( false === strpos( (string) $hook, self::SLUG ) ) {
			return;
		}
		wp_enqueue_style( 'wdfb-admin', WDFB_URL . 'assets/css/admin.css', array(), WDFB_VERSION );
		wp_enqueue_script( 'wdfb-admin', WDFB_URL . 'assets/js/admin.js', array(), WDFB_VERSION, true );
		wp_add_inline_script( 'wdfb-admin', self::tool_js() );
	}

	private static function checkout_state() {
		if ( ! function_exists( 'wc_get_page_id' ) ) {
			return array( 'id' => 0, 'block' => false, 'backup' => false );
		}
		$id   = (int) wc_get_page_id( 'checkout' );
		$post = $id > 0 ? get_post( $id ) : null;
		return array(
			'id'     => $id,
			'block'  => $post ? has_block( 'woocommerce/checkout', $post ) : false,
			'backup' => $post ? (bool) get_post_meta( $id, '_wdta_block_backup', true ) : false,
		);
	}

	public static function handle_action() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'Yetkiniz yok.' );
		}
		check_admin_referer( 'wdfb_action' );
		$st = self::checkout_state();
		if ( $st['id'] && $st['block'] ) {
			$post = get_post( $st['id'] );
			update_post_meta( $st['id'], '_wdta_block_backup', wp_slash( $post->post_content ) );
			wp_update_post( array( 'ID' => $st['id'], 'post_content' => "<!-- wp:shortcode -->\n[woocommerce_checkout]\n<!-- /wp:shortcode -->" ) );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&wdfb_msg=classic#uyumluluk' ) );
		exit;
	}

	private static function toggle( $key, $title, $desc ) {
		$s = WDFB_Settings::all();
		printf(
			'<label class="wa-switch"><span class="wa-switch__text"><strong>%1$s</strong><small>%2$s</small></span><input type="checkbox" name="%3$s[%4$s]" value="1" %5$s><i aria-hidden="true"></i></label>',
			esc_html( $title ), esc_html( $desc ), esc_attr( WDFB_Settings::OPTION ), esc_attr( $key ), checked( ! empty( $s[ $key ] ), true, false )
		);
	}

	private static function cards( $key, $options ) {
		$s = WDFB_Settings::all();
		echo '<div class="wa-cards">';
		foreach ( $options as $val => $o ) {
			printf(
				'<label class="wa-card"><input type="radio" name="%1$s[%2$s]" value="%3$s" %4$s><span class="wa-card__in"><strong>%5$s</strong><small>%6$s</small></span></label>',
				esc_attr( WDFB_Settings::OPTION ), esc_attr( $key ), esc_attr( $val ), checked( (string) $s[ $key ], (string) $val, false ), esc_html( $o[0] ), esc_html( $o[1] )
			);
		}
		echo '</div>';
	}

	private static function text( $key, $placeholder, $mono = true ) {
		$s = WDFB_Settings::all();
		printf(
			'<div class="wa-field"><input type="text" name="%1$s[%2$s]" value="%3$s" placeholder="%4$s"%5$s></div>',
			esc_attr( WDFB_Settings::OPTION ), esc_attr( $key ), esc_attr( $s[ $key ] ), esc_attr( $placeholder ), $mono ? '' : ' style="font-family:inherit"'
		);
	}

	private static function icon( $d ) {
		return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="' . esc_attr( $d ) . '" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	}

	public static function page() {
		$s   = WDFB_Settings::all();
		$st  = self::checkout_state();
		$opt = WDFB_Settings::OPTION;
		$msg = isset( $_GET['wdfb_msg'] ) ? sanitize_key( wp_unslash( $_GET['wdfb_msg'] ) ) : ''; // phpcs:ignore
		?>
		<div class="wa" data-wa-theme="dark" lang="tr">
			<header class="wa-top">
				<div class="wa-brand">
					<span class="wa-brand__mark" aria-hidden="true">
						<svg viewBox="0 0 32 32"><path d="M9 4.5h10l6 6v17H9z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M19 4.5v6h6M13 16h8M13 20.5h5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
					</span>
					<div>
						<h1>Fatura Bilgileri</h1>
						<p>Bireysel · Kurumsal · TCKN · VKN · Vergi dairesi — v<?php echo esc_html( WDFB_VERSION ); ?></p>
					</div>
				</div>
				<div class="wa-top__actions">
					<span class="wa-status <?php echo $s['enabled'] ? 'is-on' : ''; ?>"><i></i><?php echo $s['enabled'] ? 'Aktif' : 'Kapalı'; ?></span>
					<button type="button" class="wa-theme" data-wa-theme-toggle aria-label="Tema değiştir"><?php echo self::icon( 'M20 14.5A8 8 0 0 1 9.5 4 8 8 0 1 0 20 14.5Z' ); // phpcs:ignore ?></button>
				</div>
			</header>

			<?php if ( isset( $_GET['settings-updated'] ) ) : // phpcs:ignore ?><div class="wa-flash">Ayarlar kaydedildi.</div><?php endif; ?>
			<?php if ( 'classic' === $msg ) : ?><div class="wa-flash">Ödeme sayfası klasik yapıya dönüştürüldü. Blok içerik yedeklendi.</div><?php endif; ?>
			<?php if ( $st['block'] ) : ?>
				<div class="wa-alert"><?php echo self::icon( 'M12 8v5M12 16.5v.01M10.3 3.9 2.6 17.2A2 2 0 0 0 4.3 20h15.4a2 2 0 0 0 1.7-2.8L13.7 3.9a2 2 0 0 0-3.4 0Z' ); // phpcs:ignore ?><div><strong>Ödeme sayfanız blok tabanlı.</strong> Fatura bilgisi alanları klasik ödeme sayfasında çalışır. Uyumluluk sekmesinden dönüştürebilirsiniz.</div></div>
			<?php endif; ?>

			<div class="wa-layout">
				<nav class="wa-nav" role="tablist">
					<a href="#genel" class="is-active"><?php echo self::icon( 'M4 6h16M4 12h16M4 18h10' ); // phpcs:ignore ?>Genel</a>
					<a href="#bireysel"><?php echo self::icon( 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4.5 20.5c.8-3.8 3.7-6 7.5-6s6.7 2.2 7.5 6' ); // phpcs:ignore ?>Bireysel</a>
					<a href="#kurumsal"><?php echo self::icon( 'M4.5 20.5V6.5l7-3v17M11.5 9.5h8v11M3 20.5h18M7.5 9h1M7.5 12.5h1M7.5 16h1M14.5 13h2M14.5 16.5h2' ); // phpcs:ignore ?>Kurumsal</a>
					<a href="#gorunum"><?php echo self::icon( 'M12 3a9 9 0 1 0 0 18c1.2 0 1.8-.9 1.8-1.8 0-1.2-.9-1.6-.9-2.6 0-1 .8-1.6 1.8-1.6H17a4 4 0 0 0 4-4C21 6.6 17 3 12 3Z' ); // phpcs:ignore ?>Görünüm</a>
					<a href="#uyumluluk"><?php echo self::icon( 'M9 12l2 2 4-4M12 3l7 3v6c0 4.5-3 7.8-7 9-4-1.2-7-4.5-7-9V6l7-3Z' ); // phpcs:ignore ?>Uyumluluk &amp; KVKK</a>
					<a href="#araclar"><?php echo self::icon( 'M14.5 6.5a4 4 0 0 0-5.3 5.3L4 17v3h3l5.2-5.2a4 4 0 0 0 5.3-5.3l-2.5 2.5-2.5-.5-.5-2.5 2.5-2.5Z' ); // phpcs:ignore ?>Araçlar</a>
					<div class="wa-nav__foot">
						<a class="wa-credit" href="https://webdanismani.com" target="_blank" rel="noopener"><small>Geliştirici</small><strong>Web Danışmanı</strong><span>Özel eklenti ve yazılım için iletişime geçin</span></a>
					</div>
				</nav>

				<main class="wa-main">
					<form method="post" action="options.php">
						<?php settings_fields( 'wdfb_group' ); ?>

						<section class="wa-panel is-active" id="genel">
							<div class="wa-panel__head"><h2>Genel</h2><p>Fatura türü seçiminin davranışı.</p></div>
							<div class="wa-group">
								<?php self::toggle( 'enabled', 'Eklentiyi etkinleştir', 'Ödeme sayfası ve Hesabım › Fatura adresi.' ); ?>
								<?php self::toggle( 'show_summary', 'Fatura özeti', 'Müşteri bilgi girdikçe faturanın kime kesileceğini gösteren satır.' ); ?>
								<?php self::toggle( 'address_line', 'Adrese VKN ve vergi dairesi ekle', 'Kurumsal siparişlerde fatura adresinin altına yazılır; kargo etiketi ve PDF faturalarda görünür.' ); ?>
								<?php self::toggle( 'email_block', 'E-postalarda fatura bilgileri', 'Sipariş e-postalarına ayrı bir tablo olarak eklenir.' ); ?>
								<?php self::toggle( 'list_column', 'Sipariş listesinde "Fatura" sütunu', 'Bireysel / Kurumsal ve e-Fatura etiketi.' ); ?>
							</div>
							<div class="wa-sub"><h3>Varsayılan seçim</h3></div>
							<?php self::cards( 'default_tip', array( 'bireysel' => array( 'Bireysel', 'Perakende satış yapan mağazalar için.' ), 'kurumsal' => array( 'Kurumsal', 'B2B ve toptan satış yapan mağazalar için.' ) ) ); ?>
						</section>

						<section class="wa-panel" id="bireysel">
							<div class="wa-panel__head"><h2>Bireysel fatura</h2><p>TC kimlik numarası alanının davranışı. Numara her durumda algoritmik olarak doğrulanır.</p></div>
							<?php
							self::cards( 'tc_mode', array(
								'hidden'    => array( 'Gizli', 'TC kimlik numarası sorulmaz.' ),
								'optional'  => array( 'İsteğe bağlı', 'Önerilen. Girilirse doğrulanır.' ),
								'required'  => array( 'Zorunlu', 'Her bireysel siparişte istenir.' ),
								'threshold' => array( 'Tutar eşiği', 'Sipariş toplamı eşiği aşınca zorunlu olur.' ),
							) );
							?>
							<div class="wa-sub"><h3>Tutar eşiği (₺)</h3><p>"Tutar eşiği" seçiliyse bu tutar ve üzerindeki siparişlerde TC kimlik numarası zorunlu olur.</p></div>
							<?php self::text( 'tc_threshold', '5000' ); ?>
							<div class="wa-sub"><h3>Seçenekler</h3></div>
							<div class="wa-group">
								<?php self::toggle( 'allow_foreign', '"TC kimlik numaram yok" seçeneği', 'Yabancı uyruklu müşteriler için 11111111111 kullanılır.' ); ?>
							</div>
						</section>

						<section class="wa-panel" id="kurumsal">
							<div class="wa-panel__head"><h2>Kurumsal fatura</h2><p>Şirketler ve şahıs şirketleri. Vergi numarası alanı 10 haneli VKN ve 11 haneli TCKN kabul eder.</p></div>
							<div class="wa-group">
								<?php self::toggle( 'unvan_required', 'Firma ünvanı zorunlu', 'WooCommerce\'in "Şirket adı" alanına yazılır.' ); ?>
								<?php self::toggle( 'vd_required', 'Vergi dairesi zorunlu', '1.047 vergi dairesi ve malmüdürlüğü arasından aranarak seçilir.' ); ?>
								<?php self::toggle( 'vkn_required', 'Vergi / TC kimlik no zorunlu', 'Geçersiz numaralar her durumda reddedilir.' ); ?>
								<?php self::toggle( 'show_efatura', 'e-Fatura mükellefi seçeneği', 'Muhasebeniz faturayı e-Fatura mı e-Arşiv mi keseceğini bilir.' ); ?>
							</div>
						</section>

						<section class="wa-panel" id="gorunum">
							<div class="wa-panel__head"><h2>Görünüm</h2><p>WD Türkiye Adres ile aynı tasarım dilini kullanır; iki eklentiyi aynı renge ayarlayabilirsiniz.</p></div>
							<div class="wa-sub"><h3>Tema</h3></div>
							<?php self::cards( 'theme', array( 'auto' => array( 'Siteye uyum', 'Arka plan rengini okuyarak seçer.' ), 'light' => array( 'Açık', 'Açık temalar için.' ), 'dark' => array( 'Koyu', 'Koyu temalar için.' ) ) ); ?>
							<div class="wa-sub"><h3>Vurgu rengi</h3></div>
							<div class="wa-colors">
								<?php foreach ( array( '#1f6f5c' => 'Zümrüt', '#b8893b' => 'Altın', '#1d4ed8' => 'Lacivert', '#c2410c' => 'Kiremit', '#18181b' => 'Mürekkep', '#7c2d5a' => 'Bordo' ) as $hex => $name ) : ?>
									<button type="button" class="wa-swatch" data-color="<?php echo esc_attr( $hex ); ?>" style="--sw:<?php echo esc_attr( $hex ); ?>"><span></span><?php echo esc_html( $name ); ?></button>
								<?php endforeach; ?>
								<label class="wa-picker"><input type="color" name="<?php echo esc_attr( $opt ); ?>[accent]" value="<?php echo esc_attr( $s['accent'] ); ?>" data-wa-accent><code data-wa-accent-code><?php echo esc_html( $s['accent'] ); ?></code></label>
							</div>
							<div class="wa-sub"><h3>Köşe yuvarlaklığı</h3></div>
							<div class="wa-range" style="max-width:420px"><input type="range" min="0" max="24" name="<?php echo esc_attr( $opt ); ?>[radius]" value="<?php echo esc_attr( $s['radius'] ); ?>" data-wa-radius><output data-wa-radius-out><?php echo (int) $s['radius']; ?>px</output></div>
						</section>

						<section class="wa-panel" id="uyumluluk">
							<div class="wa-panel__head"><h2>Uyumluluk &amp; KVKK</h2><p>e-Fatura entegrasyonları ve kişisel veri koruması.</p></div>
							<div class="wa-group">
								<?php self::toggle( 'mask_customer', 'Müşteriye giden içerikte TCKN maskele', 'E-posta ve sipariş detayında 123••••••45 biçiminde gösterilir. Yönetici e-postaları tam numarayı içerir.' ); ?>
							</div>

							<div class="wa-sub"><h3>Ek meta anahtarları</h3><p>Kullandığınız e-Fatura / muhasebe eklentisi bilgileri belirli bir anahtardan okuyorsa buraya yazın; değerler siparişe o anahtarla da kaydedilir. Boş bırakılan alan kullanılmaz.</p></div>
							<div class="wa-kv wa-meta">
								<?php foreach ( array( 'meta_tip' => 'Fatura türü', 'meta_tc' => 'TC kimlik no', 'meta_vkn' => 'Vergi / TC kimlik no', 'meta_vd' => 'Vergi dairesi' ) as $k => $l ) : ?>
									<div><span><?php echo esc_html( $l ); ?></span><b><input type="text" name="<?php echo esc_attr( $opt ); ?>[<?php echo esc_attr( $k ); ?>]" value="<?php echo esc_attr( $s[ $k ] ); ?>" placeholder="örn. _billing_<?php echo esc_attr( str_replace( 'meta_', '', $k ) ); ?>"></b></div>
								<?php endforeach; ?>
							</div>
							<p class="wa-note">Eklentinin kendi anahtarları her zaman yazılır: <code>_billing_wdfb_tip</code>, <code>_billing_wdfb_tc</code>, <code>_billing_wdfb_vkn</code>, <code>_billing_wdfb_vd</code>, <code>_billing_wdfb_vd_kod</code>, <code>_billing_wdfb_efatura</code>. Firma ünvanı WooCommerce'in <code>billing_company</code> alanındadır.</p>

							<div class="wa-kv">
								<div><span>Ödeme sayfası</span><b class="<?php echo $st['block'] ? 'is-warn' : 'is-ok'; ?>"><?php echo $st['block'] ? 'Blok (Checkout Block)' : 'Klasik — uyumlu'; ?></b></div>
								<div><span>HPOS</span><b class="is-ok">Uyumlu</b></div>
								<div><span>Kişisel veri dışa aktarma / silme</span><b class="is-ok">Destekleniyor</b></div>
							</div>
						</section>

						<div class="wa-save" data-wa-save><span>Değişiklikleri kaydetmeyi unutmayın.</span><button type="submit" class="wa-btn wa-btn--primary">Ayarları kaydet</button></div>
					</form>

					<?php if ( $st['block'] ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wdfb-convert" style="display:none">
						<?php wp_nonce_field( 'wdfb_action' ); ?><input type="hidden" name="action" value="wdfb_action">
						<button class="wa-btn wa-btn--primary" style="margin-top:14px">Klasik ödeme sayfasına dönüştür</button>
					</form>
					<?php endif; ?>

					<section class="wa-panel" id="araclar">
						<div class="wa-panel__head"><h2>Araçlar</h2><p>Numara doğrulama ve veri bilgisi.</p></div>
						<div class="wa-sub"><h3>Hızlı doğrulama</h3><p>TC kimlik no (11 hane) veya vergi kimlik no (10 hane) yazın.</p></div>
						<div class="wa-field wdfb-tool">
							<input type="text" inputmode="numeric" maxlength="11" placeholder="Numara" data-wdfb-tool>
							<output data-wdfb-tool-out>—</output>
						</div>
						<div class="wa-stats" style="margin-top:22px">
							<div class="wa-stat"><strong><?php echo esc_html( number_format_i18n( WDFB_Data::count() ) ); ?></strong><span>Vergi dairesi</span></div>
							<div class="wa-stat"><strong>81</strong><span>İl</span></div>
							<div class="wa-stat"><strong><?php echo esc_html( WDFB_Data::all()['v'] ); ?></strong><span>Veri sürümü (GİB)</span></div>
							<div class="wa-stat"><strong>2</strong><span>Algoritma · TCKN / VKN</span></div>
						</div>
					</section>
				</main>
			</div>
		</div>
		<style>
			.wa-meta input{width:100%;max-width:320px;height:36px;padding:0 10px;border:1px solid var(--line-2);border-radius:8px;background:var(--card-2);color:var(--ink);font-family:var(--mono);font-size:12.5px}
			.wa-meta input:focus{border-color:var(--g);outline:none;box-shadow:0 0 0 3px var(--g-soft)}
			.wa-meta > div{align-items:center}
			.wdfb-tool{display:flex;align-items:center;gap:14px;flex-wrap:wrap}
			.wdfb-tool output{font-weight:600;color:var(--mut)}
			.wdfb-tool output.is-ok{color:var(--ok)}
			.wdfb-tool output.is-bad{color:#f97066}
			.wa[data-wa-theme="light"] .wdfb-tool output.is-bad{color:#b42318}
			#uyumluluk.is-active ~ * .wdfb-convert, .wa-main:has(#uyumluluk.is-active) .wdfb-convert{display:block!important}
		</style>
		<?php
	}

	private static function tool_js() {
		return <<<'JS'
(function(){
	var i=document.querySelector('[data-wdfb-tool]'),o=document.querySelector('[data-wdfb-tool-out]');if(!i)return;
	function tc(v){if(!/^[1-9]\d{10}$/.test(v))return false;var d=v.split('').map(Number);var a=((d[0]+d[2]+d[4]+d[6]+d[8])*7-(d[1]+d[3]+d[5]+d[7]))%10;a=(a+10)%10;return a===d[9]&&(d.slice(0,10).reduce(function(x,y){return x+y},0)%10)===d[10];}
	function vkn(v){if(!/^\d{10}$/.test(v))return false;var d=v.split('').map(Number),s=0;for(var k=0;k<9;k++){var t=(d[k]+(9-k))%10,x=(t*Math.pow(2,9-k))%9;if(t!==0&&x===0)x=9;s+=x;}return (10-s%10)%10===d[9];}
	i.addEventListener('input',function(){var v=i.value.replace(/\D/g,'');i.value=v;o.className='';
		if(v.length===11){var ok=tc(v);o.textContent=v==='11111111111'?'Yabancı uyruklu kodu':ok?'Geçerli TC kimlik no':'Geçersiz TC kimlik no';o.className=ok||v==='11111111111'?'is-ok':'is-bad';}
		else if(v.length===10){var ok2=vkn(v);o.textContent=ok2?'Geçerli vergi kimlik no':'Geçersiz vergi kimlik no';o.className=ok2?'is-ok':'is-bad';}
		else{o.textContent=v.length?v.length+' hane':'—';}
	});
})();
JS;
	}
}
