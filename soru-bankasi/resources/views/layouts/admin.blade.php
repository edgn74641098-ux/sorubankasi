<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Soru Bankasi') }}</title>
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="admin-body">
    @php
        $navItems = [
            ['label' => 'Panel', 'route' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')],
            ['label' => 'Dersler', 'route' => route('admin.subjects.index'), 'active' => request()->routeIs('admin.subjects.*')],
            ['label' => 'Sorular', 'route' => route('admin.questions.index'), 'active' => request()->routeIs('admin.questions.*')],
            ['label' => 'Arsiv', 'route' => route('admin.archive.index'), 'active' => request()->routeIs('admin.archive.*')],
            ['label' => 'Import', 'route' => route('admin.imports.index'), 'active' => request()->routeIs('admin.imports.*')],
            ['label' => 'Oneriler', 'route' => route('admin.submissions.pending'), 'active' => request()->routeIs('admin.submissions.*')],
            ['label' => 'Itirazlar', 'route' => route('admin.reports.index'), 'active' => request()->routeIs('admin.reports.*')],
        ];

        if (auth()->user()->isAdmin()) {
            $navItems = array_merge($navItems, [
                ['label' => 'Kullanicilar', 'route' => route('admin.users.index'), 'active' => request()->routeIs('admin.users.*')],
                ['label' => 'Ayarlar', 'route' => route('admin.settings.index'), 'active' => request()->routeIs('admin.settings.*')],
                ['label' => 'Audit Log', 'route' => route('admin.audit-logs.index'), 'active' => request()->routeIs('admin.audit-logs.*')],
            ]);
        }
    @endphp

    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <a href="{{ route('admin.dashboard') }}" class="admin-brand__mark">SB</a>
                <div>
                    <a href="{{ route('admin.dashboard') }}" class="admin-brand__title">Soru Bankasi</a>
                    <div class="admin-brand__meta">Yonetim paneli</div>
                </div>
            </div>

            <div class="admin-profile-card">
                <div class="admin-avatar">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</div>
                <div>
                    <div class="admin-profile-name">{{ auth()->user()->name }}</div>
                    <div class="admin-profile-role">{{ auth()->user()->isAdmin() ? 'Administrator' : 'Editor' }}</div>
                </div>
            </div>

            <nav class="admin-nav" aria-label="Yonetim menusu">
                @foreach($navItems as $item)
                    <a href="{{ $item['route'] }}" class="admin-nav__link {{ $item['active'] ? 'is-active' : '' }}">
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="admin-sidebar__foot">
                <div class="admin-sidebar__label">Hizli Durum</div>
                <div class="admin-sidebar__note">Tum ana bolumlere bu menuden erisin.</div>
            </div>
        </aside>

        <div class="admin-main">
            <header class="admin-topbar">
                <div>
                    <div class="admin-topbar__title">{{ $pageTitle ?? $title ?? 'Yonetim' }}</div>
                    <div class="admin-topbar__subtitle">Tum aktif operasyonlari ve kritik islemleri buradan takip edin.</div>
                </div>

                <div class="admin-topbar__actions">
                    <form method="GET" action="{{ route('admin.search') }}" class="admin-search">
                        <input type="search" name="q" value="{{ request('q') }}" placeholder="Hemen ara..." aria-label="Yonetim panelinde ara">
                        <button type="submit">Ara</button>
                    </form>
                    <a href="{{ route('subjects.index') }}" class="admin-top-link">Ana Sayfa</a>
                </div>
            </header>

            <main class="admin-content">
                @if (session('success'))
                    <div class="alert alert-success admin-alert">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger admin-alert">
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
</body>
</html>
