<nav class="navbar navbar-expand-lg bg-white border-bottom sb-app-navbar">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ route('dashboard') }}">
            <span class="sb-brand-mark">SB</span>
            <span>Soru Bankasi</span>
        </a>

        <button
            class="navbar-toggler d-lg-none sb-app-menu-toggle"
            type="button"
            id="appNavbarToggler"
            aria-controls="appNavbar"
            aria-expanded="false"
            aria-label="Menü"
        >
            <i class="bi bi-list"></i>
        </button>

        {{-- Bootstrap `.collapse` ile Tailwind `.collapse` çakışır; menü açılmasını kendimiz yönetiyoruz. --}}
        <div class="navbar-collapse sb-navbar-menu" id="appNavbar">
            <div class="sb-mobile-menu-head d-lg-none">
                <div class="sb-mobile-menu-brand">
                    <span class="sb-brand-mark">SB</span>
                    <div>
                        <strong>Soru Bankasi</strong>
                        <small>Kullanici menusu</small>
                    </div>
                </div>
                <div class="sb-mobile-menu-user">
                    <span class="sb-mobile-menu-avatar">{{ str(Auth::user()->name)->substr(0, 1)->upper() }}</span>
                    <div>
                        <strong>{{ Auth::user()->name }}</strong>
                        <small>{{ Auth::user()->isAdmin() ? 'Administrator' : (Auth::user()->isEditor() ? 'Editor' : 'Kullanici') }}</small>
                    </div>
                </div>
            </div>

            <ul class="navbar-nav me-auto mb-2 mb-lg-0 sb-mobile-nav-list">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('search.*') ? 'active fw-semibold' : '' }}" href="{{ route('search.index') }}">Ara</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('questions.favorites.*') ? 'active fw-semibold' : '' }}" href="{{ route('questions.favorites.index') }}">Favorilerim</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active fw-semibold' : '' }}" href="{{ route('dashboard') }}">Panel</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('subjects.*') ? 'active fw-semibold' : '' }}" href="{{ route('subjects.index') }}">Dersler</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('leaderboard.*') ? 'active fw-semibold' : '' }}" href="{{ route('leaderboard.index') }}">Leaderboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('questions.submitted') ? 'active fw-semibold' : '' }}" href="{{ route('questions.submitted') }}">Onerilerim</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('questions.reports') ? 'active fw-semibold' : '' }}" href="{{ route('questions.reports') }}">Itirazlarim</a>
                </li>
                @if(Auth::user()->isAdmin() || Auth::user()->isEditor())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.*') ? 'active fw-semibold' : '' }}" href="{{ route('admin.dashboard') }}">Yonetim</a>
                    </li>
                @endif
            </ul>

            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    {{ Auth::user()->name }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Profil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">Cikis Yap</button>
                        </form>
                    </li>
                </ul>
            </div>

            <div class="sb-mobile-menu-foot d-lg-none">
                <small>Tum ana bolumlere buradan erisin.</small>
            </div>
        </div>
    </div>
    <div class="sb-app-navbar-backdrop" id="appNavbarBackdrop" hidden></div>
    <script src="{{ asset('js/navigation.js') }}" defer></script>
</nav>
