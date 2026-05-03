<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'test_feedback_mode' => 'DELAYED_FEEDBACK',
            'registration_open' => true,
            'daily_test_limit' => 20,
            'daily_question_limit' => 20,
            'login_rate_limit' => 5,
            'login_lockout_duration' => 900,
            'minimum_leaderboard_tests' => 3,
            'maintenance_mode' => false,
            'backup_mode' => 'manual',
        ];

        foreach ($settings as $key => $value) {
            $setting = Setting::query()->firstOrNew(['key' => $key]);
            $setting->setTypedValue($value);
            $setting->save();
        }
    }
}
