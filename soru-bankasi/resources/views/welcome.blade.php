<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Soru Bankasi') }}</title>
    <link rel="stylesheet" href="{{ asset('css/sorubank-theme.css') }}">
</head>
<body class="sb-entry-body">
    <main class="sb-entry-shell">
        <section class="sb-entry-card" aria-label="Soru Bankasi giris ekrani">
            <div class="sb-entry-actions">
                @auth
                    <a href="{{ route('dashboard') }}" class="sb-entry-button sb-entry-button--primary">Panele Git</a>
                    @if(auth()->user()->isAdmin() || auth()->user()->isEditor())
                        <a href="{{ route('admin.dashboard') }}" class="sb-entry-button sb-entry-button--secondary">Yonetim Paneli</a>
                    @endif
                @else
                    <a href="{{ route('register') }}" class="sb-entry-button sb-entry-button--primary">Kayit Ol</a>
                    <a href="{{ route('login') }}" class="sb-entry-button sb-entry-button--secondary">Sisteme Giris Yap</a>
                @endauth
            </div>
        </section>
    </main>
</body>
</html>
