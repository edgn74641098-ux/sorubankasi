<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('admin.settings.index', [
            'groups' => collect($definitions)
                ->groupBy('group')
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
        $validated = $request->validate($this->rules());

        $oldValues = [];
        $newValues = [];

        foreach ($this->definitions() as $key => $definition) {
            $old = $this->settings->get($key, $definition['default']);
            $new = $this->castValue($validated[$key], $definition['type']);

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
        }

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Ayarlar guncellendi.');
    }

    private function rules(): array
    {
        return [
            'test_feedback_mode' => ['required', Rule::in(['DELAYED_FEEDBACK', 'INSTANT_FEEDBACK_LOCKED', 'NO_FEEDBACK'])],
            'registration_open' => ['required', 'boolean'],
            'inactive_login_message' => ['required', 'string', 'min:10', 'max:500'],
            'email_verification_required' => ['required', 'boolean'],
            'google_auth_enabled' => ['required', 'boolean'],
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
            'email_verification_required' => ['group' => 'auth', 'label' => 'E-posta dogrulama zorunlu', 'type' => 'boolean', 'default' => true],
            'google_auth_enabled' => ['group' => 'auth', 'label' => 'Google ile giris', 'type' => 'boolean', 'default' => true],
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
        ];
    }

    private function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL),
            'integer' => (int) $value,
            default => (string) $value,
        };
    }
}
