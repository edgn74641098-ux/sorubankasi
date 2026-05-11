<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditLogService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogService $auditLog
    ) {
    }

    public function index(): View
    {
        $definitions = $this->definitions();
        $this->ensureSettingsExist($definitions);

        return view('admin.settings.index', [
            'groups' => collect($definitions)
                ->groupBy('group', preserveKeys: true)
                ->map(fn ($items) => $items->mapWithKeys(fn (array $definition, string $key) => [
                    $key => array_merge($definition, [
                        'value' => $this->settings->get($key, $definition['default']),
                    ]),
                ]))
                ->all(),
            'groupLabels' => $this->groupLabels(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $definitions = $this->definitions();
        $this->ensureSettingsExist($definitions);

        $validated = $request->validate($this->rules());

        $oldValues = [];
        $newValues = [];

        foreach ($definitions as $key => $definition) {
            $old = $this->settings->get($key, $definition['default']);
            $new = $this->castValue($validated[$key], $definition['type']);

            // Keep existing password unless admin explicitly clears it or sets a new one.
            if ($key === 'mail_password') {
                $clearPassword = filter_var($request->input('mail_password_clear', false), FILTER_VALIDATE_BOOL);
                $newPassword = (string) $request->input('mail_password', '');

                if ($clearPassword) {
                    $new = '';
                } elseif ($newPassword === '') {
                    $new = (string) $old;
                }
            }

            if ($old === $new) {
                continue;
            }

            $oldValues[$key] = $old;
            $newValues[$key] = $new;
            $this->settings->set($key, $new);
        }

        if ($newValues !== []) {
            $this->auditLog->record(
                $request->user(),
                'settings.updated',
                'settings',
                null,
                $oldValues,
                $newValues,
                'Admin ayarlari guncellendi.',
                $request
            );

            if ($this->hasMailSettingChanges($newValues)) {
                Artisan::call('queue:restart');
            }
        }

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Ayarlar guncellendi.');
    }

    private function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'test_feedback_mode' => ['required', Rule::in(['DELAYED_FEEDBACK', 'INSTANT_FEEDBACK_LOCKED', 'NO_FEEDBACK'])],
            'registration_open' => ['required', 'boolean'],
            'inactive_login_message' => ['required', 'string', 'min:10', 'max:500'],
            'email_verification_required' => ['required', 'boolean'],
            'google_auth_enabled' => ['required', 'boolean'],
            'password_reset_enabled' => ['required', 'boolean'],
            'daily_test_limit' => ['required', 'integer', 'between:1,100'],
            'daily_question_limit' => ['required', 'integer', 'between:1,100'],
            'login_rate_limit' => ['required', 'integer', 'between:1,20'],
            'login_lockout_duration' => ['required', 'integer', 'between:60,86400'],
            'minimum_leaderboard_tests' => ['required', 'integer', 'between:1,20'],
            'correct_answer_points' => ['required', 'integer', 'between:0,100'],
            'wrong_answer_penalty_enabled' => ['required', 'boolean'],
            'wrong_answer_penalty_points' => ['required', 'integer', 'between:0,100'],
            'blank_answer_points' => ['required', 'integer', 'between:0,100'],
            'leaderboard_global_limit' => ['required', 'integer', 'between:1,100'],
            'leaderboard_weekly_limit' => ['required', 'integer', 'between:1,50'],
            'leaderboard_form_limit' => ['required', 'integer', 'between:1,50'],
            'question_report_accept_message' => ['required', 'string', 'min:10', 'max:1000'],
            'user_submissions_enabled' => ['required', 'boolean'],
            'submission_approval_reward' => ['required', 'integer', 'between:0,1000'],
            'submission_rejection_note_required' => ['required', 'boolean'],
            'archive_retention_days' => ['required', 'integer', 'between:1,365'],
            'archive_auto_prune_enabled' => ['required', 'boolean'],
            'maintenance_mode' => ['required', 'boolean'],
            'backup_mode' => ['required', Rule::in(['manual', 'automatic'])],
            'mail_mailer' => ['required', Rule::in(['smtp', 'log'])],
            'mail_host' => ['required_if:mail_mailer,smtp', 'nullable', 'string', 'max:255'],
            'mail_port' => ['required_if:mail_mailer,smtp', 'nullable', 'integer', 'between:1,65535'],
            'mail_encryption' => ['nullable', Rule::in(['', 'tls', 'ssl'])],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_password_clear' => ['nullable', 'boolean'],
            'mail_from_address' => ['required', 'email', 'max:255'],
            'mail_from_name' => ['required', 'string', 'max:255'],
        ];
    }

    private function definitions(): array
    {
        return [
            'test_feedback_mode' => ['group' => 'test', 'label' => 'Test feedback modu', 'type' => 'string', 'default' => 'DELAYED_FEEDBACK'],
            'daily_test_limit' => ['group' => 'test', 'label' => 'Gunluk test limiti', 'type' => 'integer', 'default' => 20],
            'daily_question_limit' => ['group' => 'submissions', 'label' => 'Gunluk soru onerisi limiti', 'type' => 'integer', 'default' => 20],

            'registration_open' => ['group' => 'auth', 'label' => 'Yeni kullanici kaydi', 'type' => 'boolean', 'default' => true],
            'inactive_login_message' => ['group' => 'auth', 'label' => 'Pasif kullanici giris mesaji', 'type' => 'text', 'default' => 'Kullanici hesabiniz pasif duruma getirilmistir. Lutfen yonetici ile iletisime gecin.'],
            'email_verification_required' => ['group' => 'auth', 'label' => 'E-posta dogrulama zorunlu', 'type' => 'boolean', 'default' => false],
            'google_auth_enabled' => ['group' => 'auth', 'label' => 'Google ile giris', 'type' => 'boolean', 'default' => false],
            'password_reset_enabled' => ['group' => 'auth', 'label' => 'Sifremi unuttum linki', 'type' => 'boolean', 'default' => false],
            'login_rate_limit' => ['group' => 'auth', 'label' => 'Login deneme limiti', 'type' => 'integer', 'default' => 5],
            'login_lockout_duration' => ['group' => 'auth', 'label' => 'Login kilit suresi (sn)', 'type' => 'integer', 'default' => 900],

            'correct_answer_points' => ['group' => 'leaderboard', 'label' => 'Dogru cevap puani', 'type' => 'integer', 'default' => 5],
            'wrong_answer_penalty_enabled' => ['group' => 'leaderboard', 'label' => 'Yanlis cevap cezasi', 'type' => 'boolean', 'default' => false],
            'wrong_answer_penalty_points' => ['group' => 'leaderboard', 'label' => 'Yanlis cevap ceza puani', 'type' => 'integer', 'default' => 0],
            'blank_answer_points' => ['group' => 'leaderboard', 'label' => 'Bos cevap puani', 'type' => 'integer', 'default' => 0],
            'minimum_leaderboard_tests' => ['group' => 'leaderboard', 'label' => 'Leaderboard minimum test', 'type' => 'integer', 'default' => 3],
            'leaderboard_global_limit' => ['group' => 'leaderboard', 'label' => 'Global siralama sayisi', 'type' => 'integer', 'default' => 20],
            'leaderboard_weekly_limit' => ['group' => 'leaderboard', 'label' => 'Haftalik lider sayisi', 'type' => 'integer', 'default' => 5],
            'leaderboard_form_limit' => ['group' => 'leaderboard', 'label' => 'En iyi form sayisi', 'type' => 'integer', 'default' => 5],

            'question_report_accept_message' => ['group' => 'reports', 'label' => 'Itiraz kabul mesaji', 'type' => 'text', 'default' => 'Itiraziniz kabul edildi. Katkiniz icin tesekkur ederiz. Sorunun dogru cevabi {old_answer} yerine {new_answer} olarak guncellendi.'],

            'user_submissions_enabled' => ['group' => 'submissions', 'label' => 'Kullanici soru onerisi', 'type' => 'boolean', 'default' => true],
            'submission_approval_reward' => ['group' => 'submissions', 'label' => 'Oneri onay odulu', 'type' => 'integer', 'default' => 10],
            'submission_rejection_note_required' => ['group' => 'submissions', 'label' => 'Red notu zorunlu', 'type' => 'boolean', 'default' => true],

            'archive_retention_days' => ['group' => 'archive', 'label' => 'Arsiv saklama gunu', 'type' => 'integer', 'default' => 7],
            'archive_auto_prune_enabled' => ['group' => 'archive', 'label' => 'Arsiv otomatik silme', 'type' => 'boolean', 'default' => true],

            'maintenance_mode' => ['group' => 'system', 'label' => 'Bakim modu', 'type' => 'boolean', 'default' => false],
            'backup_mode' => ['group' => 'system', 'label' => 'Yedekleme modu', 'type' => 'string', 'default' => 'manual'],

            'mail_mailer' => ['group' => 'mail', 'label' => 'Mail gonderim yontemi', 'type' => 'string', 'default' => 'log'],
            'mail_host' => ['group' => 'mail', 'label' => 'SMTP host', 'type' => 'string', 'default' => 'smtp.mailgun.org'],
            'mail_port' => ['group' => 'mail', 'label' => 'SMTP port', 'type' => 'integer', 'default' => 587],
            'mail_encryption' => ['group' => 'mail', 'label' => 'SMTP sifreleme', 'type' => 'string', 'default' => 'tls'],
            'mail_username' => ['group' => 'mail', 'label' => 'SMTP kullanici adi', 'type' => 'string', 'default' => ''],
            'mail_password' => ['group' => 'mail', 'label' => 'SMTP sifre', 'type' => 'string', 'default' => ''],
            'mail_from_address' => ['group' => 'mail', 'label' => 'Gonderen e-posta adresi', 'type' => 'string', 'default' => 'hello@example.com'],
            'mail_from_name' => ['group' => 'mail', 'label' => 'Gonderen adi', 'type' => 'string', 'default' => 'Soru Bankasi'],
        ];
    }

    private function groupLabels(): array
    {
        return [
            'auth' => 'Kayit ve Giris',
            'test' => 'Test Davranisi',
            'leaderboard' => 'Puanlama ve Leaderboard',
            'reports' => 'Itirazlar',
            'submissions' => 'Oneriler',
            'archive' => 'Arsiv ve Silme',
            'system' => 'Sistem',
            'mail' => 'Mail Gonderim',
        ];
    }

    private function ensureSettingsExist(array $definitions): void
    {
        $existingKeys = Setting::query()
            ->whereIn('key', array_keys($definitions))
            ->pluck('key')
            ->all();

        foreach (array_diff(array_keys($definitions), $existingKeys) as $key) {
            $this->settings->set($key, $definitions[$key]['default']);
        }
    }

    private function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL),
            'integer' => (int) $value,
            default => (string) $value,
        };
    }

    private function hasMailSettingChanges(array $newValues): bool
    {
        foreach (array_keys($newValues) as $key) {
            if (str_starts_with($key, 'mail_')) {
                return true;
            }
        }

        return false;
    }
}
