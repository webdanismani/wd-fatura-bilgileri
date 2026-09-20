# Katkı

Katkılar memnuniyetle kabul edilir. Küçük düzeltmeler için doğrudan PR açabilirsiniz; büyük değişiklikler için önce bir issue veya
[oblifex.com/ozellik-istegi](https://oblifex.com/ozellik-istegi?utm_source=github&utm_medium=contributing&utm_campaign=wd-fatura-bilgileri) üzerinden konuşalım.

## Kurallar

- **Dil:** Kod, yorumlar ve arayüz metinleri Türkçe (mevcut isimlendirmeyi izleyin: `wdfb_*`, `class-wdfb-*.php`).
- **Güvenlik:** Her çıktı `esc_html` / `esc_attr` / `esc_url`; her giriş `sanitize_*`; her form `wp_nonce_field` + `check_admin_referer`.
- **Uyumluluk:** PHP 7.4+, WordPress 6.0+, WooCommerce 7.0+ (HPOS dahil).
- **Sürüm:** `Version:` başlığı, `WDFB_VERSION` sabiti, `readme.txt` içindeki `Stable tag` ve `CHANGELOG.md` birlikte güncellenir.
- **Yayın:** `git tag v1.0.1 && git push --tags` → GitHub Actions ZIP'i üretir ve Release'e ekler; kullanıcılar WordPress panelinde güncellemeyi görür.

## Yerelde deneme

```bash
git clone https://github.com/oblifex/wd-fatura-bilgileri.git wp-content/plugins/wd-fatura-bilgileri
```
