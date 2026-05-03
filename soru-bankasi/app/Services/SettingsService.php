<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    private const CACHE_PREFIX = 'settings.';

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever(
            $this->cacheKey($key),
            function () use ($key, $default) {
                $setting = Setting::query()->where('key', $key)->first();

                if (! $setting) {
                    return $default;
                }

                return Setting::decodeValue($setting->value_type, $setting->value);
            }
        );
    }

    public function set(string $key, mixed $value): Setting
    {
        $setting = Setting::query()->firstOrNew(['key' => $key]);
        $setting->setTypedValue($value);
        $setting->save();

        Cache::forever($this->cacheKey($key), $setting->typed_value);

        return $setting;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default);
    }

    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    public function getString(string $key, string $default = ''): string
    {
        return (string) $this->get($key, $default);
    }

    private function cacheKey(string $key): string
    {
        return self::CACHE_PREFIX.$key;
    }
}
