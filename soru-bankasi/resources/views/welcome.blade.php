<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Soru Bankasi') }}</title>
    <link rel="stylesheet" href="{{ asset('css/sorubank-theme.css') }}">
</head>
<body>
    <div class="sb-shell">
        <nav class="sb-public-nav">
            <div class="sb-public-nav__inner">
                <a href="{{ url('/') }}" class="sb-brand">
                    <span class="sb-brand-mark">SB</span>
                    <span>
                        <span class="sb-brand-title">Soru Bankasi</span>
                        <span class="sb-brand-subtitle">Hizli test ve soru yonetimi</span>
                    </span>
                </a>

                <div class="sb-nav-actions">
                    @auth
                        <a href="{{ route('dashboard') }}" class="sb-btn sb-btn-primary">Panel</a>
                        @if(auth()->user()->isAdmin() || auth()->user()->isEditor())
                            <a href="{{ route('admin.dashboard') }}" class="sb-btn sb-btn-secondary">Yonetim</a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="sb-btn sb-btn-primary">Giris Yap</a>
                        <a href="{{ route('register') }}" class="sb-btn sb-btn-secondary">Kayit Ol</a>
                    @endauth
                </div>
            </div>
        </nav>

        <header class="sb-hero">
            <div class="sb-hero-grid">
                <div>
                    <div class="sb-kicker">20 soru / 30 dakika</div>
                    <h1>Ders bazli test, soru bankasi ve leaderboard tek sistemde.</h1>
                    <p class="sb-hero-copy">
                        Ogrenciler test cozer, soru onerir ve siralamayi takip eder. Editorler sorulari yonetir,
                        adminler import, ayarlar, audit log ve rollback islemlerini cPanel uyumlu panelden kontrol eder.
                    </p>

                    <div class="sb-hero-actions">
                        @auth
                            <a href="{{ route('subjects.index') }}" class="sb-btn sb-btn-primary">Test Baslat</a>
                            <a href="{{ route('subjects.index') }}" class="sb-btn sb-btn-secondary">Dersleri Gor</a>
                        @else
                            <a href="{{ route('login') }}" class="sb-btn sb-btn-primary">Giris Yap</a>
                            <a href="{{ route('register') }}" class="sb-btn sb-btn-secondary">Kayit Ol</a>
                        @endauth
                    </div>
                </div>

                <aside class="sb-card sb-exam-panel" aria-label="Test ozeti">
                    <div class="sb-exam-head">
                        <div>
                            <div class="sb-kicker">Test Kurali</div>
                            <div class="sb-exam-title">100 puanlik deneme</div>
                        </div>
                        <span class="sb-pill">Aktif</span>
                    </div>

                    <div class="sb-question-list">
                        <div class="sb-question-row">
                            <span class="sb-question-no">1</span>
                            <span class="sb-question-line sb-question-line--long"></span>
                            <span class="sb-question-state">+5</span>
                        </div>
                        <div class="sb-question-row">
                            <span class="sb-question-no">2</span>
                            <span class="sb-question-line sb-question-line--short"></span>
                            <span class="sb-question-state">+5</span>
                        </div>
                        <div class="sb-question-row">
                            <span class="sb-question-no">3</span>
                            <span class="sb-question-line sb-question-line--mid"></span>
                            <span class="sb-question-state">Bekliyor</span>
                        </div>
                    </div>

                    <div class="sb-metrics">
                        <div class="sb-metric">
                            <span class="sb-metric-value">20</span>
                            <span class="sb-metric-label">Soru</span>
                        </div>
                        <div class="sb-metric">
                            <span class="sb-metric-value">30</span>
                            <span class="sb-metric-label">Dakika</span>
                        </div>
                        <div class="sb-metric">
                            <span class="sb-metric-value">+5</span>
                            <span class="sb-metric-label">Dogru</span>
                        </div>
                        <div class="sb-metric">
                            <span class="sb-metric-value">3</span>
                            <span class="sb-metric-label">Mod</span>
                        </div>
                    </div>
                </aside>
            </div>
        </header>

        <section class="sb-section">
            <div class="sb-section-head">
                <div class="sb-kicker">Canli Ozet</div>
                <h2>Sistem durumu</h2>
                <p>Veriler mevcut veritabanindan okunur; seed veya import yaptikca bu alanlar guncellenir.</p>
            </div>

            <div class="sb-metrics">
                @foreach([
                    'Aktif Ders' => $stats['subjects'],
                    'Aktif Soru' => $stats['questions'],
                    'Tamamlanan Test' => $stats['tests'],
                    'Kayitli Kullanici' => $stats['users'],
                ] as $label => $value)
                    <div class="sb-metric">
                        <span class="sb-metric-value">{{ $value }}</span>
                        <span class="sb-metric-label">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="sb-section">
            <div class="sb-feature-grid">
                <article class="sb-feature">
                    <div class="sb-kicker">Ogrenci</div>
                    <h3>Test cozme</h3>
                    <p>Random, zorluk araligi ve takildiklarim modlariyla ders bazli test baslatilir.</p>
                </article>
                <article class="sb-feature">
                    <div class="sb-kicker">Katki</div>
                    <h3>Soru onerisi</h3>
                    <p>Kullanicilar soru gonderir; onaylanan her soru icin +10 puan kazanir.</p>
                </article>
                <article class="sb-feature">
                    <div class="sb-kicker">Editor</div>
                    <h3>Moderasyon</h3>
                    <p>Bekleyen sorular onaylanir, reddedilir veya daha sonra geri alinabilir.</p>
                </article>
                <article class="sb-feature">
                    <div class="sb-kicker">Admin</div>
                    <h3>Yonetim</h3>
                    <p>Import, ayarlar, audit log, kullanici rolleri ve soru rollback islemleri tek paneldedir.</p>
                </article>
            </div>
        </section>

        <section class="sb-flow">
            <div class="sb-section">
                <div>
                    <div class="sb-kicker">Akis</div>
                    <h2>Uygulama nasil kullanilir?</h2>
                    <p>Ilk kullanim icin admin hesabi ile girip dersleri ve sorulari kontrol edin.</p>
                </div>
                <div class="sb-flow-grid">
                    <div class="sb-flow-step">
                        <strong>1. Ders ve soru havuzu</strong>
                        <span>Admin panelinden dersleri, sorulari ve import onizlemelerini yonetin.</span>
                    </div>
                    <div class="sb-flow-step">
                        <strong>2. Test baslat</strong>
                        <span>Kullanici 20 soruluk testi baslatir, cevaplar ve sonuc ekranini gorur.</span>
                    </div>
                    <div class="sb-flow-step">
                        <strong>3. Sira ve istatistik</strong>
                        <span>Snapshot komutu leaderboard kayitlarini gunceller.</span>
                    </div>
                </div>
            </div>
        </section>

        <footer class="sb-footer">
            <div class="sb-footer__inner">
                <span>Soru Bankasi</span>
                <span>Health: <a href="{{ route('health') }}">/health</a></span>
            </div>
        </footer>
    </div>
</body>
</html>
