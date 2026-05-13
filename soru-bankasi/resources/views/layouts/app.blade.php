<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Soru Bankası') }}</title>

        @vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.js', 'resources/js/admin.js'])
        <link rel="stylesheet" href="{{ asset('css/sorubank-theme.css') }}">
        @stack('head')
    </head>
    <body class="font-sans antialiased">
        <div class="sb-app-shell min-h-screen d-flex flex-column">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="sb-app-header bg-white">
                    <div class="container py-4">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="flex-grow-1">
                @isset($slot)
                    {{ $slot }}
                @else
                    @yield('content')
                @endisset
            </main>

            @include('layouts.footer')
        </div>
        @stack('scripts')
    </body>
</html>
