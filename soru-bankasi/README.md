# Soru Bankasi

Laravel 10 tabanli, rol bazli yonetim ve test motoru iceren bir soru bankasi uygulamasi.
Proje hem local gelistirme hem de cPanel/shared hosting senaryolari icin duzenlenmistir.

## Icerik

1. [Temel Ozellikler](#temel-ozellikler)
2. [Teknoloji Yigini](#teknoloji-yigini)
3. [Hizli Kurulum (Local)](#hizli-kurulum-local)
4. [Uretim Kurulumu (cPanel/VPS)](#uretim-kurulumu-cpanelvps)
5. [Roller ve Yetkiler](#roller-ve-yetkiler)
6. [Sik Kullanilan Komutlar](#sik-kullanilan-komutlar)
7. [API Uclari](#api-uclari)
8. [Tema ve UI Standardi](#tema-ve-ui-standardi)
9. [Sorun Giderme](#sorun-giderme)
10. [Deploy Security Runbook](#deploy-security-runbook)

## Temel Ozellikler

- Kimlik dogrulama ve rol bazli yetkilendirme (`admin`, `editor`, `user`)
- Ders/soru yonetimi, soru versiyonlama ve rollback
- Test motoru:
  - 20 soru / 30 dakika
  - `RANDOM`, `DIFFICULTY_RANGE`, `WEAKNESSES` modlari
  - Geri bildirim modlari (`DELAYED`, `INSTANT_LOCKED`, `NO_FEEDBACK`)
- Kullanici itirazlari ve onerileri (admin onay/red akisi)
- Leaderboard ve performans metrikleri
- Import sistemi (CSV yukleme, inceleme, onay)
- Arsiv yonetimi (ders/soru arsive alma, geri alma, otomatik temizleme)
- Audit log ve yonetim paneli operasyon kayitlari

## Teknoloji Yigini

- PHP `^8.1`
- Laravel `^10.10`
- Veritabani: SQLite / MySQL (ortama gore)
- UI: Bootstrap 5 + Bootstrap Icons + Vite
- Ek paketler:
  - `laravel/sanctum`
  - `barryvdh/laravel-dompdf`
  - `laravel/socialite` (opsiyonel)

## Hizli Kurulum (Local)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Uygulama varsayilan olarak:

- URL: `http://127.0.0.1:8000`
- Seed admin:
  - E-posta: `admin@sorubank.com`
  - Sifre: `password`

## Uretim Kurulumu (cPanel/VPS)

### 1) Gerekli adimlar

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm install
npm run build
php artisan storage:link
```

### 2) Onerilen `.env` ayarlari

```env
APP_ENV=production
APP_DEBUG=false
CACHE_DRIVER=file
QUEUE_CONNECTION=database
SESSION_DRIVER=cookie
LOG_CHANNEL=single
```

### 3) Scheduler cron

```bash
*/5 * * * * cd /home/USER/soru-bankasi && php artisan schedule:run >> /dev/null 2>&1
```

### 4) Health check

```text
/health
```

## Deploy Security Runbook

Canli ortam guvenlik ve servis kaliciligi adimlari icin:

- [DEPLOY_SECURITY.md](DEPLOY_SECURITY.md)

## Roller ve Yetkiler

- `admin`
  - Tum yonetim panelleri
  - Kullanici/rol yonetimi
  - Ayarlar ve audit log erisimi
- `editor`
  - Soru/icerik yonetimi
  - Moderasyon ekranlari
- `user`
  - Test cozum, leaderboard, itiraz/oneri gonderimi

## Sik Kullanilan Komutlar

### Gelistirme

```bash
php artisan serve
npm run dev
php artisan test
```

### Uretim bakim

```bash
php artisan leaderboard:snapshot
php artisan queue:work --once --max-jobs=10
php artisan cleanup:audit-logs --days=90
php artisan archive:prune
php artisan backup:database
```

### Cache smoke kontrolu

```bash
php artisan route:cache
php artisan view:cache
php artisan config:cache
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

### Veri duzeltme yardimci komutu

Bos cevaplardan sisen gecmis soru gecmisi kayitlarini duzeltmek icin:

```bash
php artisan data:fix-recent-history --dry-run
php artisan data:fix-recent-history
```

## API Uclari

Sanctum token kullanan istemciler icin (yetki/kurallar web ile aynidir):

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

## Tema ve UI Standardi

- Ana tema dosyasi: `public/css/sorubank-theme.css`
- Admin stili: `resources/css/admin.css`
- Ayrintili kural seti: `THEME_STANDARD.md`

Standart karar:

- Bootstrap grid/component tabanli tutarli arayuz
- Ortak renk, spacing, radius ve tipografi tokenlarinin kullanimi
- Yeni ekranlarda mevcut tasarim diline sadik kalinmasi

## Sorun Giderme

### Migration/kolon hatalari

```bash
php artisan migrate
```

Gerekirse local ortamda:

```bash
php artisan migrate:fresh --seed
```

### Log temizleme

```bash
# Windows PowerShell
Clear-Content storage\\logs\\laravel.log
```

### Mailpit baglanti hatasi (local)

Mailpit kullanilmiyorsa `.env` icinde mail ayarini `log` driver ile calistirin:

```env
MAIL_MAILER=log
```

---

Projeye katkida bulunurken degisiklikleri kucuk ve izlenebilir commitlerle ilerletmeniz ve testleri (`php artisan test`) calistirmaniz onerilir.
