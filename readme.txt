=== WD Fatura Bilgileri — Bireysel / Kurumsal ===
Contributors: webdanismani
Tags: woocommerce, fatura, vergi dairesi, tc kimlik, e-fatura
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
WC requires at least: 7.0
WC tested up to: 11.1
Stable tag: 1.0.1
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

WooCommerce ödeme sayfasında bireysel / kurumsal fatura seçimi: TC kimlik no, firma ünvanı, vergi dairesi ve VKN — algoritmik doğrulama ile.

== Description ==

Türkiye'deki mağazaların fatura kesebilmesi için gereken bilgileri ödeme sırasında doğru ve eksiksiz toplar.

**Bireysel fatura**

* TC kimlik numarası: gizli, isteğe bağlı, zorunlu veya belirli sipariş tutarının üzerinde zorunlu
* Resmi algoritma ile anlık doğrulama
* "TC kimlik numaram yok" seçeneği (yabancı uyruklu — 11111111111)

**Kurumsal fatura**

* Firma ünvanı (WooCommerce şirket adı alanına yazılır)
* 1.047 vergi dairesi ve malmüdürlüğü arasından aranabilir seçim; müşterinin seçtiği ildeki daireler üstte
* 10 haneli VKN veya şahıs şirketleri için 11 haneli TCKN — otomatik tanıma ve doğrulama
* e-Fatura mükellefi seçeneği

**Mağaza tarafı**

* Sipariş ekranında doğrulama durumlu fatura kutusu ve düzenlenebilir alanlar
* Sipariş listesinde Bireysel / Kurumsal ve e-Fatura sütunu
* Kurumsal siparişlerde fatura adresine VKN ve vergi dairesi satırı
* Sipariş e-postaları ve sipariş detay sayfasında fatura bilgileri
* e-Fatura / muhasebe eklentileri için ek meta anahtarları
* KVKK: müşteriye giden içerikte TCKN maskeleme, WordPress kişisel veri dışa aktarma ve silme desteği
* Hesabım › Fatura adresi desteği, HPOS uyumlu
* Sunucu tarafı doğrulama; mobilde alttan açılan seçim paneli
* WD Türkiye Adres eklentisiyle aynı tasarım dili

== Destek, özellik isteği ve güncellemeler ==

Bu eklenti Oblifex (https://oblifex.com) tarafından ücretsiz sunulur. Güncellemeler GitHub sürümlerinden (https://github.com/webdanismani/wd-fatura-bilgileri) WordPress paneline otomatik gelir; Eklentiler ekranındaki "Güncellemeleri denetle" bağlantısı denetimi hemen yapar.

* Özellik isteği: https://oblifex.com
* Destek: https://oblifex.com
* Özel geliştirme: https://oblifex.com

== Installation ==

1. ZIP dosyasını Eklentiler › Yeni Ekle › Eklenti Yükle ile yükleyip etkinleştirin.
2. WooCommerce › Fatura Bilgileri sayfasından ayarları yapın.
3. Ödeme sayfanız blok tabanlıysa klasik yapıya geçirin (Uyumluluk sekmesi).

== Frequently Asked Questions ==

= Bilgiler siparişte hangi anahtarlarla saklanır? =

`_billing_wdfb_tip`, `_billing_wdfb_tc`, `_billing_wdfb_vkn`, `_billing_wdfb_vd`, `_billing_wdfb_vd_kod`, `_billing_wdfb_efatura`. Firma ünvanı `billing_company` alanındadır. Ayarlardan ek anahtarlar tanımlayabilirsiniz.

= Numaralar gerçekten var mı diye kontrol ediliyor mu? =

Hayır. Numaralar resmi kontrol hanesi algoritmasıyla doğrulanır; bu, yanlış yazılan numaraların büyük çoğunluğunu yakalar ancak numaranın bir kişiye/şirkete ait olduğunu garanti etmez.

== Credits ==

* Vergi dairesi listesi: kursattaner/2024-turkey-list-of-tax-offices (GİB, Nisan 2024, GPL-3.0)

== Changelog ==

= 1.0.1 =
* Eksik dosya nedeniyle etkinleştirmede oluşan kritik hata giderildi.
* Eksik kurulum koruması ve güvenli güncelleme.
* Destek: oblifex.com · Geliştirici: Web Danışmanı (webdanismani.com).

= 1.0.0 =
* İlk sürüm.
* GitHub sürümlerinden otomatik güncelleme.
* Oblifex destek ve özellik isteği bağlantıları.
