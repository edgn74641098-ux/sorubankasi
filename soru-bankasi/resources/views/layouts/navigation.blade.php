<nav class="navbar navbar-expand-lg bg-white border-bottom sb-app-navbar">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ route('dashboard') }}">
            <span class="sb-brand-mark">SB</span>
            <span>Soru Bankasi</span>
        </a>

        <button
            class="navbar-toggler d-lg-none"
            type="button"
            id="appNavbarToggler"
            aria-controls="appNavbar"
            aria-expanded="false"
            aria-label="Menu"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        {{-- Bootstrap `.collapse` ile Tailwind `.collapse` cakisir; menu acilmasini kendimiz yonetiyoruz. --}}
        <div class="navbar-collapse sb-navbar-menu" id="appNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active fw-semibold' : '' }}" href="{{ route('dashboard') }}">Panel</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('subjects.*') ? 'active fw-semibold' : '' }}" href="{{ route('subjects.index') }}">Dersler</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('tests.*') ? 'active fw-semibold' : '' }}" href="{{ route('tests.create') }}">Testler</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('leaderboard.*') ? 'active fw-semibold' : '' }}" href="{{ route('leaderboard.index') }}">Leaderboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('questions.*') ? 'active fw-semibold' : '' }}" href="{{ route('questions.submitted') }}">Onerilerim</a>
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
        </div>
    </div>
    <script>
        (function () {
            var toggler = document.getElementById('appNavbarToggler');
            var panel = document.getElementById('appNavbar');
            if (!toggler || !panel) {
                return;
            }
            toggler.addEventListener('click', function () {
                var open = panel.classList.toggle('is-open');
                toggler.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            window.matchMedia('(min-width: 992px)').addEventListener('change', function (e) {
                if (e.matches) {
                    panel.classList.remove('is-open');
                    toggler.setAttribute('aria-expanded', 'false');
                }
            });
        })();
    </script>
</nav>
