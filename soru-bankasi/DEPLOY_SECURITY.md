# Deploy Security Runbook

Bu dokuman, Soru Bankasi projesini VPS uzerinde guvenli sekilde calistirmak icin uygulanan ayarlari adim adim toplar.

## 1) Laravel yazma izinleri

Deploy sonrasi izinleri duzelt:

```bash
cd /var/www/soru-bankasi/soru-bankasi
sudo mkdir -p storage/logs storage/framework/{cache/data,sessions,views,testing} bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

## 2) Guvenli deploy komutlari

Composer scriptlerini root veya farkli user context hatasina dusmeden calistirmak icin:

```bash
cd /var/www/soru-bankasi/soru-bankasi
composer install --no-dev --optimize-autoloader --no-scripts
sudo -u www-data php artisan package:discover --ansi
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan queue:restart
sudo systemctl reload php8.2-fpm
sudo systemctl reload nginx
```

## 3) Queue ve Scheduler servisleri (systemd)

### Queue service

`/etc/systemd/system/sorubankasi-queue.service`

```ini
[Unit]
Description=SoruBankasi Laravel Queue Worker
After=network.target

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

### Scheduler service

`/etc/systemd/system/sorubankasi-scheduler.service`

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
sudo touch /var/www/soru-bankasi/soru-bankasi/storage/logs/queue-worker.log
sudo touch /var/www/soru-bankasi/soru-bankasi/storage/logs/scheduler.log
sudo chown www-data:www-data /var/www/soru-bankasi/soru-bankasi/storage/logs/queue-worker.log
sudo chown www-data:www-data /var/www/soru-bankasi/soru-bankasi/storage/logs/scheduler.log

sudo systemctl daemon-reload
sudo systemctl enable sorubankasi-queue.service
sudo systemctl enable sorubankasi-scheduler.service
sudo systemctl restart sorubankasi-queue.service
sudo systemctl restart sorubankasi-scheduler.service
```

## 4) Mail guvenli modu

Gecici/guvenli kurulumda:

```env
QUEUE_CONNECTION=database
MAIL_MAILER=log
```

Bu modda mail gercek SMTP'ye gitmez, `storage/logs/laravel.log` dosyasina yazilir.

## 5) SSH korumasi (Fail2ban)

Kurulum:

```bash
sudo apt update && sudo apt install -y fail2ban
sudo tee /etc/fail2ban/jail.d/sshd.local > /dev/null << 'EOF'
[sshd]
enabled = true
port = ssh
logpath = /var/log/auth.log
backend = auto
maxretry = 4
findtime = 10m
bantime = 24h
bantime.increment = true
bantime.factor = 2
bantime.maxtime = 1w
EOF
sudo systemctl enable --now fail2ban
sudo systemctl restart fail2ban
```

Kontrol:

```bash
sudo fail2ban-client ping
sudo fail2ban-client status
sudo fail2ban-client status sshd
```

## 6) SSH erisimini IAP ile sinirlama

UFW ile SSH'i sadece Google IAP araligina ac:

```bash
sudo apt install -y ufw
sudo ufw allow from 35.235.240.0/20 to any port 22 proto tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable
sudo ufw status numbered
```

Not: GCP Firewall tarafinda `0.0.0.0/0` ile acik SSH kurali varsa kaldir veya sadece `35.235.240.0/20` olarak sinirla.

## 7) Boot sonrasi hizli kontrol listesi

```bash
sudo systemctl status nginx --no-pager
sudo systemctl status php8.2-fpm --no-pager
sudo systemctl status sorubankasi-queue.service --no-pager
sudo systemctl status sorubankasi-scheduler.service --no-pager
sudo systemctl status fail2ban --no-pager
sudo ufw status verbose
curl -I -H "Host: sorubankasi.duckdns.org" http://127.0.0.1
```

Beklenen:

- Nginx / PHP-FPM / queue / scheduler `active (running)`
- fail2ban `active (running)`
- UFW `active`
- `curl` cevabi `301` (https yonlendirme) veya dogrudan `200`
