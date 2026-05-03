# Soru Bankasi Tema Standardi

Bu proje icin tema standardi zorunludur. Yeni ekran, layout, component veya admin sayfasi bu dosyadaki kurallarin disina cikamaz.

## Tek Kaynak

- Ortak tema dosyasi: `public/css/sorubank-theme.css`
- Renk, radius, shadow ve temel typography tokenlari sadece `:root` icindeki `--sb-*` degiskenlerinden alinabilir.
- Blade dosyalarinda yeni inline renk, font, shadow veya radius yazilmaz. Zorunlu dinamik stiller yalnizca genislik, progress veya grafik degeri gibi runtime degerler icin kullanilabilir.
- Tailwind kullanilan sayfalarda da gorsel kararlar bu tokenlarla uyumlu olmalidir.

## Performans

- Yeni ekranlarda uzak font, uzak gorsel ve yeni CDN eklenmez.
- Ana sayfa build beklemeden calisan statik tema CSS'i ile acilir.
- Agir JavaScript, gereksiz animasyon ve dekoratif arka plan kullanilmaz.
- Tum uygulama Bootstrap tabanini yerel Vite bundle'dan yukler; public, auth, kullanici ve admin ekranlari Bootstrap bilesenlerini `sorubank-theme.css` tokenlariyla kullanir.

## Renk Paleti

- Ana metin: `--sb-ink`
- Ikincil metin: `--sb-muted`
- Arka plan: `--sb-soft`
- Yuzey: `--sb-surface`
- Cizgi: `--sb-line`
- Marka: `--sb-brand`, `--sb-brand-dark`
- Vurgu: `--sb-accent`
- Durumlar: `--sb-success`, `--sb-danger`, `--sb-warning`

Yeni renk eklemek gerekiyorsa once bu dosyada gerekcesiyle standarda eklenir, sonra uygulamada kullanilir.

## Bilesen Kurallari

- Kartlar, butonlar, formlar, modallar ve tekrar eden itemlar en fazla `8px` radius kullanir.
- Kart icinde kart yapilmaz; yalnizca tekrar eden item, modal veya gercek arac yuzeyi kart olabilir.
- Butonlar icin once mevcut componentler veya `.sb-btn`, `.sb-btn-primary`, `.sb-btn-secondary` kullanilir.
- Harf araligi `0` kalir; negatif letter spacing veya genis tracking siniflari kullanilmaz.
- Metinler mobil ve masaustu gorunumlerde kapsayicinin disina tasmamalidir.

## Layout Kurallari

- Public sayfalar `sb-public-nav`, `sb-shell`, `sb-section` ve ilgili `sb-*` yardimci siniflarini kullanir.
- Auth sayfalari `sb-auth-shell`, `sb-auth-card` ve Bootstrap form/buton bilesenlerini kullanir.
- Uygulama layout'u `sb-app-shell`, `sb-app-navbar`, `sb-app-header`, Bootstrap kartlari, tablolari ve formlariyla kurulur.
- Admin layout'u `sb-admin-body` ve `sb-admin-sidebar` standardini kullanir.

## Degisiklik Kontrolu

Tema disiplini bozulmamasi icin yeni UI degisikliginde su kontrol yapilir:

```bash
rg "https://fonts|unsplash|cdn.jsdelivr|style=|tracking-" resources public/css
php artisan test
```

Yeni CDN, uzak font veya uzak asset eklemek yasaktir.
