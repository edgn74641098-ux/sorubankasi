# Soru Bankasi

Laravel 10 ile hazirlanmis, cPanel/shared hosting uyumlu soru bankasi uygulamasi.

## Ozellikler

- Breeze auth, e-posta dogrulama ve rol tabanli yetkilendirme
- Roller: `admin`, `editor`, `user`
- Ders ve soru yonetimi
- Soru versiyon gecmisi ve rollback
- 20 soru / 30 dakika test motoru
- Random, zorluk araligi ve takildiklarim test modlari
- Delayed, instant locked ve no feedback modlari
- Kullanici soru onerisi, onay/red, +10 puan ve geri alma
- CSV import: preview, confirm, skip/merge/manual review
- Snapshot tabanli global ve ders leaderboard
- Admin dashboard, kullanici/rol yonetimi, ayarlar ve audit log
- cPanel icin file cache, database queue, cron komutlari ve backup komutu

## Local Kurulum

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Varsayilan admin:

- E-posta: `admin@sorubank.com`
- Sifre: `password`

## Test

```bash
php artisan test
```

## API Durumu

Sanctum token ile, e-postasi dogrulanmis kullanicilar icin aktif API endpointleri:

```text
GET    /api/subjects
GET    /api/subjects/{subject}
GET    /api/tests
GET    /api/tests/{test}
POST   /api/tests/{test}/answer
POST   /api/tests/{test}/finish
GET    /api/leaderboard
GET    /api/leaderboard/subject/{subject}
GET    /api/profile
PATCH  /api/profile
GET    /api/questions/submissions
POST   /api/questions/submit
```

Bu endpointler web ekranlariyla ayni yetkilendirme, dogrulama ve limit kurallarini kullanir.

## Tema Standardi

Projenin ortak tema kurallari `THEME_STANDARD.md` dosyasindadir. Yeni ekran veya component gelistirirken bu standardin disina cikilmez.

Ortak tema kaynagi:

```text
public/css/sorubank-theme.css
```

Tema karari:

- Tum proje admin panelindeki stabil Bootstrap tabanli duzeni kullanir.
- Bootstrap CSS/JS CDN'den degil yerel Vite bundle'dan yuklenir.
- Public, auth, kullanici ve admin ekranlarinda renk, radius, bosluk ve yuzey kurallari `sorubank-theme.css` tokenlarina baglidir.
- Yeni ekranlarda Tailwind odakli layout yazilmaz; Bootstrap grid, card, table, form ve button bilesenleri tercih edilir.

Deploy oncesi cache smoke kontrolu:

```bash
php artisan route:cache
php artisan view:cache
php artisan config:cache
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

## cPanel Ayarlari

Production `.env` icin onerilen degerler:

```env
APP_ENV=production
APP_DEBUG=false
CACHE_DRIVER=file
QUEUE_CONNECTION=database
SESSION_DRIVER=cookie
LOG_CHANNEL=single
```

Cron:

```bash
*/5 * * * * cd /home/USER/soru-bankasi && php artisan schedule:run >> /dev/null 2>&1
```

Planlanan komutlar:

- `leaderboard:snapshot`
- `queue:work --once --max-jobs=10`
- `cleanup:audit-logs --days=90`
- `backup:database`

`backup_mode=automatic` secildiginde `backup:database` komutu scheduler tarafindan her gun 03:00'te calistirilir.

Health check:

```text
/health
```

## Google ile Giris

Google Cloud Console'da OAuth Client olusturup redirect URI olarak sunu ekleyin:

```text
http://127.0.0.1:8000/auth/google/callback
```

Sonra `.env` dosyasina degerleri girin:

```env
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback
```
