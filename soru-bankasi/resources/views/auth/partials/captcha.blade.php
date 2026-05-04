<div class="mt-4">
    <x-input-label for="captcha_answer" value="Guvenlik dogrulamasi" />
    <div class="mt-2 flex items-center gap-3">
        <img src="{{ $captcha['svg'] }}" alt="Guvenlik dogrulama sorusu" class="rounded-md border border-gray-200 bg-gray-50">
        <x-text-input id="captcha_answer" class="block w-28" type="text" name="captcha_answer" required inputmode="numeric" autocomplete="off" />
    </div>
    <x-input-error :messages="$errors->get('captcha_answer')" class="mt-2" />
</div>
