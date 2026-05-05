<div class="mt-4">
    <x-input-label for="captcha_answer" value="Guvenlik dogrulamasi" />
    <div class="sb-auth-captcha mt-2">
        <img src="{{ $captcha['svg'] }}" alt="Guvenlik dogrulama sorusu" class="sb-auth-captcha__image">
        <x-text-input id="captcha_answer" class="sb-auth-captcha__input" type="text" name="captcha_answer" required inputmode="numeric" autocomplete="off" />
    </div>
    <x-input-error :messages="$errors->get('captcha_answer')" class="mt-2" />
</div>
