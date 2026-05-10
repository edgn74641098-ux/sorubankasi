<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Soru Bankasi') }}</title>

        @vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.js', 'resources/js/admin.js'])
        <link rel="stylesheet" href="{{ asset('css/sorubank-theme.css') }}">
    </head>
    <body class="sb-auth-shell">
        <div class="min-vh-100 d-flex flex-column">
            <div class="container flex-grow-1 d-flex align-items-center justify-content-center py-5">
                <div class="w-100 sb-auth-width">
                    <a href="/" class="d-flex align-items-center justify-content-center gap-2 mb-4 text-decoration-none">
                        <span class="sb-brand-mark">SB</span>
                        <span class="fw-bold text-white">Soru Bankasi</span>
                    </a>

                    <div class="sb-auth-card bg-white p-4">
                        {{ $slot }}
                    </div>
                </div>
            </div>

            @include('layouts.footer')
        </div>
    </body>
</html>
