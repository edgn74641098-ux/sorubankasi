<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="E-posta" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" value="Şifre" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        @include('auth.partials.captcha', ['captcha' => $captcha])

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">Beni hatırla</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request') && app(\App\Services\SettingsService::class)->getBool('password_reset_enabled', false))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                    Şifrenizi mi unuttunuz?
                </a>
            @endif

            <x-primary-button class="ms-3">
                Giriş Yap
            </x-primary-button>
        </div>
    </form>

    @if(app(\App\Services\SettingsService::class)->getBool('google_auth_enabled', false))
        <div class="my-4 flex items-center gap-3">
            <div class="h-px flex-1 bg-gray-200"></div>
            <div class="text-xs uppercase text-gray-500">veya</div>
            <div class="h-px flex-1 bg-gray-200"></div>
        </div>

        @include('auth.partials.google-button')
    @endif
</x-guest-layout>
