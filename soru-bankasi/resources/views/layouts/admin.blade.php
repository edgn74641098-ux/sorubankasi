<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Soru Bankasi') }}</title>
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    <link rel="stylesheet" href="{{ asset('css/sorubank-theme.css') }}">
</head>
<body class="sb-admin-body">
    <div class="container-fluid">
        <div class="row min-vh-100">
            <aside class="sb-admin-sidebar col-12 col-md-3 col-xl-2 bg-dark text-white p-0">
                <div class="p-3 border-bottom border-secondary">
                    <a href="{{ route('admin.dashboard') }}" class="text-white text-decoration-none fw-bold fs-5">Soru Bankasi</a>
                    <div class="small text-secondary mt-1">Yonetim Paneli</div>
                </div>
                <div class="list-group list-group-flush rounded-0">
                    <a href="{{ route('admin.dashboard') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.dashboard') ? 'active' : 'bg-dark text-white border-secondary' }}">
                        Panel
                    </a>
                    <a href="{{ route('admin.subjects.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.subjects.*') ? 'active' : 'bg-dark text-white border-secondary' }}">
                        Dersler
                    </a>
                    <a href="{{ route('admin.questions.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.questions.*') ? 'active' : 'bg-dark text-white border-secondary' }}">
                        Sorular
                    </a>
                    <a href="{{ route('admin.imports.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.imports.*') ? 'active' : 'bg-dark text-white border-secondary' }}">
                        Import
                    </a>
                    <a href="{{ route('admin.submissions.pending') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.submissions.*') ? 'active' : 'bg-dark text-white border-secondary' }}">
                        Oneriler
                    </a>
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.users.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.users.*') ? 'active' : 'bg-dark text-white border-secondary' }}">
                            Kullanicilar
                        </a>
                        <a href="{{ route('admin.settings.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.settings.*') ? 'active' : 'bg-dark text-white border-secondary' }}">
                            Ayarlar
                        </a>
                        <a href="{{ route('admin.audit-logs.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.audit-logs.*') ? 'active' : 'bg-dark text-white border-secondary' }}">
                            Audit Log
                        </a>
                    @endif
                </div>
            </aside>

            <div class="col-12 col-md-9 col-xl-10 p-0">
                <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-3">
                    <div class="container-fluid px-0">
                        <span class="navbar-brand mb-0 h1">{{ $pageTitle ?? 'Yonetim Paneli' }}</span>
                        <div class="d-flex align-items-center gap-3">
                            <a href="{{ route('subjects.index') }}" class="btn btn-outline-secondary btn-sm">Siteye Don</a>
                            <div class="text-end">
                                <div class="fw-semibold">{{ auth()->user()->name }}</div>
                                <div class="small text-muted">{{ auth()->user()->email }}</div>
                            </div>
                        </div>
                    </div>
                </nav>

                <main class="p-4">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>
    </div>
</body>
</html>
