<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Kayıt olduğunuz için teşekkürler. Başlamadan önce e-posta adresinizi doğrulamanız gerekiyor.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            Yeni doğrulama bağlantısı e-posta adresinize gönderildi.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>Doğrulama E-postasını Yeniden Gönder</x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Çıkış Yap
            </button>
        </form>
    </div>
</x-guest-layout>
