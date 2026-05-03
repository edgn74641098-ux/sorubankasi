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
        return view('admin.settings.index', [
            'settings' => collect($this->definitions())
                ->mapWithKeys(fn (array $definition, string $key) => [
                    $key => array_merge($definition, [
                        'value' => $this->settings->get($key, $definition['default']),
                    ]),
                ])
                ->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'test_feedback_mode' => ['required', Rule::in(['DELAYED_FEEDBACK', 'INSTANT_FEEDBACK_LOCKED', 'NO_FEEDBACK'])],
            'registration_open' => ['required', 'boolean'],
            'daily_test_limit' => ['required', 'integer', 'between:1,100'],
            'daily_question_limit' => ['required', 'integer', 'between:1,100'],
            'login_rate_limit' => ['required', 'integer', 'between:1,20'],
            'login_lockout_duration' => ['required', 'integer', 'between:60,86400'],
            'minimum_leaderboard_tests' => ['required', 'integer', 'between:1,20'],
            'maintenance_mode' => ['required', 'boolean'],
            'backup_mode' => ['required', Rule::in(['manual', 'automatic'])],
        ]);

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

    private function definitions(): array
    {
        return [
            'test_feedback_mode' => ['label' => 'Test feedback modu', 'type' => 'string', 'default' => 'DELAYED_FEEDBACK'],
            'registration_open' => ['label' => 'Kayit acik', 'type' => 'boolean', 'default' => true],
            'daily_test_limit' => ['label' => 'Gunluk test limiti', 'type' => 'integer', 'default' => 20],
            'daily_question_limit' => ['label' => 'Gunluk soru onerisi limiti', 'type' => 'integer', 'default' => 20],
            'login_rate_limit' => ['label' => 'Login deneme limiti', 'type' => 'integer', 'default' => 5],
            'login_lockout_duration' => ['label' => 'Login kilit suresi (sn)', 'type' => 'integer', 'default' => 900],
            'minimum_leaderboard_tests' => ['label' => 'Leaderboard minimum test', 'type' => 'integer', 'default' => 3],
            'maintenance_mode' => ['label' => 'Bakim modu', 'type' => 'boolean', 'default' => false],
            'backup_mode' => ['label' => 'Yedekleme modu', 'type' => 'string', 'default' => 'manual'],
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
