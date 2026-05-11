# Soru Bankasi Deploy Runbook

Bu dokuman, projeyi VPS uzerinde guncelleme, servisleri kalici calistirma ve hizli kontrol adimlarini icerir.

## 1) Kod Guncelleme

```bash
cd /var/www/soru-bankasi/soru-bankasi

git fetch origin
git checkout main
git pull origin main
```

## 2) Bagimliliklar ve Build

```bash
composer install --no-dev --optimize-autoloader --no-scripts
npm install
npm run build
```

## 3) Laravel Komutlari (www-data ile)

```bash
sudo -u www-data php artisan package:discover --ansi
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan queue:restart
```

## 4) Dizin Izinleri

```bash
sudo mkdir -p storage/logs storage/framework/{cache/data,sessions,views,testing} bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

## 5) Servis Reload

```bash
sudo systemctl reload php8.2-fpm
sudo systemctl reload nginx
```

## 6) Ornek Production .env Ayarlari

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sorubankasi.duckdns.org
ASSET_URL=https://sorubankasi.duckdns.org

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306

SESSION_SECURE_COOKIE=true

QUEUE_CONNECTION=database
MAIL_MAILER=log
```

## 7) Queue Worker (systemd, kalici)

Repo sablonu: `deploy/systemd/sorubankasi-queue.service`  
Sunucu yolu: `/etc/systemd/system/sorubankasi-queue.service`

```ini
[Unit]
Description=SoruBankasi Laravel Queue Worker
After=network.target mysql.service

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=5
WorkingDirectory=/var/www/soru-bankasi/soru-bankasi
ExecStart=/usr/bin/php artisan queue:work database --queue=default --sleep=1 --tries=3 --timeout=120
StandardOutput=append:/var/www/soru-bankasi/soru-bankasi/storage/logs/queue-worker.log
StandardError=append:/var/www/soru-bankasi/soru-bankasi/storage/logs/queue-worker.log

[Install]
WantedBy=multi-user.target
```

Aktif et:

```bash
sudo systemctl daemon-reload
sudo systemctl enable sorubankasi-queue.service
sudo systemctl start sorubankasi-queue.service
```

## 8) Scheduler (systemd, kalici)

Repo sablonu: `deploy/systemd/sorubankasi-scheduler.service`  
Sunucu yolu: `/etc/systemd/system/sorubankasi-scheduler.service`

```ini
[Unit]
Description=SoruBankasi Laravel Scheduler
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=5
WorkingDirectory=/var/www/soru-bankasi/soru-bankasi
ExecStart=/usr/bin/php artisan schedule:work
StandardOutput=append:/var/www/soru-bankasi/soru-bankasi/storage/logs/scheduler.log
StandardError=append:/var/www/soru-bankasi/soru-bankasi/storage/logs/scheduler.log

[Install]
WantedBy=multi-user.target
```

Aktif et:

```bash
sudo systemctl daemon-reload
sudo systemctl enable sorubankasi-scheduler.service
sudo systemctl start sorubankasi-scheduler.service
```

## 9) Reboot Sonrasi Kontrol

```bash
sudo systemctl status sorubankasi-queue.service --no-pager
sudo systemctl status sorubankasi-scheduler.service --no-pager
sudo systemctl status nginx --no-pager
sudo systemctl status php8.2-fpm --no-pager
curl -I -H "Host: sorubankasi.duckdns.org" http://127.0.0.1
```

## 10) Hizli Log Kontrolu

```bash
cd /var/www/soru-bankasi/soru-bankasi
sudo tail -n 120 storage/logs/laravel.log
sudo tail -n 120 storage/logs/queue-worker.log
sudo tail -n 120 storage/logs/scheduler.log
sudo tail -n 120 /var/log/nginx/error.log
```
