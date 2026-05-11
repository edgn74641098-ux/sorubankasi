<?php

namespace App\Providers;

use App\Services\RateLimitingService;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SettingsService::class, fn () => new SettingsService());
        $this->app->singleton(RateLimitingService::class, function ($app) {
            return new RateLimitingService($app->make(SettingsService::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            /** @var SettingsService $settings */
            $settings = $this->app->make(SettingsService::class);

            $encryption = $settings->getString('mail_encryption', (string) (config('mail.mailers.smtp.encryption') ?? ''));

            config([
                'mail.default' => $settings->getString('mail_mailer', config('mail.default', 'smtp')),
                'mail.mailers.smtp.host' => $settings->getString('mail_host', (string) config('mail.mailers.smtp.host')),
                'mail.mailers.smtp.port' => $settings->getInt('mail_port', (int) config('mail.mailers.smtp.port')),
                'mail.mailers.smtp.encryption' => $encryption !== '' ? $encryption : null,
                'mail.mailers.smtp.username' => $settings->getString('mail_username', (string) (config('mail.mailers.smtp.username') ?? '')),
                'mail.mailers.smtp.password' => $settings->getString('mail_password', (string) (config('mail.mailers.smtp.password') ?? '')),
                'mail.from.address' => $settings->getString('mail_from_address', (string) config('mail.from.address', 'hello@example.com')),
                'mail.from.name' => $settings->getString('mail_from_name', (string) config('mail.from.name', config('app.name', 'Soru Bankasi'))),
            ]);
        } catch (Throwable) {
            // Intentionally swallow exceptions so app boot does not break on early setup.
        }
    }
}
