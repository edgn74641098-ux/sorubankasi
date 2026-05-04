@if(app(\App\Services\SettingsService::class)->getBool('google_auth_enabled', false))
    <a href="{{ route('auth.google.redirect') }}"
       class="btn btn-outline-secondary w-100 mt-3 d-flex align-items-center justify-content-center gap-2">
        <span class="fw-bold text-primary">G</span>
        Google ile devam et
    </a>
@endif
