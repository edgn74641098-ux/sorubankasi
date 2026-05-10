# Soru Bankasi

Laravel tabanli, rol bazli yonetim ve gelismis test motoru iceren soru bankasi platformu.

## Proje Ozeti

Bu repository icinde asagidaki ana uygulama bulunur:

- `soru-bankasi/` -> Laravel 10 uygulamasi (ana kod tabani)

## Ozellikler

- Rol bazli yetkilendirme (`admin`, `editor`, `user`)
- Ders ve soru yonetimi
- Test motoru (farkli modlar, puanlama, performans takibi)
- Leaderboard ve kullanici performans ekranlari
- Itiraz ve oneri yonetimi
- Arsivleme, geri alma ve audit log
- CSV import ve yonetim paneli operasyonlari

## Kurulum (Hizli Baslangic)

```bash
cd soru-bankasi
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Uygulama: `http://127.0.0.1:8000`

## Dokumantasyon

Detayli kurulum, operasyon ve teknik notlar icin:

- [Uygulama README](./soru-bankasi/README.md)
- [Katki Rehberi](./soru-bankasi/CONTRIBUTING.md)
- [Guvenlik Politikasi](./soru-bankasi/SECURITY.md)

## Lisans

Bu proje MIT lisansi ile dagitilir.
