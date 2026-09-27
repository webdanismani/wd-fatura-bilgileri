<h1 align="center">🧾 WD Fatura Bilgileri</h1>

<p align="center">
  <img alt="WordPress 6.0+" src="https://img.shields.io/badge/WordPress-6.0%2B-0e0f11?logo=wordpress&logoColor=c9a45c">
  <img alt="WooCommerce 7.0+" src="https://img.shields.io/badge/WooCommerce-7.0%2B%20%C2%B7%20HPOS-0e0f11?logo=woocommerce&logoColor=c9a45c">
  <img alt="PHP 7.4+" src="https://img.shields.io/badge/PHP-7.4%2B-0e0f11?logo=php&logoColor=c9a45c">
  <a href="LICENSE"><img alt="GPL-3.0" src="https://img.shields.io/badge/lisans-GPL--3.0-0e0f11"></a>
</p>

<p align="center">
  <b>WooCommerce ödeme sayfasında bireysel / kurumsal fatura bilgisi seçimi.</b><br>
  TC kimlik no, firma ünvanı, vergi dairesi ve VKN — algoritmik doğrulama, e-Fatura mükellefi seçeneği, KVKK maskeleme. Ücretsiz ve açık kaynak.
</p>

<p align="center">
  <a href="https://github.com/webdanismani/wd-fatura-bilgileri/archive/refs/heads/main.zip"><b>⬇️ İndir (ZIP)</b></a>
  &nbsp;·&nbsp;
  <a href="#kurulum">Kurulum</a>
  &nbsp;·&nbsp;
  <a href="#özellikler">Özellikler</a>
  &nbsp;·&nbsp;
  <a href="#sss">SSS</a>
  &nbsp;·&nbsp;
  <a href="https://oblifex.com/?utm_source=github&utm_medium=readme-nav&utm_campaign=wd-fatura-bilgileri"><b>oblifex.com</b></a>
</p>

<br>

<h3 align="center">💬 Destek ve daha fazla ücretsiz yazılım: <a href="https://oblifex.com/?utm_source=github&utm_medium=readme-cta&utm_campaign=wd-fatura-bilgileri">oblifex.com</a></h3>
<p align="center">Sorularınızı sorabileceğiniz, bu ve benzeri WordPress / WooCommerce yazılımlarını bulabileceğiniz sitemiz · Geliştirici: <a href="https://webdanismani.com">Web Danışmanı</a></p>

> [!TIP]
> **Bu eklenti [Web Danışmanı](https://webdanismani.com) tarafından geliştirildi ve ücretsiz sunulur. Destek: [oblifex.com](https://oblifex.com/?utm_source=github&utm_medium=readme-tip&utm_campaign=wd-fatura-bilgileri)**
> Eklenti WordPress'e kurulduğunda yeni sürümleri **kendisi bulur**: bu depoya gelen her yeni sürüm, WordPress › Eklentiler ekranında normal bir güncelleme gibi görünür ve tek tıkla kurulur.
>
> | | |
> |---|---|
> | ✨ **Özellik isteği** | Eklentide görmek istediğiniz her şeyi [oblifex.com](https://oblifex.com?utm_source=github&utm_medium=readme-tip&utm_campaign=wd-fatura-bilgileri) üzerinden iletin. Öncelikli olarak değerlendirilir. |
> | 🛟 **Destek** | Kurulum, tema ve uyumluluk soruları: [oblifex.com](https://oblifex.com?utm_source=github&utm_medium=readme-tip&utm_campaign=wd-fatura-bilgileri) |
> | 🔄 **Güncellemeler** | Otomatik. Elle denetlemek için Eklentiler ekranındaki **Güncellemeleri denetle** bağlantısını kullanın. |
> | 🧩 **Özel geliştirme** | e-Fatura / muhasebe entegrasyonu ve mağazanıza özel çözümler için [oblifex.com](https://oblifex.com/?utm_source=github&utm_medium=readme-tip&utm_campaign=wd-fatura-bilgileri) |

<br>

## Neden?

Türkiye'de fatura kesebilmek için müşteriden TC kimlik no ya da firma ünvanı + vergi dairesi + VKN almak zorunludur. Bu eklenti bu bilgileri ödeme sırasında **doğru ve eksiksiz** toplar: resmi kontrol hanesi algoritmalarıyla anında doğrular, 1.047 vergi dairesi arasından aranabilir seçim sunar, siparişe, e-postalara ve yönetim ekranlarına işler.

## Özellikler

**Bireysel fatura**
- TC kimlik numarası: gizli · isteğe bağlı · zorunlu · belirli sipariş tutarının üzerinde zorunlu
- Resmi algoritma ile anlık doğrulama (istemci + sunucu)
- "TC kimlik numaram yok" seçeneği (yabancı uyruklu → `11111111111`)

**Kurumsal fatura**
- Firma ünvanı (WooCommerce `billing_company` alanına yazılır)
- **1.047 vergi dairesi ve malmüdürlüğü** arasından aranabilir seçim; müşterinin ilindeki daireler üstte
- 10 haneli VKN veya şahıs şirketleri için 11 haneli TCKN — **otomatik tanıma ve doğrulama**
- e-Fatura mükellefi seçeneği

**Mağaza tarafı**
- Sipariş ekranında doğrulama durumlu fatura kutusu ve düzenlenebilir alanlar
- Sipariş listesinde Bireysel / Kurumsal ve e-Fatura sütunu
- Kurumsal siparişlerde fatura adresine VKN ve vergi dairesi satırı
- Sipariş e-postaları ve sipariş detay sayfasında fatura bilgileri
- e-Fatura / muhasebe eklentileri için **ek meta anahtarları** tanımlama
- **KVKK:** müşteriye giden içerikte TCKN maskeleme; WordPress kişisel veri dışa aktarma ve silme desteği
- Hesabım › Fatura adresi desteği · **HPOS** uyumlu
- Mobilde alttan açılan seçim paneli
- [WD Türkiye Adres](https://github.com/webdanismani/wd-turkiye-adress) ile aynı tasarım dili — birlikte kullanıldığında tek parça görünür

## Kurulum

**Seçenek 1 — ZIP (önerilen)**

1. [ZIP'i indirin](https://github.com/webdanismani/wd-fatura-bilgileri/archive/refs/heads/main.zip) (ya da bu sayfadaki yeşil **Code → Download ZIP** düğmesi). Zip'i açmayın.
2. WordPress › Eklentiler › Yeni Ekle › **Eklenti Yükle** ile ZIP'i yükleyip etkinleştirin.
3. **WooCommerce › Fatura Bilgileri** sayfasından ayarları yapın.
4. Ödeme sayfanız blok tabanlıysa **Uyumluluk** sekmesinden klasik yapıya dönüştürün.

**Seçenek 2 — git**

```bash
cd wp-content/plugins
git clone https://github.com/webdanismani/wd-fatura-bilgileri.git
```

> Güncellemeler her iki kurulumda da WordPress panelinden gelir.

## Gereksinimler

| | En az |
|---|---|
| WordPress | 6.0 |
| WooCommerce | 7.0 (HPOS destekli) |
| PHP | 7.4 |
| Ödeme sayfası | Klasik `[woocommerce_checkout]` — blok sayfa tek tıkla dönüştürülür |

## Ayarlar

**WooCommerce › Fatura Bilgileri** altında altı sekme:

| Sekme | İçerik |
|---|---|
| Genel | Etkinleştirme, varsayılan fatura tipi, fatura özeti, adrese VKN satırı, e-posta tablosu, sipariş listesi sütunu |
| Bireysel | TCKN modu (gizli / isteğe bağlı / zorunlu / tutar eşiği), "TCKN yok" seçeneği |
| Kurumsal | Ünvan, vergi dairesi ve VKN zorunlulukları, e-Fatura mükellefi seçeneği |
| Görünüm | Açık/koyu/otomatik tema, vurgu rengi, köşe |
| Uyumluluk & KVKK | Ödeme sayfası yapısı, HPOS, TCKN maskeleme, ek meta anahtarları |
| Araçlar | Hızlı TCKN / VKN doğrulayıcı, veri sürümü |

## Sipariş meta anahtarları

```
_billing_wdfb_tip       bireysel | kurumsal
_billing_wdfb_tc        TC kimlik no (bireysel)
_billing_wdfb_vkn       VKN veya şahıs şirketi TCKN (kurumsal)
_billing_wdfb_vd        Vergi dairesi adı
_billing_wdfb_vd_kod    Vergi dairesi kodu (GİB)
_billing_wdfb_efatura   1 | 0
```

Firma ünvanı WooCommerce'in standart `billing_company` alanındadır. Ayarlardan e-Fatura / muhasebe eklentilerinizin beklediği **ek anahtarlar** tanımlayabilirsiniz.

## SSS

<details>
<summary><b>Numaralar gerçekten var mı diye kontrol ediliyor mu?</b></summary>

Hayır. Numaralar resmi kontrol hanesi algoritmasıyla doğrulanır; bu, yanlış yazılan numaraların büyük çoğunluğunu yakalar ancak numaranın bir kişiye/şirkete ait olduğunu garanti etmez.
</details>

<details>
<summary><b>e-Fatura eklentim farklı meta anahtarları bekliyor.</b></summary>

**Uyumluluk & KVKK** sekmesinde her alan için ek anahtar tanımlayabilirsiniz; sipariş kaydedilirken aynı değer o anahtarlara da yazılır. Entegrasyonunuz listede yoksa [özellik isteği](https://oblifex.com?utm_source=github&utm_medium=readme-faq&utm_campaign=wd-fatura-bilgileri) açın.
</details>

<details>
<summary><b>TC kimlik no e-postalarda görünüyor mu?</b></summary>

Müşteriye giden e-postalarda ve sipariş detay sayfasında maskelenir (`123••••••45`); yönetici e-postaları ve sipariş ekranı tam değeri gösterir. **Uyumluluk & KVKK** sekmesinden kapatılabilir.
</details>

<details>
<summary><b>Güncellemeler nasıl geliyor?</b></summary>

Eklenti, `Update URI` başlığı sayesinde bu deponun `main` dalındaki sürüm numarasını 12 saatte bir denetler ve yeni sürümü WordPress'in standart güncelleme akışına ekler. Paket eksik dosya içeriyorsa kurulmaz; mevcut sürüm çalışmaya devam eder. Eklentiler ekranındaki **Güncellemeleri denetle** bağlantısı denetimi hemen yapar. wordpress.org ile hiçbir bağlantısı yoktur.
</details>

## Veri kaynakları

- [kursattaner/2024-turkey-list-of-tax-offices](https://github.com/kursattaner/2024-turkey-list-of-tax-offices) — GİB vergi dairesi listesi, Nisan 2024, GPL-3.0

## Katkı

Hata bildirimi ve PR'lar için [CONTRIBUTING.md](CONTRIBUTING.md). Güvenlik açıkları için [SECURITY.md](SECURITY.md). Değişiklikler: [CHANGELOG.md](CHANGELOG.md).

## Lisans

[GPL-3.0-or-later](LICENSE) © [Web Danışmanı](https://webdanismani.com)

---

<p align="center">
  <a href="https://oblifex.com/?utm_source=github&utm_medium=readme-footer&utm_campaign=wd-fatura-bilgileri">
    <img alt="oblifex.com — destek & özellik isteği" src="https://img.shields.io/badge/oblifex.com-destek%20%26%20%C3%B6zellik%20iste%C4%9Fi-c9a45c?style=for-the-badge&labelColor=0e0f11">
  </a>
  <br><br>
  <sub><a href="https://webdanismani.com"><b>Web Danışmanı</b></a> tarafından geliştirildi · Destek ve daha fazla ücretsiz yazılım: <b>oblifex.com</b></sub>
</p>
